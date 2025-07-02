<?php

namespace App\Filament\User\Resources\ProjetResource\Pages;

use App\Filament\User\Resources\ProjetResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateProjet extends CreateRecord
{
    protected static string $resource = ProjetResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Projet créé')
            ->body('Le projet a été créé avec succès.');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // S'assurer que created_at est définie si pas déjà fait
        return $data;
    }
}