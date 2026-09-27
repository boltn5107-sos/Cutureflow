<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mesures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('libelle', 120);
            $table->string('categorie', 60)->nullable();
            $table->decimal('valeur', 8, 2);
            $table->string('unite', 12)->default('cm');
            $table->date('date_mesure');
            $table->text('commentaire')->nullable();
            $table->foreignId('commande_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['client_id', 'libelle']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mesures');
    }
};
