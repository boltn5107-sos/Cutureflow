<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mesure extends Model
{
    use HasFactory;

    protected $table = 'mesures';

    protected $fillable = [
        'client_id',
        'code',
        'libelle',
        'categorie',
        'valeur',
        'unite',
        'date_mesure',
        'commentaire',
        'commande_id',
        'created_by',
    ];

    /**
     * Icône de repli : une mesure saisie avant le catalogue, ou dont
     * l'intitulé ne correspond à aucune entrée, reste affichable.
     */
    public const ICONE_REPLI = 'fa-ruler-combined';

    /**
     * Catalogue complet, groupé par zone du corps, tel que défini dans
     * config/coutureflow.php.
     *
     * @return array<string, array<int, array{code: string, libelle: string, icone: string}>>
     */
    public static function catalogue(): array
    {
        return (array) config('coutureflow.catalogue_mesures', []);
    }

    /**
     * Même catalogue, à plat et indexé par code : la forme pratique pour
     * résoudre une mesure déjà enregistrée.
     *
     * @return array<string, array{code: string, libelle: string, icone: string, categorie: string}>
     */
    public static function catalogueParCode(): array
    {
        static $cache = null;

        if ($cache !== null) {
            return $cache;
        }

        $cache = [];

        foreach (self::catalogue() as $categorie => $entrees) {
            foreach ($entrees as $entree) {
                $cache[$entree['code']] = $entree + ['categorie' => $categorie];
            }
        }

        return $cache;
    }

    /**
     * Entrée du catalogue correspondant à un code, ou null.
     *
     * @return array{code: string, libelle: string, icone: string, categorie: string}|null
     */
    public static function entreeParCode(?string $code): ?array
    {
        if ($code === null || $code === '') {
            return null;
        }

        return self::catalogueParCode()[$code] ?? null;
    }

    /**
     * Entrée du catalogue correspondant à un intitulé, sans tenir compte de la
     * casse ni des espaces superflus. Sert à rattacher les mesures anciennes,
     * créées avant l'existence des codes.
     *
     * @return array{code: string, libelle: string, icone: string, categorie: string}|null
     */
    public static function entreeParLibelle(?string $libelle): ?array
    {
        if ($libelle === null || trim($libelle) === '') {
            return null;
        }

        $recherche = mb_strtolower(trim($libelle));

        foreach (self::catalogueParCode() as $entree) {
            if (mb_strtolower($entree['libelle']) === $recherche) {
                return $entree;
            }
        }

        return null;
    }

    /**
     * Icône à afficher pour cette mesure : celle du catalogue si elle est
     * connue, celle de l'entrée retrouvée par l'intitulé à défaut, sinon
     * l'icône générique.
     */
    public function icone(): string
    {
        return self::entreeParCode($this->code)['icone']
            ?? self::entreeParLibelle($this->libelle)['icone']
            ?? self::ICONE_REPLI;
    }

    /**
     * Zone du corps associée à cette mesure, déduite du catalogue.
     */
    public function categorieCatalogue(): ?string
    {
        return self::entreeParCode($this->code)['categorie']
            ?? self::entreeParLibelle($this->libelle)['categorie']
            ?? null;
    }

    protected function casts(): array
    {
        return [
            'valeur' => 'decimal:2',
            'date_mesure' => 'date',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function valeurFormatee(): string
    {
        $valeur = rtrim(rtrim((string) $this->valeur, '0'), '.');

        return $valeur.' '.$this->unite;
    }
}
