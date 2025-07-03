<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasFrenchDates;

class LigneDevis extends Model
{
    use HasFactory;
    use HasFrenchDates;

    protected $table = 'ligne_devis';
    protected $primaryKey = 'id_ligne_devis';

    protected $fillable = [
        'id_devis',
        'libelle',
        'prix_unitaire',
        'quantite',
        'ordre',
    ];

    protected $casts = [
        'prix_unitaire' => 'decimal:2',
        'quantite' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relation avec le devis
     */
    public function devis(): BelongsTo
    {
        return $this->belongsTo(Devis::class, 'id_devis', 'id_devis');
    }

    /**
     * Calcule le montant total de la ligne
     */
    public function getMontantTotalAttribute(): float
    {
        return $this->prix_unitaire * $this->quantite;
    }

    /**
     * Formate le prix unitaire pour l'affichage
     */
    public function getPrixUnitaireFormateAttribute(): string
    {
        return number_format($this->prix_unitaire, 2, ',', ' ') . ' €';
    }

    /**
     * Formate le montant total pour l'affichage
     */
    public function getMontantTotalFormateAttribute(): string
    {
        return number_format($this->montant_total, 2, ',', ' ') . ' €';
    }
}