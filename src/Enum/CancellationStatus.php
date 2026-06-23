<?php

namespace App\Enum;

enum CancellationStatus: string
{
    case DRAFT = 'draft';
    case SENDING = 'sending';
    case SENT = 'sent';
    case FAILED = 'failed';
    case ACCEPTED = 'accepted';
    case REFUSED = 'refused';

    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'Brouillon',
            self::SENDING => 'Envoi en cours',
            self::SENT => 'Envoyée',
            self::FAILED => 'Échouée',
            self::ACCEPTED => 'Acceptée',
            self::REFUSED => 'Réfusée',
        };
    }
}