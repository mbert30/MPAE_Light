<?php

namespace App\Filament\User\Resources\DevisResource\Pages;

use App\Filament\User\Resources\DevisResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Notifications\Notification;

class ViewDevis extends ViewRecord
{
    protected static string $resource = DevisResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [];
        
        // Bouton Modifier - Disponible pour tous les statuts
        $actions[] = Actions\EditAction::make();
        
        // Boutons selon le statut
        switch ($this->record->statut) {
            case 'brouillon':
                break;
            case 'envoye':
                // Envoyé : Modifier + Extraire
                $actions[] = Actions\Action::make('extract_excel')
                    ->label('Extraire en Excel')
                    ->icon('heroicon-o-table-cells')
                    ->color('success')
                    ->action(function () {
                        return $this->record->downloadExcel();
                    });
                break;
            case 'accepte':
                $actions[] = Actions\Action::make('extract_excel')
                    ->label('Extraire en Excel')
                    ->icon('heroicon-o-table-cells')
                    ->color('success')
                    ->action(function () {
                        return $this->record->downloadExcel();
                    });
                $actions[] = Actions\Action::make('create_facture')
                    ->label('Créer facture')
                    ->icon('heroicon-o-document-plus')
                    ->color('success')
                    ->visible(fn () => $this->record->statut === 'accepte')
                    ->url(fn () => route('filament.user.resources.factures.create', ['devis' => $this->record->id_devis]));
                break;
            case 'refuse':
            case 'expire':
                $actions[] = Actions\Action::make('extract_excel')
                    ->label('Extraire en Excel')
                    ->icon('heroicon-o-table-cells')
                    ->color('success')
                    ->action(function () {
                        return $this->record->downloadExcel();
                    });
                break;
        }
        
        return $actions;
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Informations du devis')
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('numero_devis')
                                    ->label('Numéro de devis'),
                                Components\TextEntry::make('statut')
                                    ->label('Statut')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'brouillon' => 'gray',
                                        'envoye' => 'warning',
                                        'accepte' => 'success',
                                        'refuse' => 'danger',
                                        'expire' => 'gray',
                                    }),
                                Components\TextEntry::make('projet.designation')
                                    ->label('Projet'),
                                Components\TextEntry::make('projet.client.designation')
                                    ->label('Client'),
                                Components\TextEntry::make('date_validite')
                                    ->label('Date de validité')
                                    ->getStateUsing(fn ($record) => $record->formatFrenchDateOnly($record->date_validite))
                                    ->color(fn ($record) => $record->est_expire ? 'danger' : 'gray')
                                    ->icon(fn ($record) => $record->est_expire ? 'heroicon-o-exclamation-triangle' : null),
                                Components\TextEntry::make('created_at')
                                    ->label('Créé le')
                                    ->getStateUsing(fn ($record) => $record->created_at_french),
                                Components\TextEntry::make('updated_at')
                                    ->label('Modifié le')
                                    ->dateTime('d/m/Y à H:i')
                                    ->since(),
                            ]),
                        Components\TextEntry::make('note')
                            ->label('Note')
                            ->columnSpanFull()
                            ->visible(fn ($state) => !empty($state)),
                    ]),

                Components\Section::make('Lignes du devis')
                    ->schema([
                        Components\RepeatableEntry::make('lignesDevis')
                            ->schema([
                                Components\Grid::make(4)
                                    ->schema([
                                        Components\TextEntry::make('libelle')
                                            ->label('Libellé')
                                            ->columnSpan(2),
                                        Components\TextEntry::make('prix_unitaire')
                                            ->label('Prix unitaire')
                                            ->money('EUR'),
                                        Components\TextEntry::make('quantite')
                                            ->label('Quantité'),
                                    ]),
                            ])
                            ->columns(1),
                    ]),

                Components\Section::make('Résumé financier')
                    ->schema([
                        Components\Grid::make(2) // Grid en 2 colonnes pour un meilleur affichage
                            ->schema([
                                Components\TextEntry::make('montant_total_ht')
                                    ->label('Montant total HT')
                                    ->money('EUR')
                                    ->size('lg')
                                    ->weight('bold'),
                                    
                                Components\TextEntry::make('taux_tva')
                                    ->label('Taux TVA')
                                    ->suffix('%')
                                    ->size('lg')
                                    ->weight('bold'),
                                    
                                Components\TextEntry::make('montant_tva')
                                    ->label('Montant TVA')
                                    ->money('EUR')
                                    ->size('lg')
                                    ->weight('bold')
                                    ->color('warning'),
                                    
                                Components\TextEntry::make('montant_total_ttc')
                                    ->label('Montant total TTC')
                                    ->money('EUR')
                                    ->size('xl')
                                    ->weight('bold')
                                    ->color('success'),
                            ]),
                    ]),
            ]);
    }
}