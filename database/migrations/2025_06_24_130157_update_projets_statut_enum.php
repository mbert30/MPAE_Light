<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modifier l'ENUM pour la colonne statut
        DB::statement("ALTER TABLE projets MODIFY COLUMN statut ENUM('prospect', 'devis_envoye', 'devis_accepte', 'demarre', 'termine', 'annule') NOT NULL DEFAULT 'prospect'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remettre l'ancien ENUM
        DB::statement("ALTER TABLE projets MODIFY COLUMN statut ENUM('en_attente', 'en_cours', 'termine', 'annule', 'en_pause') NOT NULL DEFAULT 'en_attente'");
    }
};