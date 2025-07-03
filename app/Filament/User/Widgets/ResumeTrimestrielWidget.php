<?php

namespace App\Filament\User\Widgets;

use App\Models\Facture;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ResumeTrimestrielWidget extends Widget
{
    protected static string $view = 'filament.widgets.resume-trimestriel';
    
    protected static ?string $heading = 'Résumé Trimestriel';

    public $currentQuarter;
    public $currentYear;

    public function mount(): void
    {
        $this->currentQuarter = now()->quarter;
        $this->currentYear = now()->year;
    }

    public function previousQuarter(): void
    {
        if ($this->currentQuarter == 1) {
            $this->currentQuarter = 4;
            $this->currentYear--;
        } else {
            $this->currentQuarter--;
        }
    }

    public function nextQuarter(): void
    {
        if ($this->currentQuarter == 4) {
            $this->currentQuarter = 1;
            $this->currentYear++;
        } else {
            $this->currentQuarter++;
        }
    }

    public function getTrimestreData(): array
    {
        $user = Auth::user();
        
        // Calculer les dates du trimestre
        $startMonth = ($this->currentQuarter - 1) * 3 + 1;
        $endMonth = $this->currentQuarter * 3;
        
        $startDate = Carbon::create($this->currentYear, $startMonth, 1)->startOfMonth();
        $endDate = Carbon::create($this->currentYear, $endMonth, 1)->endOfMonth();

        // CA payé du trimestre
        $caPaye = Facture::whereHas('devis.projet.client', function ($query) use ($user) {
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

        // CA estimé (basé sur les dates de paiement limites)
        $caEstime = Facture::whereHas('devis.projet.client', function ($query) use ($user) {
                $query->where('id_utilisateur', $user->id);
            })
            ->where('etat_facture', 'envoyee')
            ->whereBetween('date_paiement_limite', [$startDate, $endDate])
            ->with('lignesFacturation')
            ->get()
            ->sum(function ($facture) {
                $sousTotal = $facture->lignesFacturation->sum(function ($ligne) {
                    return $ligne->prix_unitaire * $ligne->quantite;
                });
                return $sousTotal * (1 + $facture->taux_tva / 100);
            });

        // Charges à payer (basé sur le CA payé)
        $chargesAPayer = $caPaye * ($user->taux_charge / 100);

        // Charges estimées à payer (basé sur le CA estimé)
        $chargesEstimees = $caEstime * ($user->taux_charge / 100);

        return [
            'periode' => $startDate->format('d/m/Y') . ' - ' . $endDate->format('d/m/Y'),
            'trimestre_label' => 'T' . $this->currentQuarter . ' ' . $this->currentYear,
            'ca_paye' => $caPaye,
            'ca_estime' => $caEstime,
            'charges_a_payer' => $chargesAPayer,
            'charges_estimees' => $chargesEstimees,
        ];
    }
}