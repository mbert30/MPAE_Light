<?php

namespace App\Filament\Admin\Widgets;

use App\Models\User;
use App\Models\Client;
use App\Models\Projet;
use App\Models\Facture;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $totalUsers = User::count();
        $totalAdmins = User::where('est_admin', true)->count();
        $usersThisMonth = User::whereMonth('created_at', now()->month)
                              ->whereYear('created_at', now()->year)
                              ->count();
        
        $totalClients = Client::count();
        $totalProjets = Projet::count();
        
        $totalFactures = Facture::count();
        $facturesPayees = Facture::where('etat_facture', 'payee')->count();
        
        // Calcul du CA total (factures payées)
        $caTotalPaye = Facture::where('etat_facture', 'payee')
            ->with('ligneFacturations')
            ->get()
            ->sum(function ($facture) {
                $sousTotal = $facture->ligneFacturations->sum(function ($ligne) {
                    return $ligne->prix_unitaire * $ligne->quantite;
                });
                return $sousTotal * (1 + $facture->taux_tva / 100);
            });

        return [
            Stat::make('Utilisateurs totaux', $totalUsers)
                ->description($usersThisMonth . ' ce mois-ci')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            
            Stat::make('Administrateurs', $totalAdmins)
                ->description('Sur ' . $totalUsers . ' utilisateurs')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('warning'),
            
            Stat::make('Clients totaux', $totalClients)
                ->description('Tous utilisateurs confondus')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('info'),
            
            Stat::make('Projets actifs', $totalProjets)
                ->description('Tous statuts confondus')
                ->descriptionIcon('heroicon-m-briefcase')
                ->color('primary'),
            
            Stat::make('Factures payées', $facturesPayees)
                ->description('Sur ' . $totalFactures . ' factures')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
            
            Stat::make('CA total payé', number_format($caTotalPaye, 2, ',', ' ') . ' €')
                ->description('Toutes factures payées')
                ->descriptionIcon('heroicon-m-currency-euro')
                ->color('success'),
        ];
    }
}