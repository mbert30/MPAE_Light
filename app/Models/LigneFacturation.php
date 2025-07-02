<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LigneFacturation extends Model
{
    use HasFactory;

    protected $table = 'ligne_facturations';
    protected $primaryKey = 'id_ligne_facturation';

    protected $fillable = [
        'id_facture',
        'libelle',
        'prix_unitaire',
        'quantite',
        'ordre',
    ];

    protected $casts = [
        'prix_unitaire' => 'decimal:2',
        'quantite' => 'integer',
        'ordre' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relation avec la facture
     */
    public function facture(): BelongsTo
    {
        return $this->belongsTo(Facture::class, 'id_facture', 'id_facture');
    }

    /**
     * Calcule le montant total de la ligne
     */
    public function getMontantTotalAttribute(): float
    {
        return $this->prix_unitaire * $this->quantite;
    }

    /**
     * Scope pour ordonner par ordre
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('ordre');
    }

    /**
     * Boot method pour gestion automatique de l'ordre
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ligne) {
            // Définir l'ordre automatiquement si non fourni
            if (empty($ligne->ordre)) {
                $maxOrdre = static::where('id_facture', $ligne->id_facture)->max('ordre') ?? 0;
                $ligne->ordre = $maxOrdre + 1;
            }
        });
    }
}