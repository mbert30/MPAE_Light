<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\StatutProjet;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projets', function (Blueprint $table) {
            $table->id('id_projet');
            $table->foreignId('id_client')->constrained('clients', 'id_client');
            $table->string('designation');
            $table->enum('statut', ['en_attente', 'en_cours', 'termine', 'annule', 'en_pause'])->default('en_attente');
            $table->timestamps();

            // Index pour optimiser les requêtes
            $table->index(['id_client', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projets');
    }
};