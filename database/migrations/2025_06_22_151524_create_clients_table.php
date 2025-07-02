<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id('id_client');
            $table->foreignId('id_utilisateur')->constrained('users', 'id');
            $table->string('designation');
            $table->foreignId('id_adresse')->constrained('adresses', 'id_adresse');
            $table->string('email');
            $table->string('telephone')->nullable();
            $table->timestamps();

            // Index pour optimiser les requêtes
            $table->index(['id_utilisateur']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};