<?php

namespace App\Http\Requests;

use App\Enums\DepenseCategorie;
use Illuminate\Foundation\Http\FormRequest;

class DepenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $depense = $this->route('depense');

        if (! $user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($depense) {
            return $user->atelier?->id === $depense->atelier_id;
        }

        return $user->atelier !== null;
    }

    public function rules(): array
    {
        return [
            'libelle' => ['required', 'string', 'min:2', 'max:150'],
            'categorie' => ['required', 'string', 'max:60'],
            'montant' => ['required', 'numeric', 'min:1', 'max:99999999'],
            'date_depense' => ['required', 'date', 'before_or_equal:today', 'after:2000-01-01'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'libelle' => 'libellé',
            'categorie' => 'catégorie',
            'montant' => 'montant',
            'date_depense' => 'date de la dépense',
        ];
    }

    public function messages(): array
    {
        return [
            'libelle.required' => 'Le libellé de la dépense est obligatoire.',
            'categorie.required' => 'La catégorie est obligatoire.',
            'montant.required' => 'Le montant est obligatoire.',
            'montant.min' => 'Le montant doit être supérieur à 0.',
            'date_depense.before_or_equal' => 'La date de la dépense ne peut pas être dans le futur.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'montant' => $this->input('montant') !== null
                ? (float) str_replace([' ', ','], ['', '.'], (string) $this->input('montant'))
                : null,
            'libelle' => $this->filled('libelle') ? trim((string) $this->input('libelle')) : null,
        ]);
    }
}
