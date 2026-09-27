<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ModelePhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'modele_id',
        'disk',
        'path',
        'original_name',
        'mime',
        'position',
    ];

    public function modele(): BelongsTo
    {
        return $this->belongsTo(Modele::class);
    }

    /** URL protégée : le fichier est servi par un controller autorisé. */
    public function url(): string
    {
        return route('modeles.photo', $this);
    }

    public function deleteFile(): void
    {
        Storage::disk($this->disk)->delete($this->path);
    }
}
