<?php

namespace App\Models;

use App\Enums\RendezVousType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RendezVous extends Model
{
    use HasFactory;

    protected $table = 'rendez_vous';

    protected $fillable = [
        'atelier_id',
        'client_id',
        'commande_id',
        'titre',
        'type',
        'date_debut',
        'heure_debut',
        'date_fin',
        'heure_fin',
        'lieu',
        'notes',
        'rappel_effectue',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'date_fin' => 'date',
            'type' => RendezVousType::class,
            'rappel_effectue' => 'boolean',
        ];
    }

    public function atelier(): BelongsTo
    {
        return $this->belongsTo(Atelier::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function typeLabel(): string
    {
        return $this->type instanceof RendezVousType ? $this->type->label() : (string) $this->type;
    }

    public function typeIcon(): string
    {
        return $this->type instanceof RendezVousType ? $this->type->icon() : 'fa-regular fa-calendar';
    }

    public function typeBadgeClass(): string
    {
        return $this->type instanceof RendezVousType ? $this->type->badgeClass() : 'bg-stone-100 text-stone-700';
    }

    public function heureFormatee(): string
    {
        if (blank($this->heure_debut)) {
            return '—';
        }

        try {
            $start = \Illuminate\Support\Carbon::parse($this->date_debut->format('Y-m-d').' '.$this->heure_debut);
        } catch (\Throwable) {
            return (string) $this->heure_debut;
        }

        if (blank($this->heure_fin)) {
            return $start->format('H:i');
        }

        try {
            $end = \Illuminate\Support\Carbon::parse($this->date_debut->format('Y-m-d').' '.$this->heure_fin);
        } catch (\Throwable) {
            return $start->format('H:i');
        }

        return $start->format('H:i').' - '.$end->format('H:i');
    }

    public function estPasse(): bool
    {
        $date = $this->date_fin ?? $this->date_debut;

        return $date->lt(today());
    }

    public function estAujourdhui(): bool
    {
        return $this->date_debut->isToday();
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
            $q->where('titre', 'like', "%{$term}%")
                ->orWhere('lieu', 'like', "%{$term}%")
                ->orWhereHas('client', fn (Builder $c) => $c->where('nom', 'like', "%{$term}%"));
        });
    }
}
