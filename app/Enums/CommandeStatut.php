<?php

namespace App\Enums;

enum CommandeStatut: string
{
    case EnAttente = 'en_attente';
    case EnProduction = 'en_production';
    case Prete = 'prete';
    case Livree = 'livree';
    case Annulee = 'annulee';

    public function label(): string
    {
        return match ($this) {
            self::EnAttente => 'En attente',
            self::EnProduction => 'En production',
            self::Prete => 'Prête',
            self::Livree => 'Livrée',
            self::Annulee => 'Annulée',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::EnAttente => 'bg-stone-100 text-stone-700 dark:bg-white/10 dark:text-stone-300',
            self::EnProduction => 'bg-blue-100 text-blue-800 dark:bg-blue-500/15 dark:text-blue-300',
            self::Prete => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300',
            self::Livree => 'bg-brand-100 text-brand-800 dark:bg-brand-500/20 dark:text-brand-200',
            self::Annulee => 'bg-red-100 text-red-800 dark:bg-red-500/15 dark:text-red-300',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::EnAttente => 'fa-regular fa-clock',
            self::EnProduction => 'fa-solid fa-scissors',
            self::Prete => 'fa-solid fa-box-open',
            self::Livree => 'fa-solid fa-circle-check',
            self::Annulee => 'fa-solid fa-ban',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::EnAttente, self::EnProduction, self::Prete], true);
    }

    public function countsAsRevenue(): bool
    {
        return $this !== self::Annulee;
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
