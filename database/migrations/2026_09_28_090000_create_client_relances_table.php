<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Historique des relances faites depuis l'application.
 *
 * La page « Relances » liste les clients que l'atelier peut rappeler :
 * mesures relevées sans commande, pièce prête non retirée, client inactif.
 * Sans trace, un client déjà rappelé réapparaîtrait à chaque visite et la
 * page deviendrait vite un bruit que l'on ignore — c'est-à-dire inutilisable.
 *
 * Une relance n'est ni un message ni un paiement : l'application ne gère
 * aucune transaction entre l'atelier et ses clients, elle se contente de
 * indiquer à l'atelier qui appeler.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_relances', function (Blueprint $table) {
            $table->id();

            // L'atelier reste porté par la relation : un client supprimé ne
            // doit pas emporter son historique de relance, d'où l'absence de
            // cascade et l'index explicite pour retrouver les lignes ensuite.
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('atelier_id')->constrained('ateliers')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // Quel motif a déclenché la relance, pour comprendre d'où vient
            // la suggestion au moment où l'atelier l'a traitée.
            $table->string('motif', 40)->default('manuel');
            $table->text('note')->nullable();

            $table->timestamps();

            // La liste interroge en permanence « les clients relancés depuis
            // telle date » : cet index porte la requête.
            $table->index(['atelier_id', 'created_at'], 'client_relances_atelier_created_index');
            $table->index(['client_id', 'created_at'], 'client_relances_client_created_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_relances');
    }
};
