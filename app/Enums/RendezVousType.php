<?php

namespace App\Enums;

enum RendezVousType: string
{
    case RendezVous = 'rendez_vous';
    case Essayage = 'essayage';
    case Livraison = 'livraison';
    case Important = 'important';

    public function label(): string
    {
        return match ($this) {
            self::RendezVous => 'Rendez-vous',
            self::Essayage => 'Essayage',
            self::Livraison => 'Livraison',
            self::Important => 'Date importante',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::RendezVous => 'bg-stone-100 text-stone-700 dark:bg-white/10 dark:text-stone-300',
            self::Essayage => 'bg-purple-100 text-purple-800 dark:bg-purple-500/15 dark:text-purple-300',
            self::Livraison => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300',
            self::Important => 'bg-brand-200 text-brand-900 dark:bg-brand-accent/20 dark:text-brand-accent',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::RendezVous => 'fa-regular fa-calendar',
            self::Essayage => 'fa-solid fa-shirt',
            self::Livraison => 'fa-solid fa-box-open',
            self::Important => 'fa-solid fa-star',
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
