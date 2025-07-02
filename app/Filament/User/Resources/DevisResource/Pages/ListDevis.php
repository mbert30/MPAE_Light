<?php
// app/Filament/Resources/DevisResource/Pages/ListDevis.php

namespace App\Filament\User\Resources\DevisResource\Pages;

use App\Filament\User\Resources\DevisResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListDevis extends ListRecords
{
    protected static string $resource = DevisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'tous' => Tab::make('Tous les devis'),
            
            'brouillons' => Tab::make('Brouillons')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('statut', 'brouillon'))
                ->badge(fn () => $this->getModel()::where('statut', 'brouillon')
                    ->whereHas('projet.client', fn ($q) => $q->where('id_utilisateur', Auth::id()))
                    ->count()),
            
            'envoyes' => Tab::make('Envoyés')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('statut', 'envoye'))
                ->badge(fn () => $this->getModel()::where('statut', 'envoye')
                    ->whereHas('projet.client', fn ($q) => $q->where('id_utilisateur', Auth::id()))
                    ->count()),
            
            'acceptes' => Tab::make('Acceptés')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('statut', 'accepte'))
                ->badge(fn () => $this->getModel()::where('statut', 'accepte')
                    ->whereHas('projet.client', fn ($q) => $q->where('id_utilisateur', Auth::id()))
                    ->count())
                ->badgeColor('success'),
            
            'refuses' => Tab::make('Refusés')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('statut', 'refuse'))
                ->badge(fn () => $this->getModel()::where('statut', 'refuse')
                    ->whereHas('projet.client', fn ($q) => $q->where('id_utilisateur', Auth::id()))
                    ->count())
                ->badgeColor('danger'),
            
            'expires' => Tab::make('Expirés')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('statut', 'expire'))
                ->badge(fn () => $this->getModel()::where('statut', 'expire')
                    ->whereHas('projet.client', fn ($q) => $q->where('id_utilisateur', Auth::id()))
                    ->count())
                ->badgeColor('gray'),
        ];
    }
}