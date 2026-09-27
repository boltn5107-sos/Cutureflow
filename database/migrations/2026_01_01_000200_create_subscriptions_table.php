<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('libelle');
            $table->decimal('montant', 12, 2);
            $table->unsignedSmallInteger('duree_mois')->default(1);
            $table->enum('statut', ['en_attente', 'valide', 'rejete', 'bloque'])->default('en_attente')->index();
            $table->date('date_paiement');
            $table->string('wave_number_used', 40)->nullable();
            $table->string('wave_reference', 80)->nullable();
            $table->string('proof_disk', 30)->default('local');
            $table->string('proof_path')->nullable();
            $table->string('proof_original_name')->nullable();
            $table->string('proof_mime', 80)->nullable();
            $table->unsignedBigInteger('proof_size')->nullable();
            $table->text('reason')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
