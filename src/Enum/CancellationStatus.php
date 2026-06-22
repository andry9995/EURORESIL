<?php

namespace App\Enum;

enum CancellationStatus: string
{
    case DRAFT = 'draft';
    case SIGNING = 'signing';
    case SENDING = 'sending';
    case SENT = 'sent';
    case RECEIVED = 'received';
    case FAILED = 'failed';

    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'Brouillon',
            self::SENDING => 'Envoi en cours',
            self::SENT => 'Envoyée',
            self::RECEIVED => 'Reçue',
            self::FAILED => 'Échouée',
        };
    }
}