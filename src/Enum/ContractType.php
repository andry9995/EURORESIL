<?php

namespace App\Enum;

enum ContractType: string
{
    case CAR = 'car';
    case HOMEOWNER = 'homeowner';
    case TENANT = 'tenant';
    case HEALTH = 'health';
    case MOTORCYCLE = 'motorcycle';
    case OTHER_MOTOR_VEHICLE = 'other_motor_vehicle';
    case FAMILY_PROTECTION = 'family_protection';
    case PROFESSIONAL_MULTI_RISK = 'professional_multi_risk';
    case LIFE_AND_DISABILITY = 'life_and_disability';
    case LEGAL_PROTECTION = 'legal_protection';
    case BORROWER = 'borrower';
    case PET = 'pet';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::CAR => 'Assurance auto',
            self::HOMEOWNER => 'Assurance habitation (propriétaire)',
            self::TENANT => 'Assurance habitation (locataire)',
            self::HEALTH => 'Complémentaire santé',
            self::MOTORCYCLE => 'Assurance moto',
            self::OTHER_MOTOR_VEHICLE => 'Autre véhicule terrestre à moteur',
            self::FAMILY_PROTECTION => 'Protection familiale',
            self::PROFESSIONAL_MULTI_RISK => 'Multirisque professionnelle',
            self::LIFE_AND_DISABILITY => 'Prévoyance',
            self::LEGAL_PROTECTION => 'Protection juridique',
            self::BORROWER => 'Assurance emprunteur',
            self::PET => 'Assurance animaux',
            self::OTHER => 'Autre contrat',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::CAR => 'car',
            self::HOMEOWNER,
            self::TENANT => 'home',
            self::HEALTH => 'heart',
            self::MOTORCYCLE => 'bike',
            self::OTHER_MOTOR_VEHICLE => 'truck',
            self::FAMILY_PROTECTION => 'users',
            self::PROFESSIONAL_MULTI_RISK => 'briefcase',
            self::LIFE_AND_DISABILITY => 'shield',
            self::LEGAL_PROTECTION => 'gavel',
            self::BORROWER => 'bank',
            self::PET => 'paw',
            self::OTHER => 'file',
        };
    }

    public static function options(): array
    {
        return array_map(
            fn(self $case) => [
                'key' => $case->value,
                'label' => $case->label(),
                'icon' => $case->icon(),
            ],
            self::cases()
        );
    }
}