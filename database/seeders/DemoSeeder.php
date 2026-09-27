<?php

namespace Database\Seeders;

use App\Enums\CommandeStatut;
use App\Enums\ModeleCategorie;
use App\Enums\PaiementMethode;
use App\Enums\PaiementType;
use App\Enums\RendezVousType;
use App\Enums\UserStatus;
use App\Models\Atelier;
use App\Models\Client;
use App\Models\Commande;
use App\Models\Depense;
use App\Models\Mesure;
use App\Models\Modele;
use App\Models\Paiement;
use App\Models\RendezVous;
use App\Models\Subscription;
use App\Models\User;
use App\Services\FileStorageService;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = $this->creerAdmin();
        $atelier = $this->creerAtelierValide($admin);
        $atelier2 = $this->creerAtelierEnAttente($admin);
        $atelierBloque = $this->creerAtelierBloque($admin);

        $this->creerDonneesAtelier($atelier);
    }

    private function creerAdmin(): User
    {
        return User::updateOrCreate(
            ['email' => 'admin@coutureflow.app'],
            [
                'name' => 'Administrateur',
                'phone' => ' 77 896 91 84.',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'status' => UserStatus::Valide,
                'status_updated_at' => now(),
                'validated_at' => now(),
            ]
        );
    }

    private function creerAtelierValide(User $admin): Atelier
    {
        $user = User::updateOrCreate(
            ['email' => 'atelier@coutureflow.app'],
            [
                'name' => 'Awa Diallo',
                'phone' => '77 123 45 67',
                'password' => Hash::make('password'),
                'role' => 'atelier',
                'status' => UserStatus::Valide,
                'status_updated_at' => now()->subDays(20),
                'validated_at' => now()->subDays(20),
                'validated_by' => $admin->id,
            ]
        );

        $atelier = Atelier::updateOrCreate(
            ['user_id' => $user->id],
            [
                'nom' => 'Atelier Couture Diallo',
                'telephone' => '77 123 45 67',
                'adresse' => 'Rue 12, Médina',
                'ville' => 'Dakar',
                'valid_from' => now()->subDays(20),
                'valid_until' => now()->addDays(10),
            ]
        );

        $this->creerAbonnement($user, $admin, UserStatus::Valide, now()->subDays(20));

        return $atelier;
    }

    private function creerAtelierEnAttente(User $admin): Atelier
    {
        $user = User::updateOrCreate(
            ['email' => 'en.attente@coutureflow.app'],
            [
                'name' => 'Fatou Ndiaye',
                'phone' => '78 555 44 33',
                'password' => Hash::make('password'),
                'role' => 'atelier',
                'status' => UserStatus::EnAttente,
                'status_updated_at' => now()->subHours(6),
            ]
        );

        $atelier = Atelier::updateOrCreate(
            ['user_id' => $user->id],
            [
                'nom' => 'Atelier Fatou Couture',
                'telephone' => '78 555 44 33',
                'adresse' => 'Cité Keur Gorgui',
                'ville' => 'Dakar',
            ]
        );

        $this->creerAbonnement($user, $admin, UserStatus::EnAttente, now()->subDays(1));

        return $atelier;
    }

    private function creerAtelierBloque(User $admin): Atelier
    {
        $user = User::updateOrCreate(
            ['email' => 'bloque@coutureflow.app'],
            [
                'name' => 'Marième Sow',
                'phone' => '76 999 88 77',
                'password' => Hash::make('password'),
                'role' => 'atelier',
                'status' => UserStatus::Bloque,
                'status_reason' => 'Abonnement mensuel impayé depuis deux mois. Merci de régulariser.',
                'status_updated_at' => now()->subDays(12),
            ]
        );

        $atelier = Atelier::updateOrCreate(
            ['user_id' => $user->id],
            [
                'nom' => 'Atelier Marième',
                'telephone' => '76 999 88 77',
                'ville' => 'Thiès',
                'valid_from' => now()->subMonths(3),
                'valid_until' => now()->subDays(12),
            ]
        );

        $this->creerAbonnement($user, $admin, UserStatus::Bloque, now()->subDays(12));

        return $atelier;
    }

    private function creerAbonnement(User $user, User $admin, UserStatus $statut, string $datePaiement): Subscription
    {
        $disk = config('coutureflow.proof.disk');
        $directory = config('coutureflow.proof.directory').'/'.$user->id;
        Storage::disk($disk)->makeDirectory($directory);

        $fichier = UploadedFile::fake()->image('recu-wave.jpg', 900, 1600);

        $stored = app(FileStorageService::class)->storePrivate($fichier, $directory, $disk);

        return Subscription::updateOrCreate(
            ['user_id' => $user->id],
            [
                'libelle' => config('coutureflow.subscription.label'),
                'montant' => config('coutureflow.subscription.amount'),
                'duree_mois' => 1,
                'statut' => $statut,
                'date_paiement' => $datePaiement,
                'wave_number_used' => '77 123 45 67',
                'wave_reference' => 'WM-'.str_pad((string) $user->id, 8, '0', STR_PAD_LEFT),
                'proof_disk' => $stored['disk'],
                'proof_path' => $stored['path'],
                'proof_original_name' => 'recu-wave.jpg',
                'proof_mime' => $stored['mime'],
                'proof_size' => $stored['size'],
                'reason' => $statut === UserStatus::Bloque ? 'Abonnement impayé.' : null,
                'reviewed_at' => $statut === UserStatus::EnAttente ? null : now()->subDays(20),
                'reviewed_by' => $statut === UserStatus::EnAttente ? null : $admin->id,
                'valid_from' => $statut === UserStatus::EnAttente ? null : now()->subDays(20),
                'valid_until' => $statut === UserStatus::EnAttente ? null : now()->addDays(10),
            ]
        );
    }

    private function creerDonneesAtelier(Atelier $atelier): void
    {
        $user = $atelier->user;

        $clients = collect([
            ['nom' => 'Aminata Fall', 'telephone' => '77 111 22 33', 'email' => 'aminata.fall@exemple.com', 'adresse' => 'Dakar Plateau', 'notes' => 'Préfère les tissus immunoglobulinés. Taille 42.'],
            ['nom' => 'Cheikh Mbaye', 'telephone' => '77 444 55 66', 'email' => null, 'adresse' => 'Thiaroye', 'notes' => 'Costume 3 pièces pour mariage.'],
            ['nom' => 'Khady Ba', 'telephone' => '78 777 88 99', 'email' => 'khady.ba@exemple.com', 'adresse' => 'Mbour', 'notes' => null],
            ['nom' => 'Ousmane Diop', 'telephone' => '76 321 45 67', 'email' => null, 'adresse' => 'Saint-Louis', 'notes' => 'Client fidèle, toujours prêt à l\'avance.'],
            ['nom' => 'Ndeye Sarr', 'telephone' => '77 909 09 09', 'email' => 'ndeye.sarr@exemple.com', 'adresse' => 'Dakar Almadies', 'notes' => null],
        ])->map(fn (array $data) => Client::create([
            ...$data,
            'atelier_id' => $atelier->id,
            'is_active' => true,
        ]));

        $mesuresTypes = [
            'Tour de poitrine' => 92,
            'Tour de taille' => 74,
            'Tour de hanches' => 98,
            'Carrure' => 39,
            'Épaule' => 41,
            'Longueur manche' => 58,
            'Longueur vêtement' => 112,
            'Entrejambe' => 78,
        ];

        foreach ($clients as $index => $client) {
            foreach ($mesuresTypes as $libelle => $base) {
                Mesure::create([
                    'client_id' => $client->id,
                    'libelle' => $libelle,
                    'categorie' => config('coutureflow.mesures_courantes')[$libelle]['categorie'] ?? null,
                    'valeur' => $base + ($index % 3) * 2,
                    'unite' => 'cm',
                    'date_mesure' => now()->subDays(30 - $index * 5),
                    'created_by' => $user->id,
                ]);
            }
        }

        // Deux mesures historiques pour illustrer l'évolution
        Mesure::create([
            'client_id' => $clients[0]->id,
            'libelle' => 'Tour de taille',
            'categorie' => 'Haut du corps',
            'valeur' => 78,
            'unite' => 'cm',
            'date_mesure' => now()->subYear(),
            'commentaire' => 'Mesure de l\'an dernier',
            'created_by' => $user->id,
        ]);

        $modeles = collect([
            ['nom' => 'Robe wax wax', 'categorie' => ModeleCategorie::Femme, 'prix_indicatif' => 45000, 'description' => 'Robe longue en wax, coupe ajustée, fermeture éclair invisible.', 'tissu_conseille' => 'Wax / bogolan'],
            ['nom' => 'Chemise lin', 'categorie' => ModeleCategorie::Homme, 'prix_indicatif' => 25000, 'description' => 'Chemise manches longues en lin lavé.', 'tissu_conseille' => 'Lin lavé'],
            ['nom' => 'Ensemble bubu', 'categorie' => ModeleCategorie::Enfant, 'prix_indicatif' => 15000, 'description' => 'Ensemble deux pièces pour enfant, coton doux.', 'tissu_conseille' => 'Coton jersey'],
            ['nom' => 'Tunique brodée', 'categorie' => ModeleCategorie::Femme, 'prix_indicatif' => 60000, 'description' => 'Tunique avec broderie main au col et aux poignets.', 'tissu_conseille' => 'Pagne + mousseline'],
            ['nom' => 'Pantalon_tailleur', 'categorie' => ModeleCategorie::Homme, 'prix_indicatif' => 35000, 'description' => 'Pantalon de tailleur, taille haute, pinces marquées.', 'tissu_conseille' => 'CStretch'],
            ['nom' => 'Débardeur', 'categorie' => ModeleCategorie::Femme, 'prix_indicatif' => 12000, 'description' => 'Débardeur basic en coton côtelé.', 'tissu_conseille' => 'Coton côtelé'],
        ])->map(fn (array $data) => Modele::create([
            ...$data,
            'atelier_id' => $atelier->id,
            'is_active' => true,
        ]));

        $commandes = collect([
            ['client' => 0, 'modele' => 0, 'prix_total' => 45000, 'avance' => 20000, 'statut' => CommandeStatut::EnProduction, 'retard' => 4, 'jours' => 12],
            ['client' => 1, 'modele' => 4, 'prix_total' => 75000, 'avance' => 75000, 'statut' => CommandeStatut::Prete, 'retard' => 0, 'jours' => 2],
            ['client' => 2, 'modele' => 1, 'prix_total' => 25000, 'avance' => 10000, 'statut' => CommandeStatut::EnAttente, 'retard' => 0, 'jours' => 7],
            ['client' => 3, 'modele' => 2, 'prix_total' => 30000, 'avance' => 15000, 'statut' => CommandeStatut::EnProduction, 'retard' => 0, 'jours' => 15],
            ['client' => 4, 'modele' => 5, 'prix_total' => 12000, 'avance' => 12000, 'statut' => CommandeStatut::Livree, 'retard' => 0, 'jours' => -10],
            ['client' => 0, 'modele' => 3, 'prix_total' => 60000, 'avance' => 30000, 'statut' => CommandeStatut::EnAttente, 'retard' => 9, 'jours' => 20],
        ]);

        $sequence = 1;
        $commandesCreees = [];

        foreach ($commandes as $data) {
            $dateCommande = now()->subDays($data['jours'] + 5);
            $dateLivraison = $data['retard'] > 0
                ? now()->subDays($data['retard'])
                : now()->addDays($data['jours']);

            $commande = Commande::create([
                'atelier_id' => $atelier->id,
                'client_id' => $clients[$data['client']]->id,
                'modele_id' => $modeles[$data['modele']]?->id,
                'numero' => 'CMD-'.now()->year.'-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT),
                'type' => $data['statut'] === CommandeStatut::Prete ? 'Costume' : 'Confection',
                'tissu' => $data['modele'] === 0 ? 'Waxrella maroon' : 'Lin beige',
                'description' => 'Confection sur mesures selon fiche client.',
                'prix_total' => $data['prix_total'],
                'avance' => $data['avance'],
                'date_commande' => $dateCommande,
                'date_livraison_prevue' => $dateLivraison,
                'date_livraison_reelle' => $data['statut'] === CommandeStatut::Livree ? $dateLivraison : null,
                'statut' => $data['statut'],
                'created_by' => $user->id,
            ]);

            $commandesCreees[] = $commande;
            $sequence++;

            if ($data['avance'] > 0) {
                Paiement::create([
                    'atelier_id' => $atelier->id,
                    'commande_id' => $commande->id,
                    'client_id' => $clients[$data['client']]->id,
                    'type' => PaiementType::Avance,
                    'methode' => PaiementMethode::Especes,
                    'montant' => $data['avance'],
                    'description' => 'Avance à la commande',
                    'date_paiement' => $dateCommande,
                    'created_by' => $user->id,
                ]);
            }

            if ((float) $commande->solde === 0.0) {
                Paiement::create([
                    'atelier_id' => $atelier->id,
                    'commande_id' => $commande->id,
                    'client_id' => $clients[$data['client']]->id,
                    'type' => PaiementType::Solde,
                    'methode' => PaiementMethode::Wave,
                    'montant' => (float) $commande->prix_total - $data['avance'],
                    'description' => 'Solde encaissé à la livraison',
                    'date_paiement' => $dateCommande->copy()->addDays(2),
                    'reference' => 'WM-'.str_pad((string) $commande->id, 6, '0', STR_PAD_LEFT),
                    'created_by' => $user->id,
                ]);
            }
        }

        // Recettes diverses
        Paiement::create([
            'atelier_id' => $atelier->id,
            'type' => PaiementType::Recette,
            'methode' => PaiementMethode::Especes,
            'montant' => 15000,
            'description' => 'Retouches — commande express',
            'date_paiement' => now()->subDays(6),
            'created_by' => $user->id,
        ]);

        $depenses = [
            ['libelle' => 'Achat de wax (10 mètres)', 'categorie' => \App\Enums\DepenseCategorie::Tissu, 'montant' => 85000, 'jours' => 8],
            ['libelle' => 'Fil, aiguilles et fournitures', 'categorie' => \App\Enums\DepenseCategorie::Fournitures, 'montant' => 12500, 'jours' => 14],
            ['libelle' => 'Entretien machine à coudre', 'categorie' => \App\Enums\DepenseCategorie::Entretien, 'montant' => 20000, 'jours' => 21],
            ['libelle' => 'Transport — livraison', 'categorie' => \App\Enums\DepenseCategorie::Transport, 'montant' => 5000, 'jours' => 3],
            ['libelle' => 'Publicité Instagram', 'categorie' => \App\Enums\DepenseCategorie::Publicite, 'montant' => 30000, 'jours' => 5],
            ['libelle' => 'Aideouns salon', 'categorie' => \App\Enums\DepenseCategorie::Salaires, 'montant' => 150000, 'jours' => 7],
        ];

        foreach ($depenses as $depense) {
            Depense::create([
                'atelier_id' => $atelier->id,
                'libelle' => $depense['libelle'],
                'categorie' => $depense['categorie'],
                'montant' => $depense['montant'],
                'date_depense' => now()->subDays($depense['jours']),
                'created_by' => $user->id,
            ]);
        }

        // Mois précédent pour l'évolution financière
        Depense::create([
            'atelier_id' => $atelier->id,
            'libelle' => 'Loyer mensuel',
            'categorie' => \App\Enums\DepenseCategorie::Loyer,
            'montant' => 120000,
            'date_depense' => now()->subMonth()->startOfMonth(),
            'created_by' => $user->id,
        ]);

        $rendezVous = [
            ['titre' => 'Essayage robe wax', 'type' => RendezVousType::Essayage, 'client' => 0, 'commande' => 0, 'jours' => 1, 'heure' => '10:00'],
            ['titre' => 'Livraison costume', 'type' => RendezVousType::Livraison, 'client' => 1, 'commande' => 1, 'jours' => 2, 'heure' => '16:30'],
            ['titre' => 'Prise de mesures', 'type' => RendezVousType::RendezVous, 'client' => 4, 'commande' => null, 'jours' => 3, 'heure' => '09:00'],
            ['titre' => 'Essai bubu enfant', 'type' => RendezVousType::Essayage, 'client' => 2, 'commande' => 2, 'jours' => 5, 'heure' => '11:00'],
            ['titre' => 'Réunion fournisseurs', 'type' => RendezVousType::Important, 'client' => null, 'commande' => null, 'jours' => 6, 'heure' => '14:00'],
            ['titre' => 'Livraison débardeur', 'type' => RendezVousType::Livraison, 'client' => 3, 'commande' => 4, 'jours' => -2, 'heure' => '15:00'],
        ];

        foreach ($rendezVous as $rdv) {
            RendezVous::create([
                'atelier_id' => $atelier->id,
                'client_id' => $rdv['client'] !== null ? $clients[$rdv['client']]->id : null,
                'commande_id' => $rdv['commande'] !== null ? $commandesCreees[$rdv['commande']]->id : null,
                'titre' => $rdv['titre'],
                'type' => $rdv['type'],
                'date_debut' => today()->addDays($rdv['jours']),
                'heure_debut' => $rdv['heure'],
                'lieu' => 'Atelier',
                'created_by' => $user->id,
            ]);
        }

        $this->command?->newLine();
        $this->command?->info('Données de démonstration créées.');
        $this->command?->table(
            ['Rôle', 'Email', 'Mot de passe'],
            [
                ['Administrateur', 'admin@coutureflow.app', 'password'],
                ['Atelier validé', 'atelier@coutureflow.app', 'password'],
                ['Atelier en attente', 'en.attente@coutureflow.app', 'password'],
                ['Atelier bloqué', 'bloque@coutureflow.app', 'password'],
            ]
        );
    }
}
