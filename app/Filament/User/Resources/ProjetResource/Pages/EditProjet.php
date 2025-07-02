<?php

namespace App\Filament\User\Resources\ProjetResource\Pages;

use App\Filament\User\Resources\ProjetResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditProjet extends EditRecord
{
    protected static string $resource = ProjetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('Voir')
                ->icon('heroicon-m-eye'),
            Actions\DeleteAction::make()
                ->label('Supprimer')
                ->icon('heroicon-m-trash'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Projet modifié')
            ->body('Les modifications ont été enregistrées avec succès.');
    }
}