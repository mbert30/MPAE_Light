<?php

namespace App\Filament\User\Widgets;

use App\Models\Facture;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ChiffreAffaireChart extends ChartWidget
{
    protected static ?string $heading = 'Évolution du chiffre d\'affaire';

    protected function getData(): array
    {
        $userId = Auth::id();
        
        // Récupérer les données des 12 derniers mois pour l'utilisateur connecté
        $months = collect();
        for ($i = 11; $i >= 0; $i--) {
            $months->push(Carbon::now()->subMonths($i));
        }
        
        $data = $months->map(function ($month) use ($userId) {
            // FILTRER par utilisateur ici
            $ca = Facture::whereHas('devis.projet.client', function ($query) use ($userId) {
                $query->where('id_utilisateur', $userId);
            })
            ->where('etat_facture', 'payee')
            ->whereYear('date_paiement_effectif', $month->year)
            ->whereMonth('date_paiement_effectif', $month->month)
            ->get()
            ->sum('montant_total_ttc');
            
            return [
                'month' => $month->format('M Y'),
                'ca' => round($ca, 2)
            ];
        });

        return [
            'datasets' => [
                [
                    'label' => 'CA mensuel (€)',
                    'data' => $data->pluck('ca')->toArray(),
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'fill' => true,
                ],
            ],
            'labels' => $data->pluck('month')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}