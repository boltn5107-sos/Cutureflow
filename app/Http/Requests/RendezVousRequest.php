<?php

namespace App\Http\Requests;

use App\Enums\RendezVousType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RendezVousRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $rendezVous = $this->route('rendezVous') ?? $this->route('rendez_vous');

        if (! $user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($rendezVous) {
            return $user->atelier?->id === $rendezVous->atelier_id;
        }

        return $user->atelier !== null;
    }

    public function rules(): array
    {
        $atelierId = $this->user()?->atelier?->id;

        return [
            'titre' => ['required', 'string', 'min:2', 'max:150'],
            'type' => ['required', Rule::in(RendezVousType::values())],
            'client_id' => [
                'nullable',
                'integer',
                Rule::exists('clients', 'id')->where('atelier_id', $atelierId),
            ],
            'commande_id' => [
                'nullable',
                'integer',
                Rule::exists('commandes', 'id')->where('atelier_id', $atelierId),
            ],
            'date_debut' => ['required', 'date', 'after:2000-01-01'],
            'heure_debut' => ['nullable', 'date_format:H:i'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'heure_fin' => ['nullable', 'date_format:H:i'],
            'lieu' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'titre' => 'titre',
            'type' => 'type',
            'client_id' => 'client',
            'commande_id' => 'commande',
            'date_debut' => 'date de début',
            'heure_debut' => 'heure de début',
            'date_fin' => 'date de fin',
            'heure_fin' => 'heure de fin',
        ];
    }

    public function messages(): array
    {
        return [
            'titre.required' => 'Le titre est obligatoire.',
            'type.in' => 'Le type d\'événement est invalide.',
            'heure_debut.date_format' => 'L\'heure de début est invalide.',
            'heure_fin.date_format' => 'L\'heure de fin est invalide.',
            'date_fin.after_or_equal' => 'La date de fin doit être postérieure à la date de début.',
            'client_id.exists' => 'Le client sélectionné est invalide.',
            'commande_id.exists' => 'La commande sélectionnée est invalide.',
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator): void {
            if ($this->filled('date_fin') && blank($this->input('heure_fin')) && filled($this->input('heure_debut'))) {
                $validator->errors()->add('heure_fin', 'Renseignez l\'heure de fin.');
            }

            if (! $this->filled('date_fin') && $this->filled('heure_fin')) {
                $validator->errors()->add('date_fin', 'Renseignez la date de fin.');
            }
        });
    }
}
