<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\StatutDevis;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devis', function (Blueprint $table) {
            $table->id('id_devis');
            $table->foreignId('id_projet')->constrained('projets', 'id_projet');
            $table->enum('statut', ['brouillon', 'envoye', 'accepte', 'refuse', 'expire'])->default('brouillon');
            $table->text('note')->nullable();
            $table->timestamps();

            // Index pour optimiser les requêtes
            $table->index(['id_projet', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devis');
    }
};