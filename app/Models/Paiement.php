<?php

namespace App\Models;

use App\Enums\PaiementMethode;
use App\Enums\PaiementType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Paiement extends Model
{
    use HasFactory;

    protected $fillable = [
        'atelier_id',
        'commande_id',
        'client_id',
        'type',
        'methode',
        'montant',
        'description',
        'date_paiement',
        'reference',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'montant' => 'decimal:2',
            'date_paiement' => 'date',
            'type' => PaiementType::class,
            'methode' => PaiementMethode::class,
        ];
    }

    public function atelier(): BelongsTo
    {
        return $this->belongsTo(Atelier::class);
    }

    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function typeLabel(): string
    {
        return $this->type instanceof PaiementType ? $this->type->label() : (string) $this->type;
    }

    public function methodeLabel(): string
    {
        return $this->methode instanceof PaiementMethode ? $this->methode->label() : (string) $this->methode;
    }

    public function methodeIcon(): string
    {
        return $this->methode instanceof PaiementMethode ? $this->methode->icon() : 'fa-solid fa-ellipsis';
    }

    public function typeBadgeClass(): string
    {
        return $this->type instanceof PaiementType ? $this->type->badgeClass() : 'bg-stone-100 text-stone-700';
    }

    public function scopeForAtelier(Builder $query, ?int $atelierId): Builder
    {
        return $query->where('atelier_id', $atelierId);
    }

    public function scopeRecettes(Builder $query): Builder
    {
        return $query->where('type', '!=', PaiementType::Remboursement->value);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $term = trim($term);

        return $query->where(function (Builder $q) use ($term) {
            $q->where('description', 'like', "%{$term}%")
                ->orWhere('reference', 'like', "%{$term}%")
                ->orWhereHas('commande', fn (Builder $c) => $c->where('numero', 'like', "%{$term}%"))
                ->orWhereHas('client', fn (Builder $c) => $c->where('nom', 'like', "%{$term}%"));
        });
    }
}
