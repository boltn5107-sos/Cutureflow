<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $user = $this->user();

        return [
            'name' => ['required', 'string', 'min:3', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['required', 'string', 'min:6', 'max:40'],
            'atelier_nom' => ['required', 'string', 'min:3', 'max:150'],
            'atelier_telephone' => ['nullable', 'string', 'min:6', 'max:40'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'ville' => ['nullable', 'string', 'max:120'],
            'ninea' => ['nullable', 'string', 'max:40'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nom du responsable',
            'atelier_nom' => 'nom de l\'atelier',
            'phone' => 'téléphone',
            'atelier_telephone' => 'téléphone de l\'atelier',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Votre nom est obligatoire.',
            'atelier_nom.required' => 'Le nom de l\'atelier est obligatoire.',
            'phone.required' => 'Le téléphone est obligatoire.',
            'atelier_telephone.min' => 'Le téléphone de l\'atelier doit contenir au moins 6 caractères.',
            'email.unique' => 'Cette adresse email est déjà utilisée.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => $this->filled('email') ? mb_strtolower(trim((string) $this->input('email'))) : null,
        ]);
    }
}
