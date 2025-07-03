<?php

namespace App\Filament\Admin\Pages\Auth;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\Login as BaseLogin;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

class AdminLogin extends BaseLogin
{
    public function mount(): void
    {
        parent::mount();
        
        // Si l'utilisateur est déjà connecté mais n'est pas admin, rediriger vers le dashboard utilisateur
        if (Auth::check() && !Auth::user()->est_admin) {
            $this->redirect('/');
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getEmailFormComponent()
                    ->label('Adresse email'),
                $this->getPasswordFormComponent()
                    ->label('Mot de passe'),
                $this->getRememberFormComponent(),
            ])
            ->statePath('data');
    }

    public function authenticate(): ?LoginResponse
    {
        try {
            $data = $this->form->getState();

            // Tentative de connexion standard
            if (!Auth::guard('web')->attempt([
                'email' => $data['email'],
                'password' => $data['password'],
            ], $data['remember'] ?? false)) {
                throw ValidationException::withMessages([
                    'data.email' => 'Ces identifiants ne correspondent à aucun compte.',
                ]);
            }

            // Vérification des droits admin
            $user = Auth::user();
            if (!$user->est_admin) {
                Auth::logout();
                throw ValidationException::withMessages([
                    'data.email' => 'Accès refusé. Vous devez avoir les droits d\'administrateur pour accéder à cette section.',
                ]);
            }

            session()->regenerate();

            return app(LoginResponse::class);

        } catch (ValidationException $exception) {
            throw $exception;
        }
    }

    protected function getEmailFormComponent(): TextInput
    {
        return TextInput::make('email')
            ->label('Email')
            ->email()
            ->required()
            ->autocomplete()
            ->autofocus()
            ->extraInputAttributes(['tabindex' => 1]);
    }

    protected function getPasswordFormComponent(): TextInput
    {
        return TextInput::make('password')
            ->label('Mot de passe')
            ->password()
            ->required()
            ->extraInputAttributes(['tabindex' => 2]);
    }
}