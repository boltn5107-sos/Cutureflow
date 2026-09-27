<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commandes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('atelier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('modele_id')->nullable()->constrained()->nullOnDelete();
            $table->string('numero', 40);
            $table->string('type', 40)->nullable();
            $table->string('tissu')->nullable();
            $table->text('description')->nullable();
            $table->decimal('prix_total', 12, 2)->default(0);
            $table->decimal('avance', 12, 2)->default(0);
            $table->decimal('solde', 12, 2)->default(0);
            $table->date('date_commande');
            $table->date('date_livraison_prevue')->nullable();
            $table->date('date_livraison_reelle')->nullable();
            $table->enum('statut', ['en_attente', 'en_production', 'prete', 'livree', 'annulee'])
                ->default('en_attente')
                ->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['atelier_id', 'numero']);
            $table->index(['atelier_id', 'statut']);
            $table->index(['atelier_id', 'date_livraison_prevue']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commandes');
    }
};
