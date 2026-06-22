<?php

namespace App\Enum;

enum DocumentType : string
{
    case INVOICE = 'invoice';
    case LETTER = 'letter';
}
