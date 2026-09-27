<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paiements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('atelier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('commande_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['avance', 'solde', 'recette', 'remboursement'])
                ->default('avance')
                ->index();
            $table->enum('methode', ['especes', 'wave', 'orange_money', 'virement', 'autre'])
                ->default('especes')
                ->index();
            $table->decimal('montant', 12, 2);
            $table->text('description')->nullable();
            $table->date('date_paiement');
            $table->string('reference', 80)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['atelier_id', 'date_paiement']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paiements');
    }
};
