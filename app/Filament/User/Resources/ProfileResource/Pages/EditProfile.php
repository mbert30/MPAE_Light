<?php

namespace App\Filament\User\Resources\ProfileResource\Pages;

use App\Filament\User\Resources\ProfileResource;
use App\Models\Adresse;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class EditProfile extends EditRecord
{
    protected static string $resource = ProfileResource::class;
    
    protected static ?string $title = 'Mon Profil';

    public function mount(int | string $record): void
    {
        // S'assurer que l'utilisateur ne peut modifier que son propre profil
        if ($record != Auth::id()) {
            abort(403, 'Vous ne pouvez modifier que votre propre profil.');
        }
        
        parent::mount($record);
        
        // Charger les données de l'adresse
        $user = $this->record;
        if ($user->adresse) {
            $this->form->fill([
                ...$this->record->toArray(),
                'adresse' => [
                    'ligne1' => $user->adresse->ligne1,
                    'ligne2' => $user->adresse->ligne2,
                    'ville' => $user->adresse->ville,
                    'code_postal' => $user->adresse->code_postal,
                    'pays' => $user->adresse->pays,
                ],
            ]);
        }
    }

    protected function beforeSave(): void
    {
        $data = $this->form->getState();
        
        // Validation du mot de passe si changement demandé
        if (!empty($data['new_password'])) {
            if (empty($data['current_password']) || !Hash::check($data['current_password'], $this->record->password)) {
                Notification::make()
                    ->title('Erreur')
                    ->body('Le mot de passe actuel est incorrect.')
                    ->danger()
                    ->send();
                    
                $this->halt();
            }
            
            // Mettre à jour le mot de passe
            $this->record->password = Hash::make($data['new_password']);
        }
        
        // Gérer l'adresse
        if ($this->record->adresse) {
            $this->record->adresse->update([
                'ligne1' => $data['adresse']['ligne1'],
                'ligne2' => $data['adresse']['ligne2'] ?? null,
                'ville' => $data['adresse']['ville'],
                'code_postal' => $data['adresse']['code_postal'],
                'pays' => $data['adresse']['pays'],
            ]);
        } else {
            $adresse = Adresse::create([
                'ligne1' => $data['adresse']['ligne1'],
                'ligne2' => $data['adresse']['ligne2'] ?? null,
                'ville' => $data['adresse']['ville'],
                'code_postal' => $data['adresse']['code_postal'],
                'pays' => $data['adresse']['pays'],
            ]);
            $this->record->id_adresse = $adresse->id_adresse;
        }
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
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }
}