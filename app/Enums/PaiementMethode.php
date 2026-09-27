<?php

namespace App\Enums;

enum PaiementMethode: string
{
    case Especes = 'especes';
    case Wave = 'wave';
    case OrangeMoney = 'orange_money';
    case Virement = 'virement';
    case Autre = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::Especes => 'Espèces',
            self::Wave => 'Wave',
            self::OrangeMoney => 'Orange Money',
            self::Virement => 'Virement bancaire',
            self::Autre => 'Autre',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Especes => 'fa-solid fa-money-bill-wave',
            self::Wave => 'fa-solid fa-mobile-screen',
            self::OrangeMoney => 'fa-solid fa-signal',
            self::Virement => 'fa-solid fa-building-columns',
            self::Autre => 'fa-solid fa-ellipsis',
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

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
