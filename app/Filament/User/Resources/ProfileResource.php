<?php

namespace App\Filament\User\Resources;

use App\Models\User;
use App\Models\Adresse;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';
    
    protected static ?string $navigationLabel = 'Mon Profil';
    
    protected static ?string $modelLabel = 'profil';
    
    protected static ?string $pluralModelLabel = 'profil';

    protected static ?int $navigationSort = 99;
    
    // Désactiver la création/suppression
    protected static bool $shouldRegisterNavigation = true;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations personnelles')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nom')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('prenom')
                                    ->label('Prénom')
                                    ->required()
                                    ->maxLength(255),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('email')
                                    ->label('Email')
                                    ->email()
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\DatePicker::make('date_naissance')
                                    ->label('Date de naissance')
                                    ->maxDate(now()->subYears(16))
                                    ->displayFormat('d/m/Y'),
                            ]),

                        Forms\Components\TextInput::make('telephone')
                            ->label('Téléphone')
                            ->tel()
                            ->maxLength(255),
                    ]),

                Forms\Components\Section::make('Adresse')
                    ->schema([
                        Forms\Components\TextInput::make('adresse.ligne1')
                            ->label('Adresse (ligne 1)')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('adresse.ligne2')
                            ->label('Adresse (ligne 2)')
                            ->maxLength(255),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('adresse.ville')
                                    ->label('Ville')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('adresse.code_postal')
                                    ->label('Code postal')
                                    ->required()
                                    ->maxLength(10),

                                Forms\Components\TextInput::make('adresse.pays')
                                    ->label('Pays')
                                    ->required()
                                    ->default('France')
                                    ->maxLength(255),
                            ]),
                    ]),

                Forms\Components\Section::make('Informations de l\'entreprise')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('chiffre_affaire')
                                    ->label('Chiffre d\'affaire annuel maximum (€)')
                                    ->numeric()
                                    ->default(0)
                                    ->prefix('€'),

                                Forms\Components\TextInput::make('taux_charge')
                                    ->label('Taux de charges (%)')
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('%')
                                    ->step(0.01),
                            ]),
                    ]),

                Forms\Components\Section::make('Changer le mot de passe')
                    ->schema([
                        Forms\Components\TextInput::make('current_password')
                            ->label('Mot de passe actuel')
                            ->password()
                            ->revealable()
                            ->dehydrated(false),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('new_password')
                                    ->label('Nouveau mot de passe')
                                    ->password()
                                    ->minLength(8)
                                    ->revealable()
                                    ->dehydrated(false),

                                Forms\Components\TextInput::make('new_password_confirmation')
                                    ->label('Confirmer le nouveau mot de passe')
                                    ->password()
                                    ->revealable()
                                    ->same('new_password')
                                    ->dehydrated(false),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom'),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Modifier mon profil'),
            ])
            ->paginated(false);
    }

    public static function getEloquentQuery(): Builder
    {
        // Ne montrer que l'utilisateur connecté
        return parent::getEloquentQuery()->where('id', Auth::id());
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\User\Resources\ProfileResource\Pages\ListProfiles::route('/'),
            'edit' => \App\Filament\User\Resources\ProfileResource\Pages\EditProfile::route('/{record}/edit'),
        ];
    }
    
    // Désactiver les actions de création/suppression
    public static function canCreate(): bool
    {
        return false;
    }
    
    public static function canDelete($record): bool
    {
        return false;
    }
}