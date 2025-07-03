<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Utilisateurs';
    protected static ?string $pluralLabel = 'Utilisateurs';
    protected static ?string $label = 'Utilisateur';
    
    // Définir cette ressource comme page d'accueil
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informations personnelles')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nom')
                            ->required()
                            ->maxLength(255),
                        
                        TextInput::make('prenom')
                            ->label('Prénom')
                            ->required()
                            ->maxLength(255),
                        
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(User::class, 'email', ignoreRecord: true)
                            ->maxLength(255),
                        
                        DatePicker::make('date_naissance')
                            ->label('Date de naissance'),
                        
                        TextInput::make('telephone')
                            ->label('Téléphone')
                            ->maxLength(255),
                    ])->columns(2),

                Section::make('Informations entreprise')
                    ->schema([
                        TextInput::make('chiffre_affaire')
                            ->label('Chiffre d\'affaires maximum')
                            ->numeric()
                            ->step(0.01)
                            ->suffix('€'),
                        
                        TextInput::make('taux_charge')
                            ->label('Taux de charges')
                            ->numeric()
                            ->step(0.01)
                            ->suffix('%')
                            ->maxValue(100),
                    ])->columns(2),

                Section::make('Permissions')
                    ->schema([
                        Toggle::make('est_admin')
                            ->label('Administrateur')
                            ->helperText('Accorde les droits d\'administration à cet utilisateur')
                            ->disabled(fn ($record) => $record && $record->id === Auth::id())
                            ->dehydrated(fn ($record) => !($record && $record->id === Auth::id())),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('prenom')
                    ->label('Prénom')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('telephone')
                    ->label('Téléphone')
                    ->searchable(),
                
                TextColumn::make('chiffre_affaire')
                    ->label('CA Max')
                    ->money('EUR')
                    ->sortable(),
                
                BooleanColumn::make('est_admin')
                    ->label('Admin')
                    ->sortable(),
                
                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Filter::make('est_admin')
                    ->label('Administrateurs seulement')
                    ->query(fn (Builder $query): Builder => $query->where('est_admin', true)),
                
                Filter::make('created_this_month')
                    ->label('Créés ce mois-ci')
                    ->query(fn (Builder $query): Builder => $query->whereMonth('created_at', now()->month)),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => $record->id !== Auth::id() || Auth::user()->est_admin),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Supprimer cet utilisateur')
                    ->modalDescription('Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action est irréversible.')
                    ->modalSubmitActionLabel('Supprimer')
                    ->visible(fn ($record) => $record->id !== Auth::id()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    // Désactiver la création depuis le panel admin
    public static function canCreate(): bool
    {
        return false;
    }
}