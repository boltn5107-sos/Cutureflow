<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rendez_vous', function (Blueprint $table) {
            $table->id();
            $table->foreignId('atelier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('commande_id')->nullable()->constrained()->nullOnDelete();
            $table->string('titre');
            $table->enum('type', ['rendez_vous', 'essayage', 'livraison', 'important'])
                ->default('rendez_vous')
                ->index();
            $table->date('date_debut')->index();
            $table->time('heure_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->time('heure_fin')->nullable();
            $table->string('lieu')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('rappel_effectue')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['atelier_id', 'date_debut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rendez_vous');
    }
};
