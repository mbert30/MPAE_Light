<?php

namespace App\Filament\User\Resources\ClientResource\Pages;

use App\Filament\User\Resources\ClientResource;
use Filament\Resources\Pages\CreateRecord;

class CreateClient extends CreateRecord
{
    protected static string $resource = ClientResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Créer l'adresse d'abord si elle est fournie
        if (isset($data['adresse'])) {
            $adresseData = $data['adresse'];
            unset($data['adresse']);
            
            $adresse = \App\Models\Adresse::create($adresseData);
            $data['id_adresse'] = $adresse->id_adresse;
        }
        
        return $data;
    }
}