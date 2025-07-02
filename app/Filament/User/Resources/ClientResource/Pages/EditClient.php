<?php

namespace App\Filament\User\Resources\ClientResource\Pages;

use App\Filament\User\Resources\ClientResource;
use Filament\Resources\Pages\EditRecord;

class EditClient extends EditRecord
{
    protected static string $resource = ClientResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Charger les données de l'adresse
        if ($this->record->adresse) {
            $data['adresse'] = [
                'ligne1' => $this->record->adresse->ligne1,
                'ligne2' => $this->record->adresse->ligne2,
                'ligne3' => $this->record->adresse->ligne3,
                'ville' => $this->record->adresse->ville,
                'code_postal' => $this->record->adresse->code_postal,
                'pays' => $this->record->adresse->pays,
            ];
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Mettre à jour l'adresse existante
        if (isset($data['adresse']) && $this->record->adresse) {
            $this->record->adresse->update($data['adresse']);
            unset($data['adresse']);
        }

        return $data;
    }
}