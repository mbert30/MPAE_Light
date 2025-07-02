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
        Schema::table('factures', function (Blueprint $table) {
            // Supprimer les états non voulus du cahier des charges
            $table->dropColumn('etat_facture');
        });
        
        Schema::table('factures', function (Blueprint $table) {
            // Ajouter le bon enum selon le cahier des charges
            $table->enum('etat_facture', ['brouillon', 'envoyee', 'payee'])->default('brouillon')->after('id_devis');
            
            // Ajouter le taux de TVA hérité du devis (non modifiable)
            $table->decimal('taux_tva', 5, 2)->after('etat_facture')->comment('Taux de TVA hérité du devis');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('factures', function (Blueprint $table) {
            $table->dropColumn(['etat_facture', 'taux_tva']);
        });
        
        Schema::table('factures', function (Blueprint $table) {
            $table->enum('etat_facture', ['brouillon','envoyee','payee','en_retard','annulee'])->default('brouillon');
        });
    }
};