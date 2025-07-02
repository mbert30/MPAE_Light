<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'prenom',
        'email',
        'password',
        'date_naissance',
        'id_adresse',
        'telephone',
        'chiffre_affaire',
        'taux_charge',
        'est_admin',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'date_naissance' => 'date',
            'chiffre_affaire' => 'decimal:2',
            'taux_charge' => 'decimal:2',
            'est_admin' => 'boolean',
        ];
    }

    // Relations
    public function adresse()
    {
        return $this->belongsTo(Adresse::class, 'id_adresse', 'id_adresse');
    }

    public function clients()
    {
        return $this->hasMany(Client::class, 'id_utilisateur');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'admin') {
            return $this->est_admin == 1 || $this->est_admin === true;
        }

        if ($panel->getId() === 'user') {
            return true;
        }
        return false;
    }

    // Accesseurs
    public function getNomCompletAttribute()
    {
        return $this->prenom . ' ' . $this->name;
    }
}