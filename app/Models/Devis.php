<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Services\DevisExcelExport;
use Barryvdh\DomPDF\Facade\Pdf;

class Devis extends Model
{
    use HasFactory;

    protected $table = 'devis';
    protected $primaryKey = 'id_devis';

    protected $fillable = [
        'id_projet',
        'numero_devis',
        'statut',
        'date_validite',
        'note',
        'taux_tva', // NOUVEAU CHAMP AJOUTÉ
    ];

    protected $casts = [
        'date_validite' => 'date',  
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'taux_tva' => 'decimal:2', // NOUVEAU CAST AJOUTÉ
    ];

    /**
     * Relation avec le projet
     */
    public function projet(): BelongsTo
    {
        return $this->belongsTo(Projet::class, 'id_projet', 'id_projet');
    }

    /**
     * Relation avec les lignes de devis
     */
    public function lignesDevis(): HasMany
    {
        return $this->hasMany(LigneDevis::class, 'id_devis', 'id_devis')->orderBy('ordre');
    }

    public function factures(): HasMany
    {
        return $this->hasMany(Facture::class, 'id_devis', 'id_devis');
    }

    /**
     * Calcule le montant total HT du devis
     */
    public function getMontantTotalAttribute(): float
    {
        return $this->lignesDevis->sum(function ($ligne) {
            return $ligne->prix_unitaire * $ligne->quantite;
        });
    }

    /**
     * Alias pour le montant HT (pour clarifier)
     */
    public function getMontantTotalHtAttribute(): float
    {
        return $this->getMontantTotalAttribute();
    }

    /**
     * Calcule le montant de la TVA
     */
    public function getMontantTvaAttribute(): float
    {
        return $this->montant_total_ht * (($this->taux_tva ?? 20.00) / 100);
    }

    /**
     * Calcule le montant TTC du devis
     */
    public function getMontantTotalTtcAttribute(): float
    {
        return $this->montant_total_ht + $this->montant_tva;
    }

    /**
     * Génère le prochain numéro de devis disponible pour l'utilisateur
     */
    public static function getNextNumeroDevis(int $userId): int
    {
        // Récupérer le dernier numéro de devis pour cet utilisateur
        $lastNumero = static::whereHas('projet.client', function ($query) use ($userId) {
            $query->where('id_utilisateur', $userId);
        })->max('numero_devis');

        return ($lastNumero ?? 0) + 1;
    }

    /**
     * Récupère le dernier numéro de devis envoyé pour l'utilisateur
     */
    public static function getLastSentNumeroDevis(int $userId): int
    {
        return static::whereHas('projet.client', function ($query) use ($userId) {
            $query->where('id_utilisateur', $userId);
        })
        ->where('statut', 'envoye')
        ->max('numero_devis') ?? 0;
    }

    /**
     * Valide qu'un numéro de devis est autorisé pour l'utilisateur
     */
    public static function isValidNumeroDevis(int $numeroDevis, int $userId, ?int $excludeDevisId = null): bool
    {
        $lastSentNumero = static::getLastSentNumeroDevis($userId);
        return $numeroDevis > $lastSentNumero;
    }

    /**
     * Vérifie l'unicité du numéro de devis pour l'utilisateur
     */
    public static function isUniqueNumeroDevis(int $numeroDevis, int $userId, ?int $excludeDevisId = null): bool
    {
        $query = static::whereHas('projet.client', function ($subQuery) use ($userId) {
            $subQuery->where('id_utilisateur', $userId);
        })
        ->where('numero_devis', $numeroDevis);

        if ($excludeDevisId !== null) {
            $query->where('id_devis', '!=', $excludeDevisId);
        }

        return !$query->exists();
    }

    /**
     * Valide un numéro de devis (unicité + contrainte de séquence)
     */
    public static function validateNumeroDevis(int $numeroDevis, int $userId, ?int $excludeDevisId = null): array
    {
        $errors = [];

        if (!static::isUniqueNumeroDevis($numeroDevis, $userId, $excludeDevisId)) {
            $errors[] = "Ce numéro de devis est déjà utilisé.";
        }

        $lastSent = static::getLastSentNumeroDevis($userId);
        if ($lastSent > 0 && $numeroDevis <= $lastSent) {
            $errors[] = "Le numéro de devis doit être supérieur à {$lastSent} (dernier devis envoyé).";
        }
        return $errors;
    }

    /**
     * Vérifie si le devis est expiré
     */
    public function getEstExpireAttribute(): bool
    {
        return $this->date_validite && $this->date_validite->isPast() && in_array($this->statut, ['brouillon', 'envoye']);
    }

    /**
     * Vérifie si le devis peut être modifié
     */
    public function canBeModified(): bool
    {
        return !in_array($this->statut, ['accepte', 'refuse', 'expire']);
    }

    /**
     * Vérifie si le devis peut être supprimé
     */
    public function canBeDeleted(): bool
    {
        return $this->statut === 'brouillon' && !$this->facture;
    }

    /**
     * Boot method pour définir le numéro et le taux de TVA automatiquement
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($devis) {
            if (empty($devis->numero_devis)) {
                $userId = $devis->projet->client->id_utilisateur;
                $devis->numero_devis = static::getNextNumeroDevis($userId);
            }
            
            // Définir le taux de TVA par défaut si non spécifié
            if (empty($devis->taux_tva)) {
                $devis->taux_tva = 20.00;
            }
        });

        static::updating(function ($devis) {
            if ($devis->isDirty('statut') && $devis->statut === 'envoye') {
                $userId = $devis->projet->client->id_utilisateur;
                
                $lastSent = static::getLastSentNumeroDevis($userId);
                if ($lastSent > 0 && $devis->numero_devis <= $lastSent) {
                    throw new \Exception("Le numéro de devis doit être supérieur à {$lastSent} (dernier devis envoyé).");
                }
            }
        });
    }

    /**
     * Télécharge le devis au format Excel
     */
    public function downloadExcel()
    {
        $exporter = new DevisExcelExport($this);
        return $exporter->download();
    }
}