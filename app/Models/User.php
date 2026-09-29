<?php

namespace App\Models;

use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'status',
        'status_reason',
        'status_updated_at',
        'validated_at',
        'validated_by',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'status_updated_at' => 'datetime',
            'validated_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'role' => 'string',
            'status' => UserStatus::class,
        ];
    }

    public function atelier(): HasOne
    {
        return $this->hasOne(Atelier::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class)->latest('date_paiement');
    }

    public function latestSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->latestOfMany('date_paiement');
    }

    public function pendingSubscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class)->where('statut', 'en_attente');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function reviewedSubscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'reviewed_by');
    }

    public function pushSubscriptions(): HasMany
    {
        return $this->hasMany(PushSubscription::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function hasValidatedAccess(): bool
    {
        return $this->status?->allowsAccess() === true;
    }

    public function atelierId(): ?int
    {
        return $this->atelier?->id;
    }

    public function displayName(): string
    {
        return $this->atelier?->nom ?? $this->name;
    }

    /**
     * Restreint la requête aux comptes d'atelier (hors administrateurs).
     *
     * Volontairement nommé roleAtelier : un scope "atelier" entrerait en
     * conflit avec la relation atelier() et ne serait pas résolu.
     */
    public function scopeRoleAtelier(Builder $query): Builder
    {
        return $query->where('role', 'atelier');
    }

    public function scopeStatus(Builder $query, UserStatus|string $status): Builder
    {
        $value = $status instanceof UserStatus ? $status->value : $status;

        return $query->where('status', $value);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $term = trim($term);

        return $query->where(function (Builder $q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")
                ->orWhereHas('atelier', fn (Builder $a) => $a->where('nom', 'like', "%{$term}%"));
        });
    }
}
