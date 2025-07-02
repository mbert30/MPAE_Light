<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Adresse;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Créer une adresse par défaut
        $adresse = Adresse::create([
            'ligne1' => '1 rue de la Paix',
            'ville' => 'Paris',
            'code_postal' => '75001',
            'pays' => 'France',
        ]);

        // Créer l'utilisateur admin
        User::firstOrCreate(
            ['email' => 'bertmatheo@gmail.com'],
            [
                'name' => 'Admin',
                'prenom' => 'Système',
                'password' => bcrypt('azertyuiop'),
                'est_admin' => true,
                'id_adresse' => $adresse->id_adresse,
                'chiffre_affaire' => 0,
                'taux_charge' => 0,
            ]
        );
    }
}