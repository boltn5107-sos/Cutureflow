<?php

namespace App\Models;

use App\Enums\DepenseCategorie;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Depense extends Model
{
    use HasFactory;

    protected $fillable = [
        'atelier_id',
        'libelle',
        'categorie',
        'montant',
        'date_depense',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'montant' => 'decimal:2',
            'date_depense' => 'date',
            'categorie' => DepenseCategorie::class,
        ];
    }

    public function atelier(): BelongsTo
    {
        return $this->belongsTo(Atelier::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function categorieLabel(): string
    {
        return $this->categorie instanceof DepenseCategorie ? $this->categorie->label() : (string) $this->categorie;
    }

    public function categorieIcon(): string
    {
        return $this->categorie instanceof DepenseCategorie ? $this->categorie->icon() : 'fa-solid fa-box-open';
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
            $q->where('libelle', 'like', "%{$term}%")
                ->orWhere('notes', 'like', "%{$term}%");
        });
    }
}
