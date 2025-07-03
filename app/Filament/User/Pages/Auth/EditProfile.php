<?php

namespace App\Filament\User\Pages\Auth;

use App\Models\Adresse;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\EditProfile as BaseEditProfile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class EditProfile extends BaseEditProfile
{
    protected static ?string $title = 'Mon Profil';

    public function form(Form $form): Form
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
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        
                        DatePicker::make('date_naissance')
                            ->label('Date de naissance'),
                        
                        TextInput::make('telephone')
                            ->label('Téléphone')
                            ->maxLength(255),
                    ])->columns(2),

                Section::make('Adresse')
                    ->schema([
                        TextInput::make('adresse.ligne1')
                            ->label('Ligne 1')
                            ->required()
                            ->maxLength(255),
                        
                        TextInput::make('adresse.ligne2')
                            ->label('Ligne 2')
                            ->maxLength(255),
                        
                        TextInput::make('adresse.ville')
                            ->label('Ville')
                            ->required()
                            ->maxLength(255),
                        
                        TextInput::make('adresse.code_postal')
                            ->label('Code postal')
                            ->required()
                            ->maxLength(255),
                        
                        TextInput::make('adresse.pays')
                            ->label('Pays')
                            ->required()
                            ->default('France')
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

                Section::make('Sécurité')
                    ->description('Laissez vide si vous ne souhaitez pas modifier votre mot de passe')
                    ->schema([
                        TextInput::make('current_password')
                            ->label('Mot de passe actuel')
                            ->password()
                            ->dehydrated(false)
                            ->required(fn ($get) => filled($get('password')))
                            ->rule('current_password')
                            ->autocomplete('current-password'),
                        
                        TextInput::make('password')
                            ->label('Nouveau mot de passe')
                            ->password()
                            ->rule(Password::default())
                            ->autocomplete('new-password')
                            ->dehydrated(fn ($state): bool => filled($state))
                            ->dehydrateStateUsing(fn ($state): string => filled($state) ? Hash::make($state) : '')
                            ->live(debounce: 500)
                            ->same('passwordConfirmation')
                            ->validationAttribute('nouveau mot de passe'),
                        
                        TextInput::make('passwordConfirmation')
                            ->label('Confirmer le nouveau mot de passe')
                            ->password()
                            ->required(fn ($get) => filled($get('password')))
                            ->dehydrated(false)
                            ->validationAttribute('confirmation du mot de passe'),
                    ])->columns(1),
            ]);
    }

    public function mount(): void
    {
        parent::mount();
        
        // Charger les données de l'adresse
        $user = Auth::user();
        
        if (!$user) {
            abort(401, 'Utilisateur non connecté');
        }
        
        // Construire les données manuellement
        $data = [
            'name' => $user->name,
            'prenom' => $user->prenom,
            'email' => $user->email,
            'date_naissance' => $user->date_naissance,
            'telephone' => $user->telephone,
            'chiffre_affaire' => $user->chiffre_affaire,
            'taux_charge' => $user->taux_charge,
        ];
        
        if ($user->adresse) {
            $data['adresse'] = [
                'ligne1' => $user->adresse->ligne1,
                'ligne2' => $user->adresse->ligne2,
                'ville' => $user->adresse->ville,
                'code_postal' => $user->adresse->code_postal,
                'pays' => $user->adresse->pays,
            ];
        }
        
        $this->form->fill($data);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = Auth::user();
        
        // Validation du mot de passe si changement demandé
        if (!empty($data['password'])) {
            if (empty($data['current_password']) || !Hash::check($data['current_password'], $user->password)) {
                Notification::make()
                    ->title('Erreur')
                    ->body('Le mot de passe actuel est incorrect.')
                    ->danger()
                    ->send();
                    
                $this->halt();
            }
        }
        
        // Gérer l'adresse
        $adresseData = $data['adresse'] ?? [];
        
        if ($user->adresse) {
            $user->adresse->update($adresseData);
        } else {
            if (!empty($adresseData['ligne1'])) { // Créer seulement si des données d'adresse sont fournies
                $adresse = Adresse::create($adresseData);
                $data['id_adresse'] = $adresse->id_adresse;
            }
        }
        
        // Retirer les données qui ne doivent pas être sauvegardées dans le modèle User
        unset($data['adresse']);
        unset($data['current_password']);
        unset($data['passwordConfirmation']);
        
        // Si pas de nouveau mot de passe, on retire le champ password vide
        if (empty($data['password'])) {
            unset($data['password']);
        }
        
        return $data;
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Profil mis à jour')
            ->body('Vos informations ont été mises à jour avec succès.');
    }

    protected function getRedirectUrl(): string
    {
        return '/'; 
    }
}