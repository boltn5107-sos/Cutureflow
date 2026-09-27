<?php

namespace App\Http\Requests;

use App\Models\Client;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MesureRequest extends FormRequest
{
    public function authorize(): bool
    {
        $client = $this->route('client');

        return $client instanceof Client
            && $this->user()?->can('update', $client);
    }

    /**
     * Le formulaire propose quatre lignes à l'ouverture et permet d'en
     * ajouter. Les lignes laissées entièrement vides sont retirées avant
     * validation : l'utilisateur ne doit pas remplir les champs dont il
     * n'a pas besoin.
     *
     * Seuls le libellé et la valeur comptent : l'unité est pré-remplie à
     * « cm » sur chaque ligne et ne suffirait donc pas à définir une mesure.
     */
    protected function prepareForValidation(): void
    {
        $lignes = $this->input('mesures');

        if (! is_array($lignes)) {
            return;
        }

        $lignes = array_values(array_filter(
            $lignes,
            fn ($ligne) => is_array($ligne)
                && (filled($ligne['libelle'] ?? null) || filled($ligne['valeur'] ?? null)),
        ));

        $this->merge(['mesures' => $lignes]);
    }

    public function rules(): array
    {
        $client = $this->route('client');

        return [
            'mesures' => ['required', 'array', 'min:1', 'max:30'],
            'mesures.*.libelle' => ['required', 'string', 'max:120'],
            'mesures.*.valeur' => ['required', 'numeric', 'min:0', 'max:9999.99'],
            'mesures.*.unite' => ['nullable', 'string', 'max:12'],
            'date_mesure' => ['required', 'date', 'before_or_equal:today', 'after:2000-01-01'],
            'categorie' => ['nullable', 'string', 'max:60'],
            'commande_id' => [
                'nullable',
                'integer',
                Rule::exists('commandes', 'id')->where('atelier_id', $client?->atelier_id),
            ],
            'commentaire' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'mesures.required' => 'Saisissez au moins une mesure.',
            'mesures.min' => 'Saisissez au moins une mesure.',
            'mesures.max' => 'Vous ne pouvez pas enregistrer plus de :max mesures à la fois.',
            'mesures.*.libelle.required' => 'Indiquez le nom de la mesure.',
            'mesures.*.libelle.max' => 'Le nom de la mesure ne peut pas dépasser :max caractères.',
            'mesures.*.valeur.required' => 'Indiquez la valeur mesurée.',
            'mesures.*.valeur.numeric' => 'La valeur doit être un nombre.',
            'mesures.*.valeur.min' => 'La valeur ne peut pas être négative.',
            'mesures.*.unite.max' => 'L\'unité ne peut pas dépasser :max caractères.',
            'categorie.max' => 'La catégorie ne peut pas dépasser :max caractères.',
            'date_mesure.required' => 'Indiquez la date de la prise de mesure.',
            'date_mesure.before_or_equal' => 'La date de mesure ne peut pas être dans le futur.',
            'date_mesure.after' => 'La date de mesure est trop ancienne.',
            'commentaire.max' => 'Le commentaire ne peut pas dépasser :max caractères.',
            'commande_id.exists' => 'La commande sélectionnée est invalide.',
        ];
    }

    public function attributes(): array
    {
        return [
            'mesures' => 'mesures',
            'date_mesure' => 'date de mesure',
            'categorie' => 'catégorie',
            'commande_id' => 'commande',
            'commentaire' => 'commentaire',
        ];
    }
}
