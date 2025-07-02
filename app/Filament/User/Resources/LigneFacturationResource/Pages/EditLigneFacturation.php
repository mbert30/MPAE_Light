<?php

namespace App\Filament\User\Resources\LigneFacturationResource\Pages;

use App\Filament\User\Resources\LigneFacturationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLigneFacturation extends EditRecord
{
    protected static string $resource = LigneFacturationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
