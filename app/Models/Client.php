<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Clé de groupement des mesures sans date, en fin de liste : plus petite
     * que n'importe quelle date au format Y-m-d, elle se retrouve en dernier
     * dans un classement descendant.
     */
    public const DATE_SANS_RELEVE = '0000-00-00';

    protected $fillable = [
        'atelier_id',
        'nom',
        'telephone',
        'email',
        'adresse',
        'notes',
        'photo_disk',
        'photo_path',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function atelier(): BelongsTo
    {
        return $this->belongsTo(Atelier::class);
    }

    public function mesures(): HasMany
    {
        return $this->hasMany(Mesure::class)->latest();
    }

    public function commandes(): HasMany
    {
        return $this->hasMany(Commande::class)->latest('date_commande');
    }

    public function paiements(): HasMany
    {
        return $this->hasMany(Paiement::class)->latest('date_paiement');
    }

    public function rendezVous(): HasMany
    {
        return $this->hasMany(RendezVous::class)->latest('date_debut');
    }

    public function relances(): HasMany
    {
        return $this->hasMany(ClientRelance::class)->latest();
    }

    public function hasPhoto(): bool
    {
        return filled($this->photo_path);
    }

    public function photoUrl(): string
    {
        if (! $this->hasPhoto()) {
            return '';
        }

        return route('clients.photo', $this);
    }

    public function deletePhotoFile(): void
    {
        if ($this->hasPhoto()) {
            Storage::disk($this->photo_disk)->delete($this->photo_path);
        }
    }

    /**
     * Relevés de mesures, groupés par journée de prise : une carte par
     * relevé. La fiche du client raconte alors l'historique des mensurations,
     * séance après séance, au lieu d'un instantané de la dernière valeur.
     *
     * Le groupe le plus récent vient en premier ; à l'intérieur d'un relevé,
     * l'ordre d'enregistrement est conservé. Les mesures sans date — elles
     * ont pu être saisies avant la mise en place de la colonne — forment un
     * dernier groupe « sans date », trié en fin de liste.
     *
     * @param  Collection<int, Mesure>|null  $mesures  valeurs déjà chargées (ex. le résultat filtré d'une requête)
     * @return Collection<string, Collection<int, Mesure>>
     */
   public function relevesMesures(?Collection $mesures = null): Collection
{
    $mesures ??= $this->mesures;

    return $mesures
        ->sortByDesc(fn (Mesure $mesure) => $mesure->date_mesure ?? self::DATE_SANS_RELEVE)
        ->groupBy(function (Mesure $mesure) {
            $date = $mesure->date_mesure?->toDateString() ?? self::DATE_SANS_RELEVE;
            $heure = $mesure->created_at?->format('H:i') ?? '00:00';
            return $date . '|' . $heure;
        })
        ->map(fn (Collection $releve) => $releve->sortBy('id')->values())
        ->sortKeysDesc();
}

    public function totalCommande(): float
    {
        return (float) $this->commandes()->sum('prix_total');
    }

    public function totalPaye(): float
    {
        return (float) $this->paiements()->whereIn('type', ['avance', 'solde', 'recette'])->sum('montant');
    }

    public function totalDu(): float
    {
        return round($this->totalCommande() - $this->totalPaye(), 2);
    }

    public function initiales(): string
    {
        $parts = preg_split('/\s+/', trim($this->nom)) ?: [];
        $initials = '';

        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= mb_strtoupper(mb_substr($part, 0, 1));
        }

        return $initials !== '' ? $initials : mb_strtoupper(mb_substr($this->nom, 0, 2));
    }

    public function scopeForAtelier(Builder $query, ?int $atelierId): Builder
    {
        return $query->where('atelier_id', $atelierId);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $term = trim($term);

        return $query->where(function (Builder $q) use ($term) {
            $q->where('nom', 'like', "%{$term}%")
                ->orWhere('telephone', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('adresse', 'like', "%{$term}%");
        });
    }
}
