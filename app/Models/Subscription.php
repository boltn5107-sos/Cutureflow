<?php

namespace App\Models;

use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'libelle',
        'montant',
        'duree_mois',
        'statut',
        'date_paiement',
        'wave_number_used',
        'wave_reference',
        'proof_disk',
        'proof_path',
        'proof_original_name',
        'proof_mime',
        'proof_size',
        'reason',
        'reviewed_at',
        'reviewed_by',
        'valid_from',
        'valid_until',
    ];

    protected function casts(): array
    {
        return [
            'montant' => 'decimal:2',
            'duree_mois' => 'integer',
            'statut' => UserStatus::class,
            'date_paiement' => 'date',
            'reviewed_at' => 'datetime',
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function hasProof(): bool
    {
        return filled($this->proof_path);
    }

    public function isImage(): bool
    {
        return filled($this->proof_mime) && str_starts_with($this->proof_mime, 'image/');
    }

    /** URL publique de la preuve — inaccessible sans autorisation. */
    public function proofUrl(): string
    {
        if (! $this->hasProof()) {
            return '#';
        }

        return route('admin.paiements.proof', $this);
    }

    public function proofSizeForHumans(): string
    {
        if (! $this->proof_size) {
            return '—';
        }

        $kb = $this->proof_size / 1024;

        return $kb >= 1024
            ? round($kb / 1024, 2).' Mo'
            : round($kb).' Ko';
    }

    public function deleteProofFile(): void
    {
        if ($this->hasProof()) {
            Storage::disk($this->proof_disk)->delete($this->proof_path);
        }
    }

    public function scopeEnAttente(Builder $query): Builder
    {
        return $query->where('statut', UserStatus::EnAttente->value);
    }

    public function scopeForAtelierUser(Builder $query, ?int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }
}
