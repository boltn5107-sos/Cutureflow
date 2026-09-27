<?php

namespace App\Http\Requests;

use App\Enums\CommandeStatut;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CommandeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $commande = $this->route('commande');

        if ($commande) {
            return $user->atelier?->id === $commande->atelier_id;
        }

        return $user->atelier !== null;
    }

    public function rules(): array
    {
        $atelierId = $this->user()?->atelier?->id;

        return [
            'client_id' => [
                'required',
                'integer',
                Rule::exists('clients', 'id')
                    ->where('atelier_id', $atelierId)
                    ->whereNull('deleted_at'),
            ],
            'modele_id' => [
                'nullable',
                'integer',
                Rule::exists('modeles', 'id')->where('atelier_id', $atelierId),
            ],
            'type' => ['nullable', 'string', 'max:40'],
            'tissu' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:3000'],
            'prix_total' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'avance' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'date_commande' => ['required', 'date', 'before_or_equal:today', 'after:2000-01-01'],
            'date_livraison_prevue' => ['nullable', 'date', 'after_or_equal:date_commande'],
            'date_livraison_reelle' => ['nullable', 'date', 'before_or_equal:today'],
            'statut' => ['required', Rule::in(CommandeStatut::values())],
            'notes' => ['nullable', 'string', 'max:3000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'client_id' => 'client',
            'modele_id' => 'modèle',
            'prix_total' => 'prix total',
            'avance' => 'avance',
            'date_commande' => 'date de commande',
            'date_livraison_prevue' => 'date de livraison prévue',
            'date_livraison_reelle' => 'date de livraison réelle',
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.required' => 'Sélectionnez le client de la commande.',
            'client_id.exists' => 'Le client sélectionné n\'existe pas ou n\'appartient pas à votre atelier.',
            'modele_id.exists' => 'Le modèle sélectionné est invalide.',
            'prix_total.required' => 'Le prix total est obligatoire.',
            'prix_total.min' => 'Le prix total ne peut pas être négatif.',
            'avance.required' => 'L\'avance est obligatoire (saisissez 0 si aucune).',
            'avance.min' => 'L\'avance ne peut pas être négative.',
            'avance.lte' => 'L\'avance ne peut pas dépasser le prix total.',
            'date_commande.required' => 'La date de commande est obligatoire.',
            'date_commande.before_or_equal' => 'La date de commande ne peut pas être dans le futur.',
            'date_livraison_prevue.after_or_equal' => 'La livraison prévue doit être postérieure ou égale à la date de commande.',
            'date_livraison_reelle.before_or_equal' => 'La date de livraison réelle ne peut pas être dans le futur.',
            'statut.in' => 'Le statut sélectionné est invalide.',
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator): void {
            $total = (float) $this->input('prix_total');
            $avance = (float) $this->input('avance');

            if ($total > 0 && $avance > $total) {
                $validator->errors()->add('avance', 'L\'avance ne peut pas dépasser le prix total.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'prix_total' => $this->input('prix_total') !== null ? (float) str_replace([' ', ','], ['', '.'], (string) $this->input('prix_total')) : null,
            'avance' => $this->input('avance') !== null ? (float) str_replace([' ', ','], ['', '.'], (string) $this->input('avance')) : 0,
            'tissu' => $this->filled('tissu') ? trim((string) $this->input('tissu')) : null,
        ]);
    }
}
