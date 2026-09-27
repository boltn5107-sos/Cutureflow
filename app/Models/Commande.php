<?php

namespace App\Models;

use App\Enums\CommandeStatut;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Commande extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'atelier_id',
        'client_id',
        'modele_id',
        'numero',
        'type',
        'tissu',
        'description',
        'prix_total',
        'avance',
        'solde',
        'date_commande',
        'date_livraison_prevue',
        'date_livraison_reelle',
        'statut',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'prix_total' => 'decimal:2',
            'avance' => 'decimal:2',
            'solde' => 'decimal:2',
            'date_commande' => 'date',
            'date_livraison_prevue' => 'date',
            'date_livraison_reelle' => 'date',
            'statut' => CommandeStatut::class,
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $commande): void {
            $total = (float) $commande->prix_total;
            $avance = (float) $commande->avance;
            $commande->solde = round(max($total - $avance, 0), 2);
        });
    }

    public function atelier(): BelongsTo
    {
        return $this->belongsTo(Atelier::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function modele(): BelongsTo
    {
        return $this->belongsTo(Modele::class);
    }

    public function paiements(): HasMany
    {
        return $this->hasMany(Paiement::class)->latest('date_paiement');
    }

    public function mesures(): HasMany
    {
        return $this->hasMany(Mesure::class);
    }

    public function rendezVous(): HasMany
    {
        return $this->hasMany(RendezVous::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getStatutEnum(): CommandeStatut
    {
        return $this->statut instanceof CommandeStatut ? $this->statut : CommandeStatut::from((string) $this->statut);
    }

    public function estEnRetard(): bool
    {
        if (! $this->statut?->isOpen()) {
            return false;
        }

        return filled($this->date_livraison_prevue)
            && $this->date_livraison_prevue->isBefore(today());
    }

    public function joursDeRetard(): int
    {
        if (! $this->estEnRetard()) {
            return 0;
        }

        return (int) today()->diffInDays($this->date_livraison_prevue);
    }

    public function livraisonDansJours(): ?int
    {
        if (! $this->statut?->isOpen() || ! filled($this->date_livraison_prevue)) {
            return null;
        }

        return (int) today()->diffInDays($this->date_livraison_prevue, false);
    }

    public function estSoldePaye(): bool
    {
        return (float) $this->solde <= 0;
    }

    public function prochainPaiement(): ?string
    {
        if (! $this->statut?->isOpen()) {
            return null;
        }

        if ((float) $this->avance <= 0) {
            return 'Avance';
        }

        if ((float) $this->solde > 0) {
            return 'Solde';
        }

        return null;
    }

    public function totalEncaisse(): float
    {
        return (float) $this->paiements()
            ->whereIn('type', ['avance', 'solde', 'recette'])
            ->sum('montant');
    }

    public function resteAPayer(): float
    {
        return round((float) $this->solde, 2);
    }

    public function statutBadgeClass(): string
    {
        return $this->getStatutEnum()->badgeClass();
    }

    public function statutLabel(): string
    {
        return $this->getStatutEnum()->label();
    }

    public static function genererNumero(?int $atelierId): string
    {
        $year = now()->year;
        $prefix = 'CMD-'.$year.'-';

        if (! $atelierId) {
            return $prefix.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        }

        $last = static::withTrashed()
            ->where('atelier_id', $atelierId)
            ->where('numero', 'like', $prefix.'%')
            ->orderByDesc('numero')
            ->value('numero');

        $sequence = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    public function scopeForAtelier(Builder $query, ?int $atelierId): Builder
    {
        return $query->where('atelier_id', $atelierId);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $term = trim($term);

        return $query->where(function (Builder $q) use ($term) {
            $q->where('numero', 'like', "%{$term}%")
                ->orWhere('tissu', 'like', "%{$term}%")
                ->orWhere('type', 'like', "%{$term}%")
                ->orWhereHas('client', fn (Builder $c) => $c->where('nom', 'like', "%{$term}%"));
        });
    }

    public function scopeStatut(Builder $query, CommandeStatut|string|null $statut): Builder
    {
        if (blank($statut)) {
            return $query;
        }

        $value = $statut instanceof CommandeStatut ? $statut->value : $statut;

        return $query->where('statut', $value);
    }

    public function scopeOuvertes(Builder $query): Builder
    {
        return $query->whereIn('statut', [
            CommandeStatut::EnAttente->value,
            CommandeStatut::EnProduction->value,
            CommandeStatut::Prete->value,
        ]);
    }

    public function scopeEnRetard(Builder $query): Builder
    {
        return $query->ouvertes()
            ->whereNotNull('date_livraison_prevue')
            ->whereDate('date_livraison_prevue', '<', today());
    }
}
