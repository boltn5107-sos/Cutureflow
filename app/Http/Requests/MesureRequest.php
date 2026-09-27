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
     * Le formulaire ne propose plus de lignes à remplir : le tailleur clique
     * sur les icônes des mesures dont il a besoin, et seules celles-ci sont
     * envoyées.
     *
     * Seules les lignes totalement vides sont retirées avant validation, ce
     * qui n'arrive pas depuis l'interface. Une ligne choisie mais laissée sans
     * valeur est en revanche conservée, puis refusée : le bouton d'enregistrement
     * annonce le nombre de mesures sélectionnées, et un enregistrement
     * silencieux en perdrait une sans le dire.
     *
     * L'unité est pré-remplie à « cm » et ne suffit donc pas à définir une
     * mesure : elle ne compte pas dans le critère de purge.
     */
    protected function prepareForValidation(): void
    {
        $lignes = $this->input('mesures');

        if (! is_array($lignes)) {
            $this->merge(['mesures' => []]);

            return;
        }

        $lignes = array_values(array_filter(
            $lignes,
            fn ($ligne) => is_array($ligne) && (
                filled($ligne['code'] ?? null)
                || filled($ligne['libelle'] ?? null)
                || filled($ligne['valeur'] ?? null)
            ),
        ));

        $this->merge(['mesures' => $lignes]);
    }

    public function rules(): array
    {
        return [
            /*
             * Le nombre maximal de mesures est aligné sur la taille réelle du
             * catalogue : au-delà, le tailleur a Manifestement choisi autre
             * chose qu'une mesure de corps.
             */
            'mesures' => ['required', 'array', 'min:1', 'max:40'],
            'mesures.*.code' => ['nullable', 'string', 'max:60'],
            'mesures.*.libelle' => ['required', 'string', 'max:120'],
            'mesures.*.valeur' => ['required', 'numeric', 'min:0', 'max:9999.99'],
            'mesures.*.unite' => ['nullable', 'string', Rule::in(array_keys(config('coutureflow.unites_mesure', ['cm' => ''])))],
            'date_mesure' => ['required', 'date', 'before_or_equal:today', 'after:2000-01-01'],
            'commentaire' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'mesures.required' => 'Choisissez au moins une mesure à enregistrer.',
            'mesures.min' => 'Choisissez au moins une mesure à enregistrer.',
            'mesures.max' => 'Vous ne pouvez pas enregistrer plus de :max mesures à la fois.',
            'mesures.*.code.max' => 'La mesure sélectionnée n\'est pas reconnue.',
            'mesures.*.libelle.required' => 'Indiquez le nom de la mesure.',
            'mesures.*.libelle.max' => 'Le nom de la mesure ne peut pas dépasser :max caractères.',
            'mesures.*.valeur.required' => 'Indiquez la valeur mesurée.',
            'mesures.*.valeur.numeric' => 'La valeur doit être un nombre.',
            'mesures.*.valeur.min' => 'La valeur ne peut pas être négative.',
            'mesures.*.unite.in' => 'L\'unité sélectionnée n\'est pas reconnue.',
            'date_mesure.required' => 'Indiquez la date de la prise de mesure.',
            'date_mesure.before_or_equal' => 'La date de mesure ne peut pas être dans le futur.',
            'date_mesure.after' => 'La date de mesure est trop ancienne.',
            'commentaire.max' => 'Le commentaire ne peut pas dépasser :max caractères.',
        ];
    }

    public function attributes(): array
    {
        return [
            'mesures' => 'mesures',
            'date_mesure' => 'date de mesure',
            'commentaire' => 'commentaire',
        ];
    }
}
