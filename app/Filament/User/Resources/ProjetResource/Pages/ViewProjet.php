<?php

namespace App\Filament\User\Resources\ProjetResource\Pages;

use App\Filament\User\Resources\ProjetResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewProjet extends ViewRecord
{
    protected static string $resource = ProjetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Modifier')
                ->icon('heroicon-m-pencil-square'),
            Actions\DeleteAction::make()
                ->label('Supprimer')
                ->icon('heroicon-m-trash'),
        ];
    }

    public function getTitle(): string
    {
        return "Projet : {$this->getRecord()->designation}";
    }
}