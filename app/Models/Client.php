<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class Client extends Model
{
    use HasFactory;

    protected $table = 'clients';
    protected $primaryKey = 'id_client';

    protected $fillable = [
        'id_utilisateur',
        'designation',
        'id_adresse',
        'email',
        'telephone',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($client) {
            $client->id_utilisateur = Auth::id();
        });
    }

    // Relations
    public function utilisateur()
    {
        return $this->belongsTo(User::class, 'id_utilisateur');
    }

    public function adresse()
    {
        return $this->belongsTo(Adresse::class, 'id_adresse', 'id_adresse');
    }

    public function projets()
    {
        return $this->hasMany(Projet::class, 'id_client', 'id_client');
    }

    /**
     * Projets en cours pour ce client
     */
    public function projetsEnCours(): HasMany
    {
        return $this->projets()->whereIn('statut', [
            'prospect', 
            'devis_envoye', 
            'devis_accepte', 
            'demarre'
        ]);
    }

    // Scopes
    public function scopeForUser($query, $userId)
    {
        return $query->where('id_utilisateur', $userId);
    }

    // Accesseurs
    public function getNombreProjetAttribute()
    {
        return $this->projets()->count();
    }

    /**
     * Nombre de projets en cours pour ce client
     */
    public function getNombreProjetsEnCoursAttribute(): int
    {
        return $this->projetsEnCours()->count();
    }

    public function getChiffreAffaireAttribute()
    {
        return $this->projets()
            ->join('devis', 'projets.id_projet', '=', 'devis.id_projet')
            ->join('factures', 'devis.id_devis', '=', 'factures.id_devis')
            ->join('ligne_facturations', 'factures.id_facture', '=', 'ligne_facturations.id_facture')
            ->where('factures.etat_facture', 'payee')
            ->sum(DB::raw('ligne_facturations.prix_unitaire * ligne_facturations.quantite'));
    }
}