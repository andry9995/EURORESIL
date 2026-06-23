<?php

namespace App\Enum;

enum CancellationProofType: string
{
    case DEPOSIT = 'deposit';
    case ACCEPTANCE = 'acceptance';
    case REFUSAL = 'refusal';

    public function label(): string
    {
        return match ($this) {
            self::DEPOSIT => 'Dépôt',
            self::ACCEPTANCE => 'Acceptation',
            self::REFUSAL => 'Refus',
        };
    }
}
