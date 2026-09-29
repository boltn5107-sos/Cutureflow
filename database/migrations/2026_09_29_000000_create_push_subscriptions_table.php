<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('endpoint');
            $table->string('keys_p256dh')->nullable();
            $table->string('keys_auth')->nullable();
            $table->timestamps();

            // Une même extrémité (endpoint) ne doit être enregistrée qu'une
            // seule fois, quel que soit le navigateur qui la rejoue.
            $table->unique('endpoint');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
    }
};
