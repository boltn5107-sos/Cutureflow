<?php

namespace App\Services;

use App\Enums\CommandeStatut;
use App\Models\Client;
use App\Models\ClientRelance;
use App\Models\Commande;
use App\Models\Mesure;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Suggestions de relance : à qui l'atelier peut encore appeler.
 *
 * L'application n'envoie rien au client — ni SMS, ni WhatsApp, ni e-mail. Elle
 * ne gère aucune transaction entre l'atelier et ses clients. Elle indique
 * seulement quels clients méritent un appel, et garde la trace des relances
 * faites pour ne pas les redemander à chaque visite.
 *
 * Trois motifs, du plus urgent au plus ancien :
 *  - pièce prête non retirée : le travail est fini, la livraison n'a pas eu lieu ;
 *  - mesures sans commande : le client s'est déplacé et n'a rien commandé ;
 *  - client inactif : aucune commande depuis un moment.
 */
class RelanceService
{
    /** Délai avant qu'une pièce soit considérée comme « prête non retirée ». */
    public const PRETE_JOURS = 7;

    /** Délai avant qu'un client soit considéré comme inactif. */
    public const INACTIF_JOURS = 60;

    /** Délai avant qu'une mesure sans commande vienne à échéance. */
    public const MESURES_JOURS = 21;

    /** Ordre d'affichage : le motif le plus urgent d'abord. */
    private const ORDRE = ['pretes', 'mesures', 'inactifs'];

    public function __construct(private readonly ?int $atelierId = null) {}

    /**
     * À toujours passer par ici plutôt que par injection de dépendance.
     *
     * Le conteneur de Laravel sait construire cette classe avec
     * atelierId = null, ce qui donne silencieusement une instance qui ne voit
     * aucun atelier : les requêtes renvoient alors zéro ligne sans erreur,
     * et la page paraît vide. C'est ce qui est arrivé une première fois.
     */
    public static function for(User $user): self
    {
        return new self($user->atelier?->id);
    }

    public function atelierId(): ?int
    {
        return $this->atelierId;
    }

    /**
     * Compteur pour le tableau de bord. Même exigence que la page : les
     * clients déjà relancés sont exclus, sinon le badge annoncerait toujours
     * plus de clients qu'il n'y a de lignes actionnables.
     */
    public function compteur(int $joursInactif = self::INACTIF_JOURS): int
    {
        $groupes = $this->suggestions($joursInactif);

        return collect($groupes)
            ->flatMap(fn (array $groupe) => $groupe['clients']->pluck('client_id'))
            ->unique()
            ->count();
    }

    /**
     * Suggestions classées par motif, chaque ligne ayant la même forme pour
     * que la vue n'ait rien à deviner : client, détail, date de référence.
     *
     * Un client déjà relancé est retiré partout. Un client qui justifie
     * deux motifs n'apparaît que dans le plus urgent : le rappeler une fois
     * suffit, et l'afficher deux fois donnerait l'impression d'un doublon.
     *
     * @return array<string, array{titre: string, icone: string, clients: Collection<int, array{client_id: int, client: Client, detail: string, depuis: Carbon|null, jours: int}>}>
     */
    public function suggestions(int $joursInactif = self::INACTIF_JOURS): array
    {
        $joursInactif = max(1, $joursInactif);
        $traites = $this->clientsTraites();

        $lignes = [
            'pretes' => $this->lignesPretesNonRetirees(),
            'mesures' => $this->lignesMesuresSansCommande(),
            'inactifs' => $this->lignesClientsInactifs($joursInactif),
        ];

        $groupes = [];
        // clientsTraites() renvoie un tableau d'ids ; $vus sert à la fois de
        // liste des clients déjà relancés et de registre des clients déjà
        // affectés à un groupe plus prioritaire.
        $vus = $traites;

        foreach (self::ORDRE as $motif) {
            $clients = $lignes[$motif]
                ->reject(fn (array $ligne) => in_array($ligne['client_id'], $vus, true))
                ->each(function (array $ligne) use (&$vus) {
                    $vus[] = $ligne['client_id'];
                })
                ->values();

            $groupes[$motif] = [
                'titre' => match ($motif) {
                    'pretes' => 'Pièces prêtes non retirées',
                    'mesures' => 'Mesures relevées, aucune commande',
                    default => 'Clients inactifs',
                },
                'icone' => match ($motif) {
                    'pretes' => 'fa-solid fa-box-open',
                    'mesures' => 'fa-solid fa-ruler-combined',
                    default => 'fa-solid fa-user-clock',
                },
                'clients' => $clients,
            ];
        }

        return $groupes;
    }

