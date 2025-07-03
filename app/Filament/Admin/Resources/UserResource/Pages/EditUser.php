<?php

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Supprimer cet utilisateur')
                ->modalDescription('Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action supprimera également tous ses clients, projets, devis et factures.')
                ->modalSubmitActionLabel('Supprimer')
                ->visible(fn () => $this->record->id !== Auth::id())
                ->before(function () {
                    if ($this->record->id === Auth::id()) {
                        Notification::make()
                            ->danger()
                            ->title('Action interdite')
                            ->body('Vous ne pouvez pas supprimer votre propre compte.')
                            ->send();
                        
                        $this->halt();
                    }
                }),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Empêcher un admin de se retirer ses propres droits
        if ($this->record->id === Auth::id() && isset($data['est_admin']) && !$data['est_admin']) {
            Notification::make()
                ->danger()
                ->title('Action interdite')
                ->body('Vous ne pouvez pas vous retirer vos propres droits d\'administrateur.')
                ->send();
            
            $data['est_admin'] = true; // Forcer à garder les droits admin
        }
        
        return $data;
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Utilisateur modifié')
            ->body('Les informations de l\'utilisateur ont été mises à jour avec succès.');
    }
}