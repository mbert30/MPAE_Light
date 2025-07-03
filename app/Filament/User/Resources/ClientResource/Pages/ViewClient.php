<?php

namespace App\Filament\User\Resources\ClientResource\Pages;

use App\Filament\User\Resources\ClientResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use App\Traits\HasFrenchDates;

class ViewClient extends ViewRecord
{
    use HasFrenchDates;
    
    protected static string $resource = ClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Modifier'),
            Actions\DeleteAction::make()
                ->label('Supprimer'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informations client')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('designation')
                                    ->label('Nom du client')
                                    ->icon('heroicon-m-building-office-2')
                                    ->copyable()
                                    ->weight('bold')
                                    ->size('lg'),

                                Infolists\Components\TextEntry::make('email')
                                    ->label('Email')
                                    ->icon('heroicon-m-envelope')
                                    ->copyable()
                                    ->url(fn ($record) => $record->email ? "mailto:{$record->email}" : null),

                                Infolists\Components\TextEntry::make('telephone')
                                    ->label('Téléphone')
                                    ->icon('heroicon-m-phone')
                                    ->copyable()
                                    ->formatStateUsing(fn (string $state): string => 
                                        preg_replace('/(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/', '$1 $2 $3 $4 $5', $state)
                                    )
                                    ->url(fn ($record) => $record->telephone ? "tel:{$record->telephone}" : null),

                                Infolists\Components\TextEntry::make('projets_count')
                                    ->label('Nombre de projets')
                                    ->icon('heroicon-m-briefcase')
                                    ->badge()
                                    ->getStateUsing(fn ($record) => $record->projets->count())
                                    ->color(fn (int $state): string => match (true) {
                                        $state === 0 => 'gray',
                                        $state <= 3 => 'warning',
                                        default => 'success',
                                    }),
                            ])
                    ])
                    ->icon('heroicon-m-user-circle'),

                Infolists\Components\Section::make('Adresse')
                    ->schema([
                        Infolists\Components\Grid::make(1)
                            ->schema([
                                Infolists\Components\TextEntry::make('adresse.ligne1')
                                    ->label('Adresse')
                                    ->icon('heroicon-m-map-pin'),

                                Infolists\Components\TextEntry::make('adresse.ligne2')
                                    ->label('Complément d\'adresse')
                                    ->placeholder('—')
                                    ->visible(fn ($record) => !empty($record->adresse?->ligne2)),

                                Infolists\Components\TextEntry::make('adresse.ligne3')
                                    ->label('Lieu-dit / Zone')
                                    ->placeholder('—')
                                    ->visible(fn ($record) => !empty($record->adresse?->ligne3)),

                                Infolists\Components\Grid::make(3)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('adresse.code_postal')
                                            ->label('Code postal')
                                            ->badge()
                                            ->color('gray'),

                                        Infolists\Components\TextEntry::make('adresse.ville')
                                            ->label('Ville')
                                            ->weight('medium'),

                                        Infolists\Components\TextEntry::make('adresse.pays')
                                            ->label('Pays')
                                            ->badge()
                                            ->color('success'),
                                    ])
                            ])
                    ])
                    ->icon('heroicon-m-map')
                    ->collapsible(),

                Infolists\Components\Section::make('Informations système')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Créé le')
                                    ->getStateUsing(fn ($record) => $record->created_at_french)
                                    ->icon('heroicon-m-calendar'),

                                Infolists\Components\TextEntry::make('updated_at')
                                    ->label('Dernière modification')
                                    ->dateTime('d/m/Y à H:i')
                                    ->since()
                                    ->icon('heroicon-m-pencil'),
                            ])
                    ])
                    ->icon('heroicon-m-information-circle')
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}