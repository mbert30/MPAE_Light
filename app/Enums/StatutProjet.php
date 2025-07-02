<?php

namespace App\Enums;

enum StatutProjet: string
{
    case EN_ATTENTE = 'en_attente';
    case EN_COURS = 'en_cours';
    case TERMINE = 'termine';
    case ANNULE = 'annule';
    case EN_PAUSE = 'en_pause';

    public function label(): string
    {
        return match($this) {
            self::EN_ATTENTE => 'En attente',
            self::EN_COURS => 'En cours',
            self::TERMINE => 'Terminé',
            self::ANNULE => 'Annulé',
            self::EN_PAUSE => 'En pause',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::EN_ATTENTE => 'warning',
            self::EN_COURS => 'info',
            self::TERMINE => 'success',
            self::ANNULE => 'danger',
            self::EN_PAUSE => 'gray',
        };
    }
}