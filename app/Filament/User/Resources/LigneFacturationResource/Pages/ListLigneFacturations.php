<?php

namespace App\Filament\User\Resources\LigneFacturationResource\Pages;

use App\Filament\User\Resources\LigneFacturationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLigneFacturations extends ListRecords
{
    protected static string $resource = LigneFacturationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
