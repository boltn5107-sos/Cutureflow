<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('atelier_id')->constrained()->cascadeOnDelete();
            $table->string('nom');
            $table->string('telephone', 40);
            $table->string('email')->nullable();
            $table->string('adresse')->nullable();
            $table->text('notes')->nullable();
            $table->string('photo_disk', 30)->default('local');
            $table->string('photo_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['atelier_id', 'nom']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
