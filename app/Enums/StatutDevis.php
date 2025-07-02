<?php

namespace App\Enums;

enum StatutDevis: string
{
    case BROUILLON = 'brouillon';
    case ENVOYE = 'envoye';
    case ACCEPTE = 'accepte';
    case REFUSE = 'refuse';
    case EXPIRE = 'expire';

    public function label(): string
    {
        return match($this) {
            self::BROUILLON => 'Brouillon',
            self::ENVOYE => 'Envoyé',
            self::ACCEPTE => 'Accepté',
            self::REFUSE => 'Refusé',
            self::EXPIRE => 'Expiré',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::BROUILLON => 'gray',
            self::ENVOYE => 'info',
            self::ACCEPTE => 'success',
            self::REFUSE => 'danger',
            self::EXPIRE => 'warning',
        };
    }
}