<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('depenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('atelier_id')->constrained()->cascadeOnDelete();
            $table->string('libelle');
            $table->string('categorie', 60)->default('autre')->index();
            $table->decimal('montant', 12, 2);
            $table->date('date_depense');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['atelier_id', 'date_depense']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('depenses');
    }
};
