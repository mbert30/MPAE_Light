<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Adresse extends Model
{
    use HasFactory;

    protected $table = 'adresses';
    protected $primaryKey = 'id_adresse';

    protected $fillable = [
        'ligne1',
        'ligne2',
        'ligne3',
        'ville',
        'code_postal',
        'pays',
    ];

    // Relations
    public function users()
    {
        return $this->hasMany(User::class, 'id_adresse', 'id_adresse');
    }

    public function clients()
    {
        return $this->hasMany(Client::class, 'id_adresse', 'id_adresse');
    }

    // Accesseur pour adresse complète
    public function getAdresseCompleteAttribute()
    {
        $adresse = $this->ligne1;
        if ($this->ligne2) $adresse .= ', ' . $this->ligne2;
        if ($this->ligne3) $adresse .= ', ' . $this->ligne3;
        $adresse .= ', ' . $this->code_postal . ' ' . $this->ville;
        $adresse .= ', ' . $this->pays;
        
        return $adresse;
    }
}