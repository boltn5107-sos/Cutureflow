<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ReviewSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'statut' => ['required', Rule::in([UserStatus::Valide->value, UserStatus::Rejete->value, UserStatus::Bloque->value, UserStatus::EnAttente->value])],
            'raison' => [
                Rule::requiredIf(in_array($this->input('statut'), [UserStatus::Rejete->value, UserStatus::Bloque->value], true)),
                'nullable',
                'string',
                'min:5',
                'max:1000',
            ],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
        ];
    }

    public function attributes(): array
    {
        return [
            'statut' => 'statut',
            'raison' => 'raison',
            'valid_from' => 'début de validité',
            'valid_until' => 'fin de validité',
        ];
    }

    public function messages(): array
    {
        return [
            'raison.required' => 'Vous devez indiquer une raison pour le rejet ou le blocage.',
            'raison.min' => 'La raison doit contenir au moins 5 caractères.',
            'statut.in' => 'Le statut sélectionné est invalide.',
            'valid_until.after_or_equal' => 'La date de fin doit être postérieure à la date de début.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->filled('valid_from') && $this->filled('valid_until')) {
                if (strtotime((string) $this->input('valid_until')) < strtotime((string) $this->input('valid_from'))) {
                    $validator->errors()->add('valid_until', 'La date de fin doit être postérieure à la date de début.');
                }
            }
        });
    }

    public function statut(): UserStatus
    {
        return UserStatus::from((string) $this->input('statut'));
    }
}
