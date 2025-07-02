<?php
namespace App\Filament\User\Resources\FactureResource\Pages;

use App\Filament\User\Resources\FactureResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListFactures extends ListRecords
{
    protected static string $resource = FactureResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        return [
            'toutes' => Tab::make('Toutes les factures'),
            
            'brouillons' => Tab::make('Brouillons')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('etat_facture', 'brouillon'))
                ->badge(fn () => $this->getModel()::where('etat_facture', 'brouillon')
                    ->whereHas('devis.projet.client', fn ($q) => $q->where('id_utilisateur', Auth::id()))
                    ->count()),
            
            'envoyees' => Tab::make('Envoyées')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('etat_facture', 'envoyee'))
                ->badge(fn () => $this->getModel()::where('etat_facture', 'envoyee')
                    ->whereHas('devis.projet.client', fn ($q) => $q->where('id_utilisateur', Auth::id()))
                    ->count()),
            
            'en_retard' => Tab::make('En retard')
                ->modifyQueryUsing(fn (Builder $query) => 
                    $query->where('etat_facture', 'envoyee')
                          ->whereDate('date_paiement_limite', '<', now())
                )
                ->badge(fn () => $this->getModel()::where('etat_facture', 'envoyee')
                    ->whereDate('date_paiement_limite', '<', now())
                    ->whereHas('devis.projet.client', fn ($q) => $q->where('id_utilisateur', Auth::id()))
                    ->count())
                ->badgeColor('danger'),
            
            'payees' => Tab::make('Payées')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('etat_facture', 'payee'))
                ->badge(fn () => $this->getModel()::where('etat_facture', 'payee')
                    ->whereHas('devis.projet.client', fn ($q) => $q->where('id_utilisateur', Auth::id()))
                    ->count())
                ->badgeColor('success'),
        ];
    }
}