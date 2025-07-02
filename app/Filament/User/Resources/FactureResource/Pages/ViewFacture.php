<?php

namespace App\Filament\User\Resources\FactureResource\Pages;

use App\Filament\User\Resources\FactureResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Notifications\Notification;
use Filament\Forms;

class ViewFacture extends ViewRecord
{
    protected static string $resource = FactureResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [];
        
        if ($this->record->canBeModified()) {
            $actions[] = Actions\EditAction::make();
        }

        $actions[] = Actions\Action::make('export_excel')
            ->label('Exporter en Excel')
            ->icon('heroicon-o-table-cells')
            ->color('success')
            ->action(function () {
                return $this->record->downloadExcel();
            });
        
        switch ($this->record->etat_facture) {
            case 'brouillon':
                break;
                
            case 'envoyee':
                // Envoyée : Modifier + Export + Marquer payée
                $actions[] = Actions\Action::make('mark_paid')
                    ->label('Marquer comme payée')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Marquer la facture comme payée')
                    ->modalDescription('Cette action marquera la facture comme payée. Vous ne pourrez plus la modifier après cette action.')
                    ->form([
                        Forms\Components\DatePicker::make('date_paiement_effectif')
                            ->label('Date de paiement effectif')
                            ->required()
                            ->default(today())
                            ->maxDate(today()),
                    ])
                    ->action(function (array $data) {
                        $this->record->update([
                            'etat_facture' => 'payee',
                            'date_paiement_effectif' => $data['date_paiement_effectif']
                        ]);
                        
                        $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                    });
                $actions[] = Actions\Action::make('set_to_draft')
                    ->label('Repasser en brouillon')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->visible(fn () =>                     $this->record->etat_facture === 'envoyee')
                    ->requiresConfirmation()
                    ->modalHeading('Repasser la facture en brouillon')
                    ->modalDescription('Êtes-vous sûr de vouloir repasser cette facture en brouillon ? Vous pourrez alors la modifier à nouveau.')
                    ->modalSubmitActionLabel('Repasser en brouillon')
                    ->action(function () {
                        $this->record->update([
                            'etat_facture' => 'brouillon',
                        ]);

                        Notification::make()
                            ->title('Facture remise en brouillon')
                            ->body('La facture est maintenant modifiable.')
                            ->success()
                            ->send();

                        return redirect($this->getResource()::getUrl('edit', ['record' => $this->record]));
                    });
                break;
                
            case 'payee':
                // Payée : Seulement consulter et exporter (export déjà ajouté)
                break;
        }
        
        return $actions;
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Informations de la facture')
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('numero_facture')
                                    ->label('Numéro de facture'),
                                Components\TextEntry::make('etat_facture')
                                    ->label('État')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'brouillon' => 'gray',
                                        'envoyee' => 'warning',
                                        'payee' => 'success',
                                    }),
                                Components\TextEntry::make('devis.numero_devis')
                                    ->label('Devis associé')
                                    ->url(fn ($record) => route('filament.admin.resources.devis.view', $record->devis))
                                    ->openUrlInNewTab(),
                                Components\TextEntry::make('devis.projet.designation')
                                    ->label('Projet'),
                                Components\TextEntry::make('devis.projet.client.designation')
                                    ->label('Client'),
                                Components\TextEntry::make('taux_tva')
                                    ->label('Taux TVA')
                                    ->suffix('%'),
                                Components\TextEntry::make('date_edition')
                                    ->label('Date d\'édition')
                                    ->date('d/m/Y'),
                                Components\TextEntry::make('date_paiement_limite')
                                    ->label('Date limite de paiement')
                                    ->date('d/m/Y')
                                    ->color(fn ($record) => $record->est_en_retard ? 'danger' : 'gray')
                                    ->icon(fn ($record) => $record->est_en_retard ? 'heroicon-o-exclamation-triangle' : null)
                                    ->badge(fn ($record) => $record->est_en_retard)
                                    ->formatStateUsing(function ($record, $state) {
                                        $date = $state instanceof \Carbon\Carbon ? $state->format('d/m/Y') : $state;
                                        return $record->est_en_retard ? $date . ' (EN RETARD)' : $date;
                                    }),
                                Components\TextEntry::make('type_paiement')
                                    ->label('Type de paiement')
                                    ->visible(fn ($state) => !empty($state)),
                                Components\TextEntry::make('date_paiement_effectif')
                                    ->label('Date de paiement effectif')
                                    ->date('d/m/Y')
                                    ->visible(fn ($state) => !empty($state)),
                                Components\TextEntry::make('created_at')
                                    ->label('Créée le')
                                    ->dateTime('d/m/Y à H:i'),
                                Components\TextEntry::make('updated_at')
                                    ->label('Modifiée le')
                                    ->dateTime('d/m/Y à H:i'),
                            ]),
                        Components\TextEntry::make('note')
                            ->label('Note')
                            ->columnSpanFull()
                            ->visible(fn ($state) => !empty($state)),
                    ]),

                Components\Section::make('Lignes de facturation')
                    ->schema([
                        Components\RepeatableEntry::make('lignesFacturation')
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
                        Components\Grid::make(2)
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