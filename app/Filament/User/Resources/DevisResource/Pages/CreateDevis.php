<?php

namespace App\Filament\User\Resources\DevisResource\Pages;

use App\Filament\User\Resources\DevisResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class CreateDevis extends CreateRecord
{
    protected static string $resource = DevisResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Devis créé')
            ->body('Le devis a été créé avec succès.');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // S'assurer que le numéro de devis est défini
        if (empty($data['numero_devis'])) {
            $data['numero_devis'] = $this->getModel()::getNextNumeroDevis(Auth::id());
        }

        return $data;
    }
}