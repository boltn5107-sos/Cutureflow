<?php

namespace App\Models;

use App\Enums\ModeleCategorie;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Modele extends Model
{
    use HasFactory;

    protected $table = 'modeles';

    protected $fillable = [
        'atelier_id',
        'nom',
        'description',
        'categorie',
        'prix_indicatif',
        'tissu_conseille',
        'duree_estimate',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'prix_indicatif' => 'decimal:2',
            'categorie' => ModeleCategorie::class,
            'is_active' => 'boolean',
        ];
    }

    public function atelier(): BelongsTo
    {
        return $this->belongsTo(Atelier::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ModelePhoto::class)->orderBy('position');
    }

    public function commandes(): HasMany
    {
        return $this->hasMany(Commande::class);
    }

    public function photoPrincipale(): ?ModelePhoto
    {
        return $this->photos->first();
    }

    public function categorieLabel(): string
    {
        return $this->categorie instanceof ModeleCategorie ? $this->categorie->label() : (string) $this->categorie;
    }

    public function categorieIcon(): string
    {
        return $this->categorie instanceof ModeleCategorie ? $this->categorie->icon() : 'fa-solid fa-shirt';
    }

    public function categorieBadgeClass(): string
    {
        return $this->categorie instanceof ModeleCategorie ? $this->categorie->badgeClass() : 'bg-stone-100 text-stone-700';
    }

    public function deleteAllPhotos(): void
    {
        foreach ($this->photos as $photo) {
            Storage::disk($photo->disk)->delete($photo->path);
        }
    }

    public function scopeForAtelier(Builder $query, ?int $atelierId): Builder
    {
        return $query->where('atelier_id', $atelierId);
    }

    public function scopeCategorie(Builder $query, ModeleCategorie|string|null $categorie): Builder
    {
        if (blank($categorie)) {
            return $query;
        }

        $value = $categorie instanceof ModeleCategorie ? $categorie->value : $categorie;

        return $query->where('categorie', $value);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $term = trim($term);

        return $query->where(function (Builder $q) use ($term) {
            $q->where('nom', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%")
                ->orWhere('tissu_conseille', 'like', "%{$term}%");
        });
    }
}
