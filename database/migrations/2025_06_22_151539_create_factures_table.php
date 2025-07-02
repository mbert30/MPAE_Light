<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\StatutFacture;
use App\Enums\TypePaiement;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('factures', function (Blueprint $table) {
            $table->id('id_facture');
            $table->foreignId('id_devis')->constrained('devis', 'id_devis');
            $table->enum('etat_facture', ['brouillon', 'envoyee', 'payee', 'en_retard', 'annulee'])->default('brouillon');
            $table->date('date_edition');
            $table->date('date_paiement_limite')->nullable();
            $table->enum('type_paiement', ['virement', 'cheque', 'especes', 'carte', 'paypal', 'autre'])->nullable();
            $table->date('date_paiement_effectif')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            // Index pour optimiser les requêtes
            $table->index(['id_devis', 'etat_facture']);
            $table->index(['date_paiement_limite']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('factures');
    }
};