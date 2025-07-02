<?php

namespace App\Filament\User\Resources\DevisResource\Pages;

use App\Filament\User\Resources\DevisResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditDevis extends EditRecord
{
    protected static string $resource = DevisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->before(function (Actions\DeleteAction $action) {
                    if (!$this->record->canBeDeleted()) {
                        Notification::make()
                            ->title('Suppression impossible')
                            ->body('Ce devis ne peut pas être supprimé car il a un statut qui ne le permet pas ou il est lié à une facture.')
                            ->danger()
                            ->send();
                        
                        $action->cancel();
                    }
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
            ->title('Devis mis à jour')
            ->body('Le devis a été mis à jour avec succès.');
    }
}