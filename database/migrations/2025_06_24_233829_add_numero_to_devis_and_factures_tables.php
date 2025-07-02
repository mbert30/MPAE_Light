<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('devis', function (Blueprint $table) {
            $table->integer('numero_devis')->after('id_devis');
            
            // Index pour optimiser les requêtes de recherche du dernier numéro par utilisateur
            $table->index(['id_projet', 'numero_devis']);
        });

        Schema::table('factures', function (Blueprint $table) {
            $table->integer('numero_facture')->after('id_facture');
            
            // Index pour optimiser les requêtes de recherche du dernier numéro par utilisateur
            $table->index(['id_devis', 'numero_facture']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devis', function (Blueprint $table) {
            $table->dropIndex(['id_projet', 'numero_devis']);
            $table->dropColumn('numero_devis');
        });

        Schema::table('factures', function (Blueprint $table) {
            $table->dropIndex(['id_devis', 'numero_facture']);
            $table->dropColumn('numero_facture');
        });
    }
};