<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modeles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('atelier_id')->constrained()->cascadeOnDelete();
            $table->string('nom');
            $table->text('description')->nullable();
            $table->enum('categorie', ['homme', 'femme', 'enfant', 'autre'])->default('femme')->index();
            $table->decimal('prix_indicatif', 12, 2)->nullable();
            $table->string('tissu_conseille')->nullable();
            $table->string('duree_estimate')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['atelier_id', 'nom']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modeles');
    }
};
