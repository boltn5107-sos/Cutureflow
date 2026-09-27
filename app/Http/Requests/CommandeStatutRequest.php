<?php

namespace App\Http\Requests;

use App\Enums\CommandeStatut;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CommandeStatutRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $commande = $this->route('commande');

        return $user !== null
            && $commande !== null
            && ($user->isAdmin() || $user->atelier?->id === $commande->atelier_id);
    }

    public function rules(): array
    {
        return [
            'statut' => ['required', Rule::in(CommandeStatut::values())],
            'date_livraison_reelle' => ['nullable', 'date', 'before_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'statut.in' => 'Le statut sélectionné est invalide.',
            'date_livraison_reelle.before_or_equal' => 'La date de livraison réelle ne peut pas être dans le futur.',
        ];
    }
}
