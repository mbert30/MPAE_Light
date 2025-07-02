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
        Schema::table('ligne_devis', function (Blueprint $table) {
            $table->integer('ordre')->default(0)->after('quantite');
            $table->index(['id_devis', 'ordre']);
        });

        Schema::table('ligne_facturations', function (Blueprint $table) {
            $table->integer('ordre')->default(0)->after('quantite');
            $table->index(['id_facture', 'ordre']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ligne_devis', function (Blueprint $table) {
            $table->dropIndex(['id_devis', 'ordre']);
            $table->dropColumn('ordre');
        });

        Schema::table('ligne_facturations', function (Blueprint $table) {
            $table->dropIndex(['id_facture', 'ordre']);
            $table->dropColumn('ordre');
        });
    }
};