<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute le code de catalogue aux mesures.
 *
 * Le formulaire ne propose plus des lignes libres : le tailleur clique sur
 * une icône et la mesure est créée avec son code stable. Ce code permet de
 * retrouver l'icône et la catégorie plus tard, même si l'intitulé est
 * retouché lors d'une édition.
 *
 * La colonne reste nullable : les mesures saisies avant cette migration
 * n'ont pas de code, et une mesure dont l'intitulé ne correspond à aucune
 * entrée du catalogue non plus.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mesures', function (Blueprint $table) {
            $table->string('code', 60)->nullable();
        });

        // Un index non unique : un client peut légitimement avoir deux
        // relevés du même code à des dates différentes.
        Schema::table('mesures', function (Blueprint $table) {
            $table->index(['client_id', 'code'], 'mesures_client_code_index');
        });

        $this->backfill();
    }

    public function down(): void
    {
        Schema::table('mesures', function (Blueprint $table) {
            $table->dropIndex('mesures_client_code_index');
            $table->dropColumn('code');
        });
    }

    /**
     * Rattache les mesures existantes à une entrée du catalogue, en se basant
     * sur l'intitulé. Les mesures antérieures utilisaient des catégories
     * comme « Manches » ou « Bas » qui ont depuis été renommées.
     */
    private function backfill(): void
    {
        if (! Schema::hasTable('mesures')) {
            return;
        }

        $parLibelle = [];

        foreach ((array) config('coutureflow.catalogue_mesures', []) as $entrees) {
            foreach ($entrees as $entree) {
                $parLibelle[mb_strtolower(trim($entree['libelle']))] = $entree['code'];
            }
        }

        if ($parLibelle === []) {
            return;
        }

        $rattachement = DB::table('mesures')
            ->select('id', 'libelle')
            ->whereNull('code')
            ->get();

        foreach ($rattachement as $mesure) {
            $code = $parLibelle[mb_strtolower(trim((string) $mesure->libelle))] ?? null;

            if ($code !== null) {
                DB::table('mesures')->where('id', $mesure->id)->update(['code' => $code]);
            }
        }
    }
};
