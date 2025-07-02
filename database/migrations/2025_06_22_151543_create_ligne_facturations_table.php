<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ligne_facturations', function (Blueprint $table) {
            $table->id('id_ligne_facturation');
            $table->foreignId('id_facture')->constrained('factures', 'id_facture')->onDelete('cascade');
            $table->string('libelle');
            $table->decimal('prix_unitaire', 10, 2);
            $table->integer('quantite')->default(1);
            $table->timestamps();

            // Index pour optimiser les requêtes
            $table->index(['id_facture']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ligne_facturations');
    }
};