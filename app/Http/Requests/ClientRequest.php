<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClientRequest extends FormRequest
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

        $client = $this->route('client');

        if ($client) {
            return $user->atelier?->id === $client->atelier_id;
        }

        return $user->atelier !== null;
    }

    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'min:2', 'max:150'],
            'telephone' => ['required', 'string', 'min:6', 'max:40'],
            'email' => ['nullable', 'string', 'email', 'max:190'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'photo' => [
                'nullable',
                'image',
                'mimes:'.implode(',', config('coutureflow.media.mimes')),
                'mimetypes:image/jpeg,image/png,image/webp',
                'max:'.config('coutureflow.media.max_kb'),
            ],
            'remove_photo' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'nom' => 'nom du client',
            'telephone' => 'téléphone',
            'email' => 'email',
            'adresse' => 'adresse',
            'notes' => 'notes',
            'photo' => 'photo',
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom du client est obligatoire.',
            'telephone.required' => 'Le téléphone est obligatoire.',
            'telephone.min' => 'Le numéro de téléphone semble trop court.',
            'email.email' => 'L\'adresse email n\'est pas valide.',
            'photo.image' => 'La photo doit être une image.',
            'photo.mimes' => 'La photo doit être au format JPG, PNG ou WEBP.',
            'photo.max' => 'La photo ne doit pas dépasser '.round(config('coutureflow.media.max_kb') / 1024).' Mo.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nom' => $this->filled('nom') ? trim((string) $this->input('nom')) : null,
            'telephone' => $this->filled('telephone') ? trim((string) $this->input('telephone')) : null,
            'email' => $this->filled('email') ? mb_strtolower(trim((string) $this->input('email'))) : null,
        ]);
    }
}
