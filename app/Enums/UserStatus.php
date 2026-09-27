<?php

namespace App\Enums;

enum UserStatus: string
{
    case EnAttente = 'en_attente';
    case Valide = 'valide';
    case Rejete = 'rejete';
    case Bloque = 'bloque';

    public function label(): string
    {
        return match ($this) {
            self::EnAttente => 'En Attente',
            self::Valide => 'Validé',
            self::Rejete => 'Rejeté',
            self::Bloque => 'Bloqué',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::EnAttente => 'Votre inscription est en cours de vérification.',
            self::Valide => 'Votre abonnement est actif. Toutes les fonctionnalités sont ouvertes.',
            self::Rejete => 'Votre inscription a été rejetée.',
            self::Bloque => 'Votre compte est bloqué jusqu\'à régularisation de votre abonnement.',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
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

    /** @return array<int, string> */
    public static function labels(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[$case->value] = $case->label();
        }

        return $out;
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::EnAttente => 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300',
            self::Valide => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300',
            self::Rejete => 'bg-red-100 text-red-800 dark:bg-red-500/15 dark:text-red-300',
            self::Bloque => 'bg-brand-200 text-brand-900 dark:bg-brand-accent/20 dark:text-brand-accent',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::EnAttente => 'fa-regular fa-clock',
            self::Valide => 'fa-solid fa-circle-check',
            self::Rejete => 'fa-solid fa-circle-xmark',
            self::Bloque => 'fa-solid fa-lock',
        };
    }

    public function allowsAccess(): bool
    {
        return $this === self::Valide;
    }
}
