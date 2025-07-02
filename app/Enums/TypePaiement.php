<?php

namespace App\Enums;

enum TypePaiement: string
{
    case VIREMENT = 'virement';
    case CHEQUE = 'cheque';
    case ESPECES = 'especes';
    case CARTE = 'carte';
    case PAYPAL = 'paypal';
    case AUTRE = 'autre';

    public function label(): string
    {
        return match($this) {
            self::VIREMENT => 'Virement bancaire',
            self::CHEQUE => 'Chèque',
            self::ESPECES => 'Espèces',
            self::CARTE => 'Carte bancaire',
            self::PAYPAL => 'PayPal',
            self::AUTRE => 'Autre',
        };
    }
}