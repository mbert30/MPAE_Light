<?php

namespace App\Models;

use App\Services\FactureExcelExport;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasFrenchDates;

class Facture extends Model
{
    use HasFactory;
    use HasFrenchDates;

    protected $table = 'factures';
    protected $primaryKey = 'id_facture';

    protected $fillable = [
        'numero_facture',
        'id_devis',
        'etat_facture',
        'taux_tva',
        'date_edition',
        'date_paiement_limite',
        'type_paiement',
        'date_paiement_effectif',
        'note',
    ];

    protected $casts = [
        'date_edition' => 'date',
        'date_paiement_limite' => 'date',
        'date_paiement_effectif' => 'date',
        'taux_tva' => 'decimal:2',
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
     * Relation avec les lignes de facturation
     */
    public function lignesFacturation(): HasMany
    {
        return $this->hasMany(LigneFacturation::class, 'id_facture', 'id_facture')->orderBy('ordre');
    }

    public function ligneFacturations(): HasMany
    {
        return $this->lignesFacturation();
    }

    /**
     * Calcule le montant total HT de la facture
     */
    public function getMontantTotalHtAttribute(): float
    {
        return $this->lignesFacturation->sum(function ($ligne) {
            return $ligne->prix_unitaire * $ligne->quantite;
        });
    }

    /**
     * Calcule le montant de la TVA
     */
    public function getMontantTvaAttribute(): float
    {
        return $this->montant_total_ht * (($this->taux_tva ?? 20.00) / 100);
    }

    /**
     * Calcule le montant TTC de la facture
     */
    public function getMontantTotalTtcAttribute(): float
    {
        return $this->montant_total_ht + $this->montant_tva;
    }

    /**
     * Alias pour compatibilité (montant total = montant HT)
     */
    public function getMontantTotalAttribute(): float
    {
        return $this->getMontantTotalHtAttribute();
    }

    /**
     * Vérifie si la facture est en retard (calcul dynamique)
     */
    public function getEstEnRetardAttribute(): bool
    {
        return $this->etat_facture === 'envoyee' 
            && $this->date_paiement_limite 
            && $this->date_paiement_limite->isPast();
    }

    /**
     * Génère le prochain numéro de facture disponible pour l'utilisateur
     */
    public static function getNextNumeroFacture(int $userId): int
    {
        // Récupérer le dernier numéro de facture pour cet utilisateur
        $lastNumero = static::whereHas('devis.projet.client', function ($query) use ($userId) {
            $query->where('id_utilisateur', $userId);
        })->max('numero_facture');

        return ($lastNumero ?? 0) + 1;
    }

    /**
     * Récupère le dernier numéro de facture envoyée ou payée pour l'utilisateur
     */
    public static function getLastSentOrPaidNumeroFacture(int $userId): int
    {
        return static::whereHas('devis.projet.client', function ($query) use ($userId) {
            $query->where('id_utilisateur', $userId);
        })
        ->whereIn('etat_facture', ['envoyee', 'payee'])
        ->max('numero_facture') ?? 0;
    }

    /**
     * Vérifie l'unicité du numéro de facture pour l'utilisateur
     */
    public static function isUniqueNumeroFacture(int $numeroFacture, int $userId, ?int $excludeFactureId = null): bool
    {
        $query = static::whereHas('devis.projet.client', function ($subQuery) use ($userId) {
            $subQuery->where('id_utilisateur', $userId);
        })
        ->where('numero_facture', $numeroFacture);

        if ($excludeFactureId !== null) {
            $query->where('id_facture', '!=', $excludeFactureId);
        }

        return !$query->exists();
    }

    /**
     * Valide un numéro de facture (unicité + contrainte de séquence)
     */
    public static function validateNumeroFacture(int $numeroFacture, int $userId, ?int $excludeFactureId = null): array
    {
        $errors = [];

        // Vérifier l'unicité
        if (!static::isUniqueNumeroFacture($numeroFacture, $userId, $excludeFactureId)) {
            $errors[] = "Ce numéro de facture est déjà utilisé.";
        }

        // Vérifier la contrainte de séquence
        $lastSentOrPaid = static::getLastSentOrPaidNumeroFacture($userId);
        if ($lastSentOrPaid > 0 && $numeroFacture <= $lastSentOrPaid) {
            $errors[] = "Le numéro de facture doit être supérieur à {$lastSentOrPaid} (dernière facture envoyée ou payée).";
        }

        return $errors;
    }

    /**
     * Vérifie si la facture peut être modifiée
     */
    public function canBeModified(): bool
    {
        return $this->etat_facture === 'brouillon'; 
    }

    public function canBeSetToDraft(): bool
    {
        return $this->etat_facture === 'envoyee';
    }

    /**
     * Vérifie si la facture peut être supprimée
     */
    public function canBeDeleted(): bool
    {
        return $this->etat_facture === 'brouillon';
    }

    /**
     * Vérifie si la facture peut être envoyée
     */
    public function canBeSent(): bool
    {
        return $this->etat_facture === 'brouillon' 
            && $this->lignesFacturation->count() > 0
            && $this->devis->statut === 'accepte';
    }

    /**
     * Vérifie si la facture peut être marquée comme payée
     */
    public function canBePaid(): bool
    {
        return $this->etat_facture === 'envoyee';
    }

    /**
     * Scope pour filtrer les factures en retard
     */
    public function scopeEnRetard($query)
    {
        return $query->where('etat_facture', 'envoyee')
            ->where('date_paiement_limite', '<', now());
    }

    /**
     * Scope pour filtrer par utilisateur
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->whereHas('devis.projet.client', function ($q) use ($userId) {
            $q->where('id_utilisateur', $userId);
        });
    }

    public function downloadExcel()
    {
        $exporter = new FactureExcelExport($this);
        return $exporter->download();
    }

    /**
     * Boot method pour la gestion automatique
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($facture) {
            if (empty($facture->numero_facture)) {
                $userId = $facture->devis->projet->client->id_utilisateur;
                $facture->numero_facture = static::getNextNumeroFacture($userId);
            }

            if (empty($facture->taux_tva) && $facture->devis) {
                $facture->taux_tva = $facture->devis->taux_tva;
            }

            if (empty($facture->date_edition)) {
                $facture->date_edition = now()->toDateString();
            }
        });

        static::created(function ($facture) {
            if ($facture->etat_facture !== 'brouillon' && $facture->lignesFacturation->count() === 0) {
                throw new \Exception('Une facture ne peut être envoyée ou payée sans lignes de facturation.');
            }
        });

        static::updating(function ($facture) {

        });
    }
}