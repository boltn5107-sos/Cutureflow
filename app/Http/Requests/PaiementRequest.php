<?php

namespace App\Http\Requests;

use App\Enums\PaiementMethode;
use App\Enums\PaiementType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaiementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $paiement = $this->route('paiement');

        if (! $user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($paiement) {
            return $user->atelier?->id === $paiement->atelier_id;
        }

        return $user->atelier !== null;
    }

    public function rules(): array
    {
        $atelierId = $this->user()?->atelier?->id;

        return [
            'commande_id' => [
                'nullable',
                'integer',
                Rule::exists('commandes', 'id')->where('atelier_id', $atelierId),
            ],
            'client_id' => [
                'nullable',
                'integer',
                Rule::exists('clients', 'id')->where('atelier_id', $atelierId),
            ],
            'type' => ['required', Rule::in(PaiementType::values())],
            'methode' => ['required', Rule::in(PaiementMethode::values())],
            'montant' => ['required', 'numeric', 'min:1', 'max:99999999'],
            'description' => ['nullable', 'string', 'max:1000'],
            'date_paiement' => ['required', 'date', 'before_or_equal:today', 'after:2000-01-01'],
            'reference' => ['nullable', 'string', 'max:80'],
        ];
    }

    public function attributes(): array
    {
        return [
            'commande_id' => 'commande',
            'client_id' => 'client',
            'type' => 'type de paiement',
            'methode' => 'méthode',
            'montant' => 'montant',
            'date_paiement' => 'date du paiement',
        ];
    }

    public function messages(): array
    {
        return [
            'montant.required' => 'Le montant est obligatoire.',
            'montant.min' => 'Le montant doit être supérieur à 0.',
            'type.in' => 'Le type de paiement est invalide.',
            'methode.in' => 'La méthode de paiement est invalide.',
            'date_paiement.before_or_equal' => 'La date du paiement ne peut pas être dans le futur.',
            'commande_id.exists' => 'La commande sélectionnée est invalide.',
            'client_id.exists' => 'Le client sélectionné est invalide.',
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator): void {
            $commandeId = $this->input('commande_id');
            $type = $this->input('type');

            if (in_array($type, ['avance', 'solde'], true) && ! $commandeId) {
                $validator->errors()->add('commande_id', 'Une commande est obligatoire pour une avance ou un solde.');
            }

            $montant = (float) $this->input('montant');
            $commande = $commandeId ? \App\Models\Commande::find($commandeId) : null;

            if ($commande && $type === 'solde' && $montant > (float) $commande->solde) {
                $validator->errors()->add('montant', 'Le montant dépasse le solde restant de la commande ('.number_format((float) $commande->solde, 0, ',', ' ').').');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'montant' => $this->input('montant') !== null
                ? (float) str_replace([' ', ','], ['', '.'], (string) $this->input('montant'))
                : null,
            'description' => $this->filled('description') ? trim((string) $this->input('description')) : null,
        ]);
    }
}
