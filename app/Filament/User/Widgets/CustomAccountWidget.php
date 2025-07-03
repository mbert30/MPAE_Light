<?php

namespace App\Filament\User\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CustomAccountWidget extends Widget
{
    protected static string $view = 'filament.widgets.custom-account';
    
    protected static ?int $sort = -3;
    
    protected static bool $isLazy = false;

    public function getViewData(): array
    {
        $user = Auth::user();
        
        // Configurer Carbon en français
        Carbon::setLocale('fr');
        
        // Format pour "jeudi 3 mars 2025 à 10h00"
        $dateCreation = $user->created_at->translatedFormat('l j F Y \à H\hi');
        
        // Format pour "il y a X mois/jours"
        $derniereModification = $user->updated_at->diffForHumans();
        
        return [
            'user' => $user,
            'dateCreation' => $dateCreation,
            'derniereModification' => $derniereModification,
        ];
    }
}