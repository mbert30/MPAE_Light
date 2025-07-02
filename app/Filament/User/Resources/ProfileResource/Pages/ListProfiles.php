<?php

namespace App\Filament\User\Resources\ProfileResource\Pages;

use App\Filament\User\Resources\ProfileResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListProfiles extends ListRecords
{
    protected static string $resource = ProfileResource::class;

    public function mount(): void
    {
        // Rediriger directement vers l'édition du profil de l'utilisateur connecté
        $this->redirect(ProfileResource::getUrl('edit', ['record' => Auth::id()]));
    }
}