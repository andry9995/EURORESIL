<?php

namespace App\Enum;

enum DocumentType : string
{
    case INVOICE = 'invoice';
    case LETTER = 'letter';
    case DEPOSIT_PROOF = 'deposit_proof';
    case ACCEPTANCE_PROOF = 'acceptance_proof';
    case REFUSAL_PROOF = 'refusal_proof';
}
