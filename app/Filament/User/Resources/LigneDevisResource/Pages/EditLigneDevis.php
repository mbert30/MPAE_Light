<?php

namespace App\Filament\User\Resources\LigneDevisResource\Pages;

use App\Filament\User\Resources\LigneDevisResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLigneDevis extends EditRecord
{
    protected static string $resource = LigneDevisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
