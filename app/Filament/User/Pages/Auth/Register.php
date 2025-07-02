<?php

namespace App\Filament\User\Pages\Auth;

use App\Models\Adresse;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Pages\Auth\Register as BaseRegister;
use Illuminate\Database\Eloquent\Model;

class Register extends BaseRegister
{
    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        Section::make('Informations personnelles')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('name')
                                            ->label('Nom')
                                            ->required()
                                            ->maxLength(255)
                                            ->autofocus(),

                                        TextInput::make('prenom')
                                            ->label('Prénom')
                                            ->required()
                                            ->maxLength(255),
                                    ]),

                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('email')
                                            ->label('Email')
                                            ->email()
                                            ->required()
                                            ->maxLength(255)
                                            ->unique($this->getUserModel()),

                                        DatePicker::make('date_naissance')
                                            ->label('Date de naissance')
                                            ->maxDate(now()->subYears(16)) // Au moins 16 ans
                                            ->displayFormat('d/m/Y'),
                                    ]),

                                TextInput::make('telephone')
                                    ->label('Téléphone')
                                    ->tel()
                                    ->maxLength(255),
                            ]),

                        Section::make('Adresse')
                            ->schema([
                                TextInput::make('adresse.ligne1')
                                    ->label('Adresse (ligne 1)')
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('adresse.ligne2')
                                    ->label('Adresse (ligne 2)')
                                    ->maxLength(255),

                                Grid::make(3)
                                    ->schema([
                                        TextInput::make('adresse.ville')
                                            ->label('Ville')
                                            ->required()
                                            ->maxLength(255),

                                        TextInput::make('adresse.code_postal')
                                            ->label('Code postal')
                                            ->required()
                                            ->maxLength(10),

                                        TextInput::make('adresse.pays')
                                            ->label('Pays')
                                            ->required()
                                            ->default('France')
                                            ->maxLength(255),
                                    ]),
                            ]),

                        Section::make('Informations de l\'entreprise')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('chiffre_affaire')
                                            ->label('Chiffre d\'affaire annuel maximum (€)')
                                            ->numeric()
                                            ->default(0)
                                            ->prefix('€'),

                                        TextInput::make('taux_charge')
                                            ->label('Taux de charges (%)')
                                            ->numeric()
                                            ->default(0)
                                            ->suffix('%')
                                            ->step(0.01),
                                    ]),
                            ]),

                        Section::make('Mot de passe')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('password')
                                            ->label('Mot de passe')
                                            ->password()
                                            ->required()
                                            ->minLength(8)
                                            ->same('passwordConfirmation')
                                            ->revealable(),

                                        TextInput::make('passwordConfirmation')
                                            ->label('Confirmer le mot de passe')
                                            ->password()
                                            ->required()
                                            ->minLength(8)
                                            ->dehydrated(false)
                                            ->revealable(),
                                    ]),
                            ]),
                    ])
                    ->statePath('data')
            ),
        ];
    }

    protected function handleRegistration(array $data): Model
    {
        // Créer d'abord l'adresse
        $adresse = Adresse::create([
            'ligne1' => $data['adresse']['ligne1'],
            'ligne2' => $data['adresse']['ligne2'] ?? null,
            'ville' => $data['adresse']['ville'],
            'code_postal' => $data['adresse']['code_postal'],
            'pays' => $data['adresse']['pays'],
        ]);

        // Créer ensuite l'utilisateur
        $user = $this->getUserModel()::create([
            'name' => $data['name'],
            'prenom' => $data['prenom'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'date_naissance' => $data['date_naissance'] ?? null,
            'telephone' => $data['telephone'] ?? null,
            'id_adresse' => $adresse->id_adresse,
            'chiffre_affaire' => $data['chiffre_affaire'] ?? 0,
            'taux_charge' => $data['taux_charge'] ?? 0,
            'est_admin' => false, // Par défaut, pas admin
        ]);

        return $user;
    }
}