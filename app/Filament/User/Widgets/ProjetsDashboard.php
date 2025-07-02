<?php

namespace App\Filament\User\Widgets;

use App\Models\Projet;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class ProjetsDashboard extends BaseWidget
{
    protected static ?string $heading = 'Projets récents';

    public function table(Table $table): Table
    {
        return $table
            ->query(Projet::query()->whereHas('client', function ($query) {
                    $query->where('id_utilisateur', Auth::id());
                })->latest()->limit(5))
            ->columns([
                Tables\Columns\TextColumn::make('designation')
                    ->label('Projet'),
                Tables\Columns\TextColumn::make('client.designation')
                    ->label('Client'),
                Tables\Columns\BadgeColumn::make('statut')
                    ->colors([
                        'warning' => 'en_attente',
                        'info' => 'en_cours',
                        'success' => 'termine',
                        'danger' => 'annule',
                        'gray' => 'en_pause',
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->date(),
            ]);
    }
}