<?php

namespace App\Filament\User\Resources\AdresseResource\Pages;

use App\Filament\User\Resources\AdresseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAdresse extends EditRecord
{
    protected static string $resource = AdresseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
