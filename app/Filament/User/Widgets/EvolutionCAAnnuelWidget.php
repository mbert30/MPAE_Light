<?php

namespace App\Filament\User\Widgets;

use App\Models\Facture;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class EvolutionCAAnnuelWidget extends ChartWidget
{
    protected static ?string $heading = 'Évolution du CA Annuel Payé';
    
    protected static ?int $sort = 4;

    protected function getData(): array
    {
        $user = Auth::user();
        $currentYear = now()->year;
        
        $data = [];
        $labels = [];
        $cumulatif = 0;
        
        for ($month = 1; $month <= 12; $month++) {
            $startDate = Carbon::create($currentYear, $month, 1)->startOfMonth();
            $endDate = Carbon::create($currentYear, $month, 1)->endOfMonth();
            
            $caMensuel = Facture::whereHas('devis.projet.client', function ($query) use ($user) {
                    $query->where('id_utilisateur', $user->id);
                })
                ->where('etat_facture', 'payee')
                ->whereBetween('date_paiement_effectif', [$startDate, $endDate])
                ->with('lignesFacturation')
                ->get()
                ->sum(function ($facture) {
                    $sousTotal = $facture->lignesFacturation->sum(function ($ligne) {
                        return $ligne->prix_unitaire * $ligne->quantite;
                    });
                    return $sousTotal * (1 + $facture->taux_tva / 100);
                });
                
            $cumulatif += $caMensuel;
            $data[] = round($cumulatif, 2);
            $labels[] = Carbon::create($currentYear, $month, 1)->format('M Y');
        }

        return [
            'datasets' => [
                [
                    'label' => 'CA Annuel Cumulé (€)',
                    'data' => $data,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'borderColor' => 'rgb(16, 185, 129)',
                    'borderWidth' => 3,
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value) { return value + " €"; }',
                    ],
                ],
            ],
            'plugins' => [
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) { return context.dataset.label + ": " + context.parsed.y + " €"; }',
                    ],
                ],
            ],
        ];
    }
}