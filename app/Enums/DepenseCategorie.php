<?php

namespace App\Enums;

enum DepenseCategorie: string
{
    case Tissu = 'tissu';
    case Fournitures = 'fournitures';
    case Machine = 'machine';
    case Entretien = 'entretien';
    case Transport = 'transport';
    case Publicite = 'publicite';
    case Salaires = 'salaires';
    case Loyer = 'loyer';
    case Autre = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::Tissu => 'Tissus & matériaux',
            self::Fournitures => 'Fournitures',
            self::Machine => 'Machine & équipement',
            self::Entretien => 'Entretien & réparation',
            self::Transport => 'Transport',
            self::Publicite => 'Publicité',
            self::Salaires => 'Salaires',
            self::Loyer => 'Loyer',
            self::Autre => 'Autre',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Tissu => 'fa-solid fa-layer-group',
            self::Fournitures => 'fa-solid fa-screwdriver-wrench',
            self::Machine => 'fa-solid fa-gears',
            self::Entretien => 'fa-solid fa-oil-can',
            self::Transport => 'fa-solid fa-truck',
            self::Publicite => 'fa-solid fa-bullhorn',
            self::Salaires => 'fa-solid fa-money-check-dollar',
            self::Loyer => 'fa-solid fa-house',
            self::Autre => 'fa-solid fa-box-open',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[$case->value] = $case->label();
        }

        return $out;
    }

    /** @return array<string, string> */
    public static function labels(): array
    {
        return self::options();
    }
}
