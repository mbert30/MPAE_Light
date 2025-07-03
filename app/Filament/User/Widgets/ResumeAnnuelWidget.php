<?php

namespace App\Filament\User\Widgets;

use App\Models\Facture;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class ResumeAnnuelWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $user = Auth::user();
        $currentYear = now()->year;

        // CA annuel (factures payées de l'année en cours)
        $caAnnuel = Facture::whereHas('devis.projet.client', function ($query) use ($user) {
                $query->where('id_utilisateur', $user->id);
            })
            ->where('etat_facture', 'payee')
            ->whereYear('date_paiement_effectif', $currentYear)
            ->with('lignesFacturation')
            ->get()
            ->sum(function ($facture) {
                $sousTotal = $facture->lignesFacturation->sum(function ($ligne) {
                    return $ligne->prix_unitaire * $ligne->quantite;
                });
                return $sousTotal * (1 + $facture->taux_tva / 100);
            });

        // Somme des paiements en attente (factures envoyées non payées)
        $paiementsEnAttente = Facture::whereHas('devis.projet.client', function ($query) use ($user) {
                $query->where('id_utilisateur', $user->id);
            })
            ->where('etat_facture', 'envoyee')
            ->with('ligneFacturations')
            ->get()
            ->sum(function ($facture) {
                $sousTotal = $facture->lignesFacturation->sum(function ($ligne) {
                    return $ligne->prix_unitaire * $ligne->quantite;
                });
                return $sousTotal * (1 + $facture->taux_tva / 100);
            });

        // Somme des factures éditées non envoyées
        $facturesNonEnvoyees = Facture::whereHas('devis.projet.client', function ($query) use ($user) {
                $query->where('id_utilisateur', $user->id);
            })
            ->where('etat_facture', 'brouillon')
            ->with('ligneFacturations')
            ->get()
            ->sum(function ($facture) {
                $sousTotal = $facture->ligneFacturations->sum(function ($ligne) {
                    return $ligne->prix_unitaire * $ligne->quantite;
                });
                return $sousTotal * (1 + $facture->taux_tva / 100);
            });

        // CA annuel max
        $caAnnuelMax = $user->chiffre_affaire;

        // CA annuel restant à faire
        $caRestant = max(0, $caAnnuelMax - $caAnnuel);

        return [
            Stat::make('CA Annuel (' . $currentYear . ')', number_format($caAnnuel, 2, ',', ' ') . ' €')
                ->description('Factures payées cette année')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Paiements en attente', number_format($paiementsEnAttente, 2, ',', ' ') . ' €')
                ->description('Factures envoyées non payées')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Factures non envoyées', number_format($facturesNonEnvoyees, 2, ',', ' ') . ' €')
                ->description('Factures éditées en attente')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info'),

            Stat::make('CA Maximum', number_format($caAnnuelMax, 2, ',', ' ') . ' €')
                ->description('Objectif annuel')
                ->descriptionIcon('heroicon-m-flag')
                ->color('primary'),

            Stat::make('CA Restant', number_format($caRestant, 2, ',', ' ') . ' €')
                ->description('À réaliser cette année')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color($caRestant > 0 ? 'gray' : 'success'),
        ];
    }
}