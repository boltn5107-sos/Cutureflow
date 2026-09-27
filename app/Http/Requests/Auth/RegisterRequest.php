<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'atelier_nom' => ['required', 'string', 'min:3', 'max:150'],
            'nom' => ['required', 'string', 'min:3', 'max:150'],
            'telephone' => ['required', 'string', 'min:6', 'max:40'],
            'email' => ['required', 'string', 'email', 'max:190', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'ville' => ['nullable', 'string', 'max:120'],

            // Paiement Wave : seul le reçu est demandé. La date de paiement
            // est enregistrée automatiquement et le reste est facultatif.
            'preuve' => [
                'required',
                'file',
                'mimes:'.implode(',', config('coutureflow.proof.mimes')),
                'mimetypes:image/jpeg,image/png,image/webp,application/pdf',
                'max:'.config('coutureflow.proof.max_kb'),
            ],
            'conditions' => ['accepted'],
        ];
    }

    public function attributes(): array
    {
        return [
            'atelier_nom' => 'nom de l\'atelier',
            'nom' => 'nom du responsable',
            'telephone' => 'téléphone',
            'preuve' => 'capture du reçu',
            'conditions' => 'conditions d\'utilisation',
        ];
    }

    public function messages(): array
    {
        return [
            'preuve.mimes' => 'La preuve doit être une image (JPG, PNG, WEBP) ou un PDF.',
            'preuve.mimetypes' => 'Le format du fichier de preuve n\'est pas autorisé.',
            'preuve.max' => 'La preuve ne doit pas dépasser '.round(config('coutureflow.proof.max_kb') / 1024).' Mo.',
            'conditions.accepted' => 'Vous devez accepter les conditions pour finaliser l\'inscription.',
            'email.unique' => 'Un compte existe déjà avec cette adresse email.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('email')) {
            $this->merge(['email' => mb_strtolower(trim((string) $this->input('email')))]);
        }

        if ($this->filled('atelier_nom')) {
            $this->merge(['atelier_nom' => trim((string) $this->input('atelier_nom'))]);
        }

        if ($this->filled('nom')) {
            $this->merge(['nom' => trim((string) $this->input('nom'))]);
        }
    }
}
