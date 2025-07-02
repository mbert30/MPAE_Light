<?php

// Dans app/Filament/User/Widgets/StatsOverview.php
// Exemple de correction pour les statistiques

namespace App\Filament\User\Widgets;

use App\Models\Client;
use App\Models\Projet;
use App\Models\Devis;
use App\Models\Facture;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $userId = Auth::id();

        // AVANT (incorrect) : 
        // $devisCount = Devis::count(); // Compte TOUS les devis
        
        // APRÈS (correct) :
        $clientsCount = Client::where('id_utilisateur', $userId)->count();
        
        $projetsCount = Projet::whereHas('client', function ($query) use ($userId) {
            $query->where('id_utilisateur', $userId);
        })->count();
        
        $devisCount = Devis::whereHas('projet.client', function ($query) use ($userId) {
            $query->where('id_utilisateur', $userId);
        })->count();
        
        $facturesCount = Facture::whereHas('devis.projet.client', function ($query) use ($userId) {
            $query->where('id_utilisateur', $userId);
        })->count();

        // Statistiques par statut (exemple pour les projets)
        $projetsEnCours = Projet::whereHas('client', function ($query) use ($userId) {
            $query->where('id_utilisateur', $userId);
        })->where('statut', 'demarre')->count();
        
        $devisAcceptes = Devis::whereHas('projet.client', function ($query) use ($userId) {
            $query->where('id_utilisateur', $userId);
        })->where('statut', 'accepte')->count();

        return [
            Stat::make('Clients', $clientsCount)
                ->description('Clients enregistrés')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
                
            Stat::make('Projets', $projetsCount)
                ->description($projetsEnCours . ' en cours')
                ->descriptionIcon('heroicon-m-briefcase')
                ->color('info'),
                
            Stat::make('Devis', $devisCount)
                ->description($devisAcceptes . ' acceptés')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('warning'),
                
            Stat::make('Factures', $facturesCount)
                ->description('Factures émises')
                ->descriptionIcon('heroicon-m-currency-euro')
                ->color('success'),
        ];
    }
}