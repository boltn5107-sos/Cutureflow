<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mesure extends Model
{
    use HasFactory;

    protected $table = 'mesures';

    protected $fillable = [
        'client_id',
        'libelle',
        'categorie',
        'valeur',
        'unite',
        'date_mesure',
        'commentaire',
        'commande_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'valeur' => 'decimal:2',
            'date_mesure' => 'date',
        ];
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

    public function valeurFormatee(): string
    {
        $valeur = rtrim(rtrim((string) $this->valeur, '0'), '.');

        return $valeur.' '.$this->unite;
    }
}