    /**
     * Ids des clients déjà relancés, tous motifs confondus.
     *
     * @return array<int, int>
     */
    public function clientsTraites(): array
    {
        return ClientRelance::forAtelier($this->atelierId)
            ->distinct()
            ->pluck('client_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Pièces passées à « prête » depuis quelques jours sans être livrées.
     * C'est un client qui s'impatiente et une pièce qui prend la place.
     *
     * @return Collection<int, array{client_id: int, client: Client, detail: string, depuis: Carbon|null, jours: int}>
     */
    private function lignesPretesNonRetirees(): Collection
    {
        $depuis = Carbon::now()->subDays(self::PRETE_JOURS);

        return Commande::forAtelier($this->atelierId)
            ->where('statut', CommandeStatut::Prete->value)
            ->where('updated_at', '<=', $depuis)
            ->whereHas('client')
            ->with('client')
            ->orderBy('updated_at')
            ->get()
            ->map(fn (Commande $commande) => $this->ligne(
                client: $commande->client,
                detail: 'Commande '.$commande->numero.' prête, pas encore livrée',
                depuis: $commande->updated_at,
            ));
    }

    /**
     * Clients dont la dernière mesure est ancienne et qui n'ont jamais
     * commandé. Un atelier en rencontre beaucoup : la mesure est prise pour
     * l'essayage, la commande part après.
     *
     * @return Collection<int, array{client_id: int, client: Client, detail: string, depuis: Carbon|null, jours: int}>
     */
    private function lignesMesuresSansCommande(): Collection
    {
        $coupe = Carbon::now()->subDays(self::MESURES_JOURS);

        /*
         * La table mesures n'a pas d'atelier_id : c'est le client qui la
         * rattache. Le périmètre passe donc par les clients de l'atelier, et
         * l'ancienneté se lit sur date_mesure, la colonne métier : created_at
         * ne dit que l'heure de saisie, pas le jour du relevé.
         */
        $dernieresMesures = Mesure::query()
            ->whereIn('client_id', Client::forAtelier($this->atelierId)->select('id'))
            ->where('date_mesure', '<=', $coupe->toDateString())
            ->groupBy('client_id')
            ->select('client_id')
            ->selectRaw('MAX(date_mesure) as derniere_mesure')
            ->pluck('derniere_mesure', 'client_id');

        if ($dernieresMesures->isEmpty()) {
            return collect();
        }

        // Un seul SELECT pour tous les candidats : vérifier client par client
        // transformerait cette page en N+1.
        $avesCommande = Commande::forAtelier($this->atelierId)
            ->whereIn('client_id', $dernieresMesures->keys())
            ->where('statut', '!=', CommandeStatut::Annulee->value)
            ->distinct()
            ->pluck('client_id');

        $candidats = $dernieresMesures->reject(fn ($date, $id) => $avesCommande->contains((int) $id));

        if ($candidats->isEmpty()) {
            return collect();
        }

        return Client::forAtelier($this->atelierId)
            ->whereIn('id', $candidats->keys())
            ->get()
            ->map(fn (Client $client) => $this->ligne(
                client: $client,
                detail: 'Mesures relevées le '.Carbon::parse($candidats[$client->id])->translatedFormat('j M Y').', aucune commande',
                depuis: Carbon::parse($candidats[$client->id])->startOfDay(),
            ))
            ->sortByDesc('depuis')
            ->values();
    }

    /**
     * Clients sans commande récente. Les commandes annulées ne comptent pas :
     * une commande annulée n'a rien produit et ne justifie pas une relance.
     *
     * @return Collection<int, array{client_id: int, client: Client, detail: string, depuis: Carbon|null, jours: int}>
     */
    private function lignesClientsInactifs(int $joursInactif): Collection
    {
        $depuis = Carbon::now()->subDays($joursInactif);

        return Client::forAtelier($this->atelierId)
            ->whereDoesntHave('commandes', fn ($q) => $q->where('date_commande', '>=', $depuis))
            ->whereHas('commandes', fn ($q) => $q->where('statut', '!=', CommandeStatut::Annulee->value))
            // withMax donne la date de la dernière commande en une requête
            // de plus, sans charger toutes les commandes du client.
            ->withMax('commandes', 'date_commande')
            ->get()
            ->map(fn (Client $client) => $this->ligne(
                client: $client,
                detail: 'Dernière commande le '.(
                    $client->commandes_max_date_commande
                        ? Carbon::parse($client->commandes_max_date_commande)->translatedFormat('j M Y')
                        : '—'
                ),
                depuis: $client->commandes_max_date_commande,
            ))
            ->sortBy('depuis')
            ->values();
    }

    /**
     * @return array{client_id: int, client: Client, detail: string, depuis: Carbon|null, jours: int}
     */
    private function ligne(Client $client, string $detail, Carbon|string|null $depuis): array
    {
        // string|null : commandes_max_date_commande arrive de la base comme
        // une chaîne, alors que les casts de modèle renvoient un Carbon.
        $depuis = $depuis ? Carbon::parse($depuis) : null;

        return [
            'client_id' => $client->id,
            'client' => $client,
            'detail' => $detail,
            'depuis' => $depuis,
            'jours' => $depuis ? (int) $depuis->diffInDays(now()) : 0,
        ];
    }
}
