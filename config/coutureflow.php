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
    | Mesures courantes proposées dans l'interface
    |--------------------------------------------------------------------------
    */

    /*
     | Les quatre lignes affichées à l'ouverture du formulaire de mesures.
     | Le tailleur peut les renommer librement ou en ajouter d'autres :
     | ces intitulés ne sont que des valeurs de départ.
     */
    'mesures_par_defaut' => [
        'Tour de poitrine',
        'Tour de taille',
        'Tour de hanches',
        'Longueur totale',
    ],

    'mesures_courantes' => [
        'Tour de poitrine' => ['categorie' => 'Haut du corps'],
        'Tour de taille' => ['categorie' => 'Haut du corps'],
        'Tour de hanches' => ['categorie' => 'Haut du corps'],
        'Tour de poitrine (buste)' => ['categorie' => 'Haut du corps'],
        'Carrure' => ['categorie' => 'Haut du corps'],
        'Épaule' => ['categorie' => 'Haut du corps'],
        'Cou' => ['categorie' => 'Haut du corps'],
        'Poignet' => ['categorie' => 'Haut du corps'],
        'Longueur manche' => ['categorie' => 'Manches'],
        'Tour de bras' => ['categorie' => 'Manches'],
        'Longueur vêtement' => ['categorie' => 'Longueurs'],
        'Longueur dos' => ['categorie' => 'Longueurs'],
        'Longueur totale' => ['categorie' => 'Longueurs'],
        'Entrejambe' => ['categorie' => 'Bas'],
        'Tour de cuisse' => ['categorie' => 'Bas'],
        'Tour de genou' => ['categorie' => 'Bas'],
        'Tour de cheville' => ['categorie' => 'Bas'],
        'Longueur jupe' => ['categorie' => 'Jupes & robes'],
        'Longueur robe' => ['categorie' => 'Jupes & robes'],
        'Entre-jambes' => ['categorie' => 'Jupes & robes'],
    ],

    'unites_mesure' => [
        'cm' => 'Centimètres (cm)',
        'mm' => 'Millimètres (mm)',
        'm' => 'Mètres (m)',
        'pouce' => 'Pouces (in)',
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
