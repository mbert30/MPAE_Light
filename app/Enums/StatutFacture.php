<?php

namespace App\Enums;

enum StatutFacture: string
{
    case BROUILLON = 'brouillon';
    case ENVOYEE = 'envoyee';
    case PAYEE = 'payee';
    case EN_RETARD = 'en_retard';
    case ANNULEE = 'annulee';

    public function label(): string
    {
        return match($this) {
            self::BROUILLON => 'Brouillon',
            self::ENVOYEE => 'Envoyée',
            self::PAYEE => 'Payée',
            self::EN_RETARD => 'En retard',
            self::ANNULEE => 'Annulée',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::BROUILLON => 'gray',
            self::ENVOYEE => 'info',
            self::PAYEE => 'success',
            self::EN_RETARD => 'danger',
            self::ANNULEE => 'warning',
        };
    }
}