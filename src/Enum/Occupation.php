<?php

namespace App\Enum;

enum Occupation: string
{
    case BROKER = 'courtier';
    case LAWYER = 'avocat';
    case CONDOMINIUM_MANAGER = 'syndic';
    case PROPERTY_MANAGER = 'adb';
    case ACCOUNTANT = 'expert';
    case OTHER = 'autre';

    public function label(): string
    {
        return match($this) {
            self::BROKER => 'Courtier / Agent d\'assurance',
            self::LAWYER => 'Avocat',
            self::CONDOMINIUM_MANAGER => 'Syndic de copropriete',
            self::PROPERTY_MANAGER => 'Administrateur de biens',
            self::ACCOUNTANT => 'Expert-comptable',
            self::OTHER => 'Autre profession',
        };
    }
}