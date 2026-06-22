<?php

namespace App\Enum;

enum CancellationProofType: string
{
    case DEPOSIT = 'deposit';
    case RECEIPT = 'receipt';
    case WITHDRAWAL = 'withdrawal';
    case NEGLIGENCE = 'negligence';

    public function label(): string
    {
        return match ($this) {
            self::DEPOSIT => 'Dépôt',
            self::RECEIPT => 'Réception',
            self::WITHDRAWAL => 'Retrait',
            self::NEGLIGENCE => 'Négligence',
        };
    }
}
