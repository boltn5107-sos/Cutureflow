<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Identité de l'application
    |--------------------------------------------------------------------------
    */

    'name' => env('APP_NAME', 'Couture Flow'),

    'currency' => env('APP_CURRENCY', 'FCFA'),

    'locale' => env('APP_LOCALE', 'fr'),

    /*
    |--------------------------------------------------------------------------
    | Paiement Wave (manuel — V1)
    |--------------------------------------------------------------------------
    |
    | Aucun appel à l'API Wave Business n'est effectué en V1. Le numéro ci-dessous
    | est simplement affiché à l'utilisateur pour qu'il effectue le virement
    | depuis son application Wave, puis dépose une capture du reçu.
    |
    */

    'wave' => [
        'number' => env('WAVE_PAYMENT_NUMBER', '221 77 000 00 00'),
        'name' => env('WAVE_PAYMENT_NAME', 'COUTURE FLOW'),
        'country' => env('WAVE_COUNTRY', 'SN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Abonnement atelier
    |--------------------------------------------------------------------------
    */

    'subscription' => [
        'amount' => (float) env('SUBSCRIPTION_AMOUNT', 5000),
        'months' => (int) env('SUBSCRIPTION_MONTHS', 1),
        'label' => env('SUBSCRIPTION_LABEL', 'Abonnement mensuel'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Preuves de paiement
    |--------------------------------------------------------------------------
    |
    | Les fichiers sont stockés sur un disque privé (hors de public/) et
    | servis uniquement via un controller autorisé. Aucun accès direct
    | par URL n'est possible.
    |
    */

    'proof' => [
        'disk' => env('PAYMENT_PROOF_DISK', 'local'),
        'directory' => 'payment-proofs',
        'max_kb' => (int) env('PAYMENT_PROOF_MAX_KB', 4096),
        'mimes' => ['jpg', 'jpeg', 'png', 'webp', 'pdf'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Médias atelier (photos clients, photos modèles)
    |--------------------------------------------------------------------------
    */

    'media' => [
        'disk' => env('MEDIA_DISK', 'local'),
        'client_directory' => 'clients',
        'model_directory' => 'modeles',
        'max_kb' => (int) env('MEDIA_MAX_KB', 4096),
        'mimes' => ['jpg', 'jpeg', 'png', 'webp'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Catalogue des mesures
    |--------------------------------------------------------------------------
    |
    | Source unique de la palette d'icônes. Chaque entrée porte un code stable
    | (colonne « mesures.code »), l'intitulé affiché et l'icône associée.
    |
    | Aucune mesure n'est obligatoire : le tailleur clique sur les icônes dont
    | le vêtement a besoin, et seules les mesures choisies sont enregistrées.
    | Le code sert aussi à retrouver l'icône d'une mesure déjà enregistrée,
    | dans le tableau des relevés et sur la fiche du client.
    |
    | Pour ajouter une mesure, ajoutez une ligne dans la catégorie voulue :
    | la palette de saisie, le sélecteur de modification et les icônes
    | affichées la voient automatiquement, sans code supplémentaire.
    */
    'catalogue_mesures' => [
        'Haut du corps' => [
            ['code' => 'hauteur_corps', 'libelle' => 'Haut du corps', 'icone' => 'fa-ruler-vertical'],
            ['code' => 'tour_poitrine', 'libelle' => 'Tour de poitrine', 'icone' => 'fa-shirt'],
            ['code' => 'tour_taille', 'libelle' => 'Tour de taille', 'icone' => 'fa-ellipsis'],
            ['code' => 'tour_hanches', 'libelle' => 'Tour de hanches', 'icone' => 'fa-ellipsis-vertical'],
            ['code' => 'carrure', 'libelle' => 'Carrure', 'icone' => 'fa-arrows-left-right'],
            ['code' => 'largeur_epaules', 'libelle' => 'Largeur épaules', 'icone' => 'fa-expand'],
            ['code' => 'tour_cou', 'libelle' => 'Tour de cou', 'icone' => 'fa-mitten'],
            ['code' => 'longueur_epaule', 'libelle' => 'Longueur épaule', 'icone' => 'fa-ruler-horizontal'],
            ['code' => 'longueur_manche', 'libelle' => 'Longueur manche', 'icone' => 'fa-vest'],
            ['code' => 'tour_bras', 'libelle' => 'Tour de bras', 'icone' => 'fa-hand-fist'],
            ['code' => 'tour_poignet', 'libelle' => 'Tour de poignet', 'icone' => 'fa-hand'],
        ],
        'Longueurs' => [
            ['code' => 'longueur_chemise', 'libelle' => 'Longueur chemise', 'icone' => 'fa-tshirt'],
            ['code' => 'longueur_veste', 'libelle' => 'Longueur veste', 'icone' => 'fa-user-tie'],
            ['code' => 'longueur_robe', 'libelle' => 'Longueur robe', 'icone' => 'fa-person-dress'],
            ['code' => 'longueur_jupe', 'libelle' => 'Longueur jupe', 'icone' => 'fa-caret-down'],
            ['code' => 'longueur_pantalon', 'libelle' => 'Longueur pantalon', 'icone' => 'fa-ruler-combined'],
            ['code' => 'entrejambe', 'libelle' => 'Entrejambe', 'icone' => 'fa-arrows-up-down'],
        ],
        'Bas du corps' => [
            ['code' => 'tour_cuisse', 'libelle' => 'Tour de cuisse', 'icone' => 'fa-person'],
            ['code' => 'tour_genou', 'libelle' => 'Tour de genou', 'icone' => 'fa-gauge'],
            ['code' => 'tour_mollet', 'libelle' => 'Tour de mollet', 'icone' => 'fa-shoe-prints'],
            ['code' => 'tour_cheville', 'libelle' => 'Tour de cheville', 'icone' => 'fa-socks'],
            ['code' => 'fourche_devant', 'libelle' => 'Fourche devant', 'icone' => 'fa-angle-down'],
            ['code' => 'fourche_dos', 'libelle' => 'Fourche dos', 'icone' => 'fa-angle-up'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Unités de mesure
    |--------------------------------------------------------------------------
    */
    'unite_mesure_par_defaut' => 'cm',

    'unites_mesure' => [
        'cm' => 'Centimètres (cm)',
        'mm' => 'Millimètres (mm)',
        'm' => 'Mètres (m)',
        'in' => 'Pouces (in)',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rappels de commandes
    |--------------------------------------------------------------------------
    */

    'reminders' => [
        'late_after_days' => (int) env('REMINDER_LATE_DAYS', 0),
        'due_soon_days' => (int) env('REMINDER_DUE_SOON_DAYS', 2),
    ],

];
