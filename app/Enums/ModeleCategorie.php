<?php

namespace App\Enums;

enum ModeleCategorie: string
{
    case Homme = 'homme';
    case Femme = 'femme';
    case Enfant = 'enfant';
    case Autre = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::Homme => 'Homme',
            self::Femme => 'Femme',
            self::Enfant => 'Enfant',
            self::Autre => 'Autre',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Homme => 'bg-blue-100 text-blue-800 dark:bg-blue-500/15 dark:text-blue-300',
            self::Femme => 'bg-pink-100 text-pink-800 dark:bg-pink-500/15 dark:text-pink-300',
            self::Enfant => 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300',
            self::Autre => 'bg-stone-100 text-stone-700 dark:bg-white/10 dark:text-stone-300',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Homme => 'fa-solid fa-mars',
            self::Femme => 'fa-solid fa-venus',
            self::Enfant => 'fa-solid fa-child-reaching',
            self::Autre => 'fa-solid fa-shirt',
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
