<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Adresse;
use App\Models\Client;
use App\Models\Projet;
use App\Models\Devis;
use App\Models\LigneDevis;
use App\Models\Facture;
use App\Models\LigneFacturation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Créer un utilisateur de démonstration
        $adresseUser = Adresse::create([
            'ligne1' => '15 Avenue des Entrepreneurs',
            'ligne2' => 'Bâtiment B',
            'ligne3' => null,
            'ville' => 'Lyon',
            'code_postal' => '69003',
            'pays' => 'France',
        ]);

        $user = User::create([
            'name' => 'Martin',
            'prenom' => 'Sophie',
            'email' => 'sophie.martin@demo.com',
            'password' => Hash::make('password'),
            'date_naissance' => '1985-03-15',
            'id_adresse' => $adresseUser->id_adresse,
            'telephone' => '06 12 34 56 78',
            'chiffre_affaire' => 80000.00,
            'taux_charge' => 25.00,
            'est_admin' => false,
        ]);

        // Vérifier que l'utilisateur a bien été créé
        if (!$user || !$user->id) {
            throw new \Exception('Impossible de créer l\'utilisateur de démonstration');
        }

        echo "Utilisateur créé avec l'ID: " . $user->id . "\n";

        // 2. Créer des adresses pour les clients
        $adresses = [
            Adresse::create([
                'ligne1' => '25 Rue de la République',
                'ligne2' => null,
                'ligne3' => null,
                'ville' => 'Paris',
                'code_postal' => '75011',
                'pays' => 'France',
            ]),
            Adresse::create([
                'ligne1' => '143 Avenue Jean Jaurès',
                'ligne2' => 'Suite 200',
                'ligne3' => null,
                'ville' => 'Marseille',
                'code_postal' => '13005',
                'pays' => 'France',
            ]),
            Adresse::create([
                'ligne1' => '8 Place Bellecour',
                'ligne2' => null,
                'ligne3' => null,
                'ville' => 'Lyon',
                'code_postal' => '69002',
                'pays' => 'France',
            ]),
            Adresse::create([
                'ligne1' => '67 Rue Victor Hugo',
                'ligne2' => null,
                'ligne3' => null,
                'ville' => 'Bordeaux',
                'code_postal' => '33000',
                'pays' => 'France',
            ]),
            Adresse::create([
                'ligne1' => '32 Boulevard Haussmann',
                'ligne2' => '3ème étage',
                'ligne3' => null,
                'ville' => 'Paris',
                'code_postal' => '75009',
                'pays' => 'France',
            ]),
        ];

        // 3. Créer des clients réalistes
        $clients = [
            Client::create([
                'id_utilisateur' => $user->id,
                'designation' => 'TechnoSoft Solutions',
                'id_adresse' => $adresses[0]->id_adresse,
                'email' => 'contact@technosoft-solutions.fr',
                'telephone' => '01 42 35 67 89',
                'created_at' => Carbon::now()->subMonths(8),
            ]),
            Client::create([
                'id_utilisateur' => $user->id,
                'designation' => 'Green Energy SARL',
                'id_adresse' => $adresses[1]->id_adresse,
                'email' => 'direction@green-energy.fr',
                'telephone' => '04 91 23 45 67',
                'created_at' => Carbon::now()->subMonths(6),
            ]),
            Client::create([
                'id_utilisateur' => $user->id,
                'designation' => 'Boutique Mode & Style',
                'id_adresse' => $adresses[2]->id_adresse,
                'email' => 'info@mode-style.fr',
                'telephone' => '04 78 12 34 56',
                'created_at' => Carbon::now()->subMonths(4),
            ]),
            Client::create([
                'id_utilisateur' => $user->id,
                'designation' => 'Cabinet Juridique Dupont',
                'id_adresse' => $adresses[3]->id_adresse,
                'email' => 'cabinet@dupont-avocats.fr',
                'telephone' => '05 56 78 90 12',
                'created_at' => Carbon::now()->subMonths(3),
            ]),
            Client::create([
                'id_utilisateur' => $user->id,
                'designation' => 'Restaurant Le Gourmet',
                'id_adresse' => $adresses[4]->id_adresse,
                'email' => 'reservation@legourmet.fr',
                'telephone' => '01 45 67 89 01',
                'created_at' => Carbon::now()->subMonths(2),
            ]),
        ];

        // 4. Créer des projets variés
        $projets = [
            // Projets terminés (pour avoir du CA)
            Projet::create([
                'id_client' => $clients[0]->id_client,
                'designation' => 'Développement site e-commerce',
                'statut' => 'termine',
                'created_at' => Carbon::now()->subMonths(7),
                'updated_at' => Carbon::now()->subMonths(4),
            ]),
            Projet::create([
                'id_client' => $clients[1]->id_client,
                'designation' => 'Application mobile de suivi énergétique',
                'statut' => 'termine',
                'created_at' => Carbon::now()->subMonths(5),
                'updated_at' => Carbon::now()->subMonths(2),
            ]),
            Projet::create([
                'id_client' => $clients[2]->id_client,
                'designation' => 'Site vitrine avec catalogue produits',
                'statut' => 'termine',
                'created_at' => Carbon::now()->subMonths(3),
                'updated_at' => Carbon::now()->subMonth(),
            ]),
            
            // Projets en cours
            Projet::create([
                'id_client' => $clients[3]->id_client,
                'designation' => 'Système de gestion documentaire',
                'statut' => 'demarre',
                'created_at' => Carbon::now()->subMonths(2),
                'updated_at' => Carbon::now()->subWeeks(2),
            ]),
            Projet::create([
                'id_client' => $clients[4]->id_client,
                'designation' => 'Site de réservation en ligne',
                'statut' => 'devis_accepte',
                'created_at' => Carbon::now()->subMonth(),
                'updated_at' => Carbon::now()->subWeeks(3),
            ]),
            
            // Nouveaux prospects
            Projet::create([
                'id_client' => $clients[0]->id_client,
                'designation' => 'Refonte du système de facturation',
                'statut' => 'devis_envoye',
                'created_at' => Carbon::now()->subWeeks(2),
                'updated_at' => Carbon::now()->subWeeks(1),
            ]),
        ];

        // 5. Créer des devis et leurs lignes
        $this->createDevisWithLines($projets[0], 1, 'accepte', Carbon::now()->subMonths(6), [
            ['Développement frontend React', 3500.00, 1],
            ['Développement backend Laravel', 4200.00, 1],
            ['Intégration paiement Stripe', 800.00, 1],
            ['Tests et mise en production', 1200.00, 1],
        ]);

        $this->createDevisWithLines($projets[1], 2, 'accepte', Carbon::now()->subMonths(4), [
            ['Développement application mobile Flutter', 6500.00, 1],
            ['API backend avec authentification', 2800.00, 1],
            ['Interface d\'administration', 1500.00, 1],
        ]);

        $this->createDevisWithLines($projets[2], 3, 'accepte', Carbon::now()->subMonths(2), [
            ['Création site WordPress sur mesure', 2200.00, 1],
            ['Intégration catalogue WooCommerce', 1300.00, 1],
            ['Formation et documentation', 500.00, 1],
        ]);

        $this->createDevisWithLines($projets[3], 4, 'accepte', Carbon::now()->subMonth(), [
            ['Analyse des besoins', 1200.00, 1],
            ['Développement système documentaire', 4500.00, 1],
            ['Formation utilisateurs', 800.00, 1],
        ]);

        $this->createDevisWithLines($projets[4], 5, 'accepte', Carbon::now()->subWeeks(3), [
            ['Site de réservation responsive', 2800.00, 1],
            ['Système de paiement en ligne', 1200.00, 1],
            ['Module de gestion des menus', 600.00, 1],
        ]);

        $this->createDevisWithLines($projets[5], 6, 'envoye', Carbon::now()->subWeeks(1), [
            ['Audit du système existant', 800.00, 1],
            ['Développement nouveau module facturation', 3200.00, 1],
            ['Migration des données', 1000.00, 1],
        ]);

        // 6. Créer des factures réalistes avec étalement dans le temps
        $this->createFacturesForDemo($user);
    }

    private function createDevisWithLines($projet, $numeroDevis, $statut, $dateCreation, $lignes)
    {
        $devis = Devis::create([
            'numero_devis' => $numeroDevis,
            'id_projet' => $projet->id_projet,
            'statut' => $statut,
            'date_validite' => $dateCreation->copy()->addMonths(2),
            'note' => 'Devis établi selon vos spécifications techniques.',
            'taux_tva' => 20.00,
            'created_at' => $dateCreation,
            'updated_at' => $dateCreation->copy()->addDays(rand(1, 5)),
        ]);

        foreach ($lignes as $index => $ligne) {
            LigneDevis::create([
                'id_devis' => $devis->id_devis,
                'libelle' => $ligne[0],
                'prix_unitaire' => $ligne[1],
                'quantite' => $ligne[2],
                'ordre' => $index + 1,
                'created_at' => $dateCreation,
                'updated_at' => $dateCreation,
            ]);
        }

        return $devis;
    }

    private function createFacturesForDemo($user)
    {
        // Récupérer tous les devis acceptés
        $devisAcceptes = Devis::where('statut', 'accepte')->with('ligneDevis')->get();

        foreach ($devisAcceptes as $devis) {
            // Créer des factures avec différents états et dates réalistes
            $this->createFactureFromDevis($devis, $user);
        }
    }

    private function createFactureFromDevis($devis, $user)
    {
        $dateCreationProjet = $devis->created_at;
        $dateFacture = $dateCreationProjet->copy()->addWeeks(rand(2, 8));
        
        // Déterminer l'état de la facture selon son ancienneté
        $now = Carbon::now();
        $ageInDays = $dateFacture->diffInDays($now);
        
        if ($ageInDays > 60) {
            $etat = 'payee';
            $datePaiementEffectif = $dateFacture->copy()->addDays(rand(15, 45));
            $typePaiement = collect(['virement', 'cheque', 'carte'])->random();
        } elseif ($ageInDays > 30) {
            $etat = 'envoyee';
            $datePaiementEffectif = null;
            $typePaiement = null;
        } else {
            $etat = collect(['brouillon', 'envoyee'])->random();
            $datePaiementEffectif = null;
            $typePaiement = null;
        }

        $numeroFacture = Facture::getNextNumeroFacture($user->id);

        $facture = Facture::create([
            'numero_facture' => $numeroFacture,
            'id_devis' => $devis->id_devis,
            'etat_facture' => $etat,
            'taux_tva' => $devis->taux_tva,
            'date_edition' => $dateFacture,
            'date_paiement_limite' => $dateFacture->copy()->addDays(30),
            'type_paiement' => $typePaiement,
            'date_paiement_effectif' => $datePaiementEffectif,
            'note' => 'Merci de procéder au règlement dans les délais impartis.',
            'created_at' => $dateFacture,
            'updated_at' => $datePaiementEffectif ?: $dateFacture,
        ]);

        // Copier les lignes du devis vers la facture
        foreach ($devis->ligneDevis as $ligneDevis) {
            LigneFacturation::create([
                'id_facture' => $facture->id_facture,
                'libelle' => $ligneDevis->libelle,
                'prix_unitaire' => $ligneDevis->prix_unitaire,
                'quantite' => $ligneDevis->quantite,
                'ordre' => $ligneDevis->ordre,
                'created_at' => $dateFacture,
                'updated_at' => $dateFacture,
            ]);
        }
    }
}