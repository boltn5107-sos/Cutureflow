<?php

namespace App\Http\Requests;

use App\Enums\ModeleCategorie;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ModeleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $modele = $this->route('modele');

        if (! $user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($modele) {
            return $user->atelier?->id === $modele->atelier_id;
        }

        return $user->atelier !== null;
    }

    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'min:2', 'max:150'],
            'description' => ['nullable', 'string', 'max:3000'],
            'categorie' => ['required', Rule::in(ModeleCategorie::values())],
            'prix_indicatif' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'tissu_conseille' => ['nullable', 'string', 'max:150'],
            'duree_estimate' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
            'photos' => ['nullable', 'array', 'max:6'],
            'photos.*' => [
                'image',
                'mimes:'.implode(',', config('coutureflow.media.mimes')),
                'mimetypes:image/jpeg,image/png,image/webp',
                'max:'.config('coutureflow.media.max_kb'),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'nom' => 'nom du modèle',
            'categorie' => 'catégorie',
            'prix_indicatif' => ' prix indicatif',
            'photos' => 'photos',
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom du modèle est obligatoire.',
            'categorie.in' => 'La catégorie est invalide.',
            'photos.max' => 'Vous pouvez envoyer 6 photos maximum.',
            'photos.*.image' => 'Chaque photo doit être une image.',
            'photos.*.mimes' => 'Les photos doivent être au format JPG, PNG ou WEBP.',
            'photos.*.max' => 'Chaque photo ne doit pas dépasser '.round(config('coutureflow.media.max_kb') / 1024).' Mo.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nom' => $this->filled('nom') ? trim((string) $this->input('nom')) : null,
            'prix_indicatif' => $this->input('prix_indicatif') !== null && $this->input('prix_indicatif') !== ''
                ? (float) str_replace([' ', ','], ['', '.'], (string) $this->input('prix_indicatif'))
                : null,
        ]);
    }
}
