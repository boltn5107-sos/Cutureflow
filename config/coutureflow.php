<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Identité de l'application
    |--------------------------------------------------------------------------
    */

    'name' => env('APP_NAME', 'Couture+'),

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
    | Indicatif téléphonique du pays
    |--------------------------------------------------------------------------
    |
    | Les numéros sont saisis en format local (« 77 000 00 00 »). Les liens
    | de partage, qui ouvrent une application tierce, exigent le format
    | international : cet indicatif est donc ajouté une fois pour toutes,
    | plutôt qu'à chaque partage.
    |
    */

    'indicatif_pays' => env('PAYS_INDICATIF', '221'),

    /*
    |--------------------------------------------------------------------------
    | Relances
    |--------------------------------------------------------------------------
    | Les suggestions de relance existent mais restent masquées le temps que
    | le tri soit jugé pertinent : le service, la page et les tests sont en
    | place, seul l'accès disparaît de la navigation et du tableau de bord.
    | Passer RELANCES_ACTIVES à true les réactive.
    */
    'relances_actives' => env('RELANCES_ACTIVES', false),

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
        /*
         * Les icônes sont choisies dans Font Awesome 7 Free (solid) et
         * vérifiées une à une dans node_modules : une icône absente du CSS
         * s'afficherait en carré vide, silencieusement.
         *
         * Aucun jeu d'icônes ne dessine un corps humain ou un mètre ruban :
         * il faut donc jouer sur des silhouettes de vêtements et des
         * symboles de mesure, chaque famille devant figurer ce qu'elle
         * représente réellement, sans laisser croire à une précision
         * qu'elle n'a pas (fa-ruler-vertical ne figure pas le haut du corps).
         *
         * Plusieurs mesures partagent une même icône (poitrine et hanches,
         * personnage pour coude/cuisse/genou…) : le libellé les distingue.
         *
         * Les mesures encore retirées du catalogue (carrure, chemise, veste,
         * jupe) gardent leurs relevés existants : ceux-ci s'affichent encore
         * sur la fiche du client avec une icône de repli.
         */
        'Haut du corps' => [
            ['code' => 'hauteur_corps', 'libelle' => 'Haut du corps', 'icone' => 'fa-ruler-combined'],
            ['code' => 'tour_poitrine', 'libelle' => 'Poitrine', 'icone' => 'fa-person-dress'],
            ['code' => 'tour_taille', 'libelle' => 'Taille', 'icone' => 'fa-ruler-horizontal'],
            ['code' => 'tour_hanches', 'libelle' => 'Hanches', 'icone' => 'fa-person-dress'],
            ['code' => 'largeur_epaules', 'libelle' => 'Épaules', 'icone' => 'fa-arrows-left-right'],
            ['code' => 'tour_bras', 'libelle' => 'Bras', 'icone' => 'fa-child-reaching'],
            ['code' => 'longueur_manche', 'libelle' => 'Manche', 'icone' => 'fa-ruler-vertical'],
            ['code' => 'tour_cou', 'libelle' => 'Cou', 'icone' => 'fa-circle-user'],
            ['code' => 'tour_poignet', 'libelle' => 'Poignet', 'icone' => 'fa-hand'],
            ['code' => 'tour_coude', 'libelle' => 'Coude', 'icone' => 'fa-person'],
            ['code' => 'longueur_epaule', 'libelle' => 'Longueur épaule', 'icone' => 'fa-ruler-horizontal'],
        ],
        'Bas du corps' => [
            ['code' => 'fourche_devant', 'libelle' => 'Fourche devant', 'icone' => 'fa-angle-down'],
            ['code' => 'fourche_dos', 'libelle' => 'Fourche dos', 'icone' => 'fa-angle-up'],
            ['code' => 'tour_cuisse', 'libelle' => 'Cuisse', 'icone' => 'fa-person'],
            ['code' => 'tour_genou', 'libelle' => 'Genou', 'icone' => 'fa-person'],
            ['code' => 'tour_mollet', 'libelle' => 'Mollet', 'icone' => 'fa-person-walking'],
            ['code' => 'tour_cheville', 'libelle' => 'Cheville', 'icone' => 'fa-shoe-prints'],
            ['code' => 'entrejambe', 'libelle' => 'Entrejambe', 'icone' => 'fa-arrows-up-down'],
            ['code' => 'longueur_pantalon', 'libelle' => 'Longueur pantalon', 'icone' => 'fa-ruler-vertical'],
            ['code' => 'longueur_robe', 'libelle' => 'Longueur robe', 'icone' => 'fa-ruler-vertical'],
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
