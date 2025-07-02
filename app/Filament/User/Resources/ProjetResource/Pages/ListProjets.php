<?php

namespace App\Filament\User\Resources\ProjetResource\Pages;

use App\Filament\User\Resources\ProjetResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListProjets extends ListRecords
{
    public ?string $activeTab = 'demarre';
    protected static string $resource = ProjetResource::class;
   

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nouveau projet')
                ->icon('heroicon-m-plus'),
        ];
    }

    public function getTabs(): array
    {
        $userId = Auth::id();

        return [
            'tous' => Tab::make('Tous les projets')
                ->modifyQueryUsing(function (Builder $query) use ($userId) {
                    return $query->whereHas('client', function ($q) use ($userId) {
                        $q->where('id_utilisateur', $userId);
                    });
                }),

            'en_cours' => Tab::make('En cours')
                ->modifyQueryUsing(function (Builder $query) use ($userId) {
                    return $query->whereHas('client', function ($q) use ($userId) {
                        $q->where('id_utilisateur', $userId);
                    })->where('statut', 'demarre');
                })
                ->badge(function () use ($userId) {
                    return \App\Models\Projet::whereHas('client', function ($q) use ($userId) {
                        $q->where('id_utilisateur', $userId);
                    })->where('statut', 'demarre')->count();
                }),

            'prospects' => Tab::make('Prospects')
                ->modifyQueryUsing(function (Builder $query) use ($userId) {
                    return $query->whereHas('client', function ($q) use ($userId) {
                        $q->where('id_utilisateur', $userId);
                    })->where('statut', 'prospect');
                })
                ->badge(function () use ($userId) {
                    return \App\Models\Projet::whereHas('client', function ($q) use ($userId) {
                        $q->where('id_utilisateur', $userId);
                    })->where('statut', 'prospect')->count();
                }),

            'devis_envoyes' => Tab::make('Devis envoyés')
                ->modifyQueryUsing(function (Builder $query) use ($userId) {
                    return $query->whereHas('client', function ($q) use ($userId) {
                        $q->where('id_utilisateur', $userId);
                    })->where('statut', 'devis_envoye');
                })
                ->badge(function () use ($userId) {
                    return \App\Models\Projet::whereHas('client', function ($q) use ($userId) {
                        $q->where('id_utilisateur', $userId);
                    })->where('statut', 'devis_envoye')->count();
                }),

            'devis_acceptes' => Tab::make('Devis acceptés')
                ->modifyQueryUsing(function (Builder $query) use ($userId) {
                    return $query->whereHas('client', function ($q) use ($userId) {
                        $q->where('id_utilisateur', $userId);
                    })->where('statut', 'devis_accepte');
                })
                ->badge(function () use ($userId) {
                    return \App\Models\Projet::whereHas('client', function ($q) use ($userId) {
                        $q->where('id_utilisateur', $userId);
                    })->where('statut', 'devis_accepte')->count();
                }),

            'demarres' => Tab::make('Démarrés')
                ->modifyQueryUsing(function (Builder $query) use ($userId) {
                    return $query->whereHas('client', function ($q) use ($userId) {
                        $q->where('id_utilisateur', $userId);
                    })->where('statut', 'demarre');
                })
                ->badge(function () use ($userId) {
                    return \App\Models\Projet::whereHas('client', function ($q) use ($userId) {
                        $q->where('id_utilisateur', $userId);
                    })->where('statut', 'demarre')->count();
                }),

            'termines' => Tab::make('Terminés')
                ->modifyQueryUsing(function (Builder $query) use ($userId) {
                    return $query->whereHas('client', function ($q) use ($userId) {
                        $q->where('id_utilisateur', $userId);
                    })->where('statut', 'termine');
                })
                ->badge(function () use ($userId) {
                    return \App\Models\Projet::whereHas('client', function ($q) use ($userId) {
                        $q->where('id_utilisateur', $userId);
                    })->where('statut', 'termine')->count();
                }),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('client', function (Builder $query) {
                $query->where('id_utilisateur', Auth::id());
            });
    }
}