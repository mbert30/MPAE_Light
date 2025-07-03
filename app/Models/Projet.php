<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasFrenchDates;

class Projet extends Model
{
    use HasFactory;
    use HasFrenchDates;

    protected $table = 'projets';
    protected $primaryKey = 'id_projet';

    protected $fillable = [
        'id_client',
        'designation',
        'statut',
    ];

    protected $casts = [
        'statut' => 'string',
    ];

    /**
     * Relation avec le client
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'id_client', 'id_client');
    }

    /**
     * Méthodes utilitaires pour les statuts
     */
    public static function getStatutsLabels(): array
    {
        return [
            'prospect' => 'Prospect',
            'devis_envoye' => 'Devis envoyé',
            'devis_accepte' => 'Devis accepté',
            'demarre' => 'Démarré',
            'termine' => 'Terminé',
            'annule' => 'Annulé',
        ];
    }

    public static function getStatutsColors(): array
    {
        return [
            'prospect' => 'info',
            'devis_envoye' => 'warning',
            'devis_accepte' => 'success',
            'demarre' => 'primary',
            'termine' => 'success',
            'annule' => 'danger',
        ];
    }

    public function getStatutLabelAttribute(): string
    {
        return self::getStatutsLabels()[$this->statut] ?? $this->statut;
    }

    public function getStatutColorAttribute(): string
    {
        return self::getStatutsColors()[$this->statut] ?? 'gray';
    }

    /**
     * Scopes
     */
    public function scopeByStatut($query, string $statut)
    {
        return $query->where('statut', $statut);
    }

    public function scopeByClient($query, int $clientId)
    {
        return $query->where('id_client', $clientId);
    }

    public function scopeEnCours($query)
    {
        return $query->whereIn('statut', ['prospect', 'devis_envoye', 'devis_accepte', 'demarre']);
    }
}