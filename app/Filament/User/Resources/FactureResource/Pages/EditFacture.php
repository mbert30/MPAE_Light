<?php

namespace App\Filament\User\Resources\FactureResource\Pages;

use App\Filament\User\Resources\FactureResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditFacture extends EditRecord
{
    protected static string $resource = FactureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->before(function (Actions\DeleteAction $action) {
                    if (!$this->record->canBeDeleted()) {
                        Notification::make()
                            ->title('Suppression impossible')
                            ->body('Cette facture ne peut pas être supprimée car elle n\'est pas en brouillon.')
                            ->danger()
                            ->send();
                        
                        $action->cancel();
                    }
                }),
            Actions\Action::make('mark_paid')
                ->label('Marquer payée')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => 
                    $this->record->etat_facture === 'envoyee' || 
                    $this->record->etat_facture === 'en_retard'
                )
                ->requiresConfirmation()
                ->modalHeading('Marquer la facture comme payée')
                ->modalDescription('Êtes-vous sûr de vouloir marquer cette facture comme payée ?')
                ->form([
                    \Filament\Forms\Components\DatePicker::make('date_paiement_effectif')
                        ->label('Date de paiement')
                        ->required()
                        ->default(\Carbon\Carbon::today()),
                    \Filament\Forms\Components\Select::make('type_paiement')
                        ->label('Type de paiement')
                        ->options([
                            'virement' => 'Virement',
                            'cheque' => 'Chèque',
                            'especes' => 'Espèces',
                            'carte' => 'Carte bancaire',
                            'paypal' => 'PayPal',
                            'autre' => 'Autre',
                        ])
                        ->required(),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'etat_facture' => 'payee',
                        'date_paiement_effectif' => $data['date_paiement_effectif'],
                        'type_paiement' => $data['type_paiement'],
                    ]);

                    Notification::make()
                        ->title('Facture marquée comme payée')
                        ->success()
                        ->send();

                    return redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Facture mise à jour')
            ->body('La facture a été mise à jour avec succès.');
    }

    protected function beforeSave(): void
    {
        // Vérifier si la facture peut être modifiée
        if (!$this->record->canBeModified()) {
            Notification::make()
                ->title('Modification impossible')
                ->body('Cette facture ne peut plus être modifiée car elle est payée.')
                ->danger()
                ->send();
            
            $this->halt();
        }

        // Validation spéciale pour les changements d'état
        $data = $this->form->getState();
        
        if (isset($data['etat_facture']) && $data['etat_facture'] === 'payee') {
            if (empty($data['date_paiement_effectif'])) {
                Notification::make()
                    ->title('Date de paiement requise')
                    ->body('La date de paiement effectif est obligatoire pour une facture payée.')
                    ->danger()
                    ->send();
                
                $this->halt();
            }
        }
    }
}