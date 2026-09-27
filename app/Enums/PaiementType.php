<?php

namespace App\Enums;

enum PaiementType: string
{
    case Avance = 'avance';
    case Solde = 'solde';
    case Recette = 'recette';
    case Remboursement = 'remboursement';

    public function label(): string
    {
        return match ($this) {
            self::Avance => 'Avance',
            self::Solde => 'Paiement solde',
            self::Recette => 'Recette diverse',
            self::Remboursement => 'Remboursement',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Avance => 'bg-blue-100 text-blue-800 dark:bg-blue-500/15 dark:text-blue-300',
            self::Solde => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300',
            self::Recette => 'bg-brand-100 text-brand-800 dark:bg-brand-500/20 dark:text-brand-200',
            self::Remboursement => 'bg-red-100 text-red-800 dark:bg-red-500/15 dark:text-red-300',
        };
    }

    public function countsAsRevenue(): bool
    {
        return $this !== self::Remboursement;
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
