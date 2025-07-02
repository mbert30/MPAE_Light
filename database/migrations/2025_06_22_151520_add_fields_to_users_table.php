<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Ajouter les champs manquants pour utilisateur
            $table->string('prenom')->after('name');
            $table->date('date_naissance')->nullable()->after('email');
            $table->foreignId('id_adresse')->nullable()->constrained('adresses', 'id_adresse')->after('date_naissance');
            $table->string('telephone')->nullable()->after('id_adresse');
            $table->decimal('chiffre_affaire', 15, 2)->default(0)->after('telephone');
            $table->decimal('taux_charge', 5, 2)->default(0)->after('chiffre_affaire');
            $table->string('mdp')->after('password'); // Champ supplémentaire si nécessaire
            $table->boolean('est_admin')->default(false)->after('mdp');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['id_adresse']);
            $table->dropColumn([
                'prenom',
                'date_naissance', 
                'id_adresse',
                'telephone',
                'chiffre_affaire',
                'taux_charge',
                'mdp',
                'est_admin'
            ]);
        });
    }
};