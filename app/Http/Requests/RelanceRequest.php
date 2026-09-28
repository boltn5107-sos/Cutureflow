<?php

namespace App\Http\Requests;

use App\Services\RelanceService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RelanceRequest extends FormRequest
{
    /**
     * Les relances sont toujours celles d'un atelier précis. Un administrateur
     * n'appartient à aucun atelier : le middleware atelier.valide le laisse
     * passer, mais il n'a ici rien à relancer, donc refus explicite plutôt
     * qu'une page vide qui laisserait croire à un bug.
     */
    public function authorize(): bool
    {
        return $this->user()?->atelier !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'client_id' => ['required', 'integer', Rule::exists('clients', 'id')->where('atelier_id', $this->user()?->atelier?->id)],
            'motif' => ['nullable', 'string', Rule::in(['manuel', 'mesures', 'pretes', 'inactifs'])],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'client_id.exists' => "Ce client n'existe pas dans votre atelier.",
        ];
    }

    /**
     * Les fenêtres de détection ne sont pas des valeurs libres : un atelier
     * qui demandait 1 jour ne verrait que ses clients du jour, et 365 jours
     * ramènerait la totalité de la base. On borne donc la saisie.
     */
    public static function joursInactif(?int $saisi): int
    {
        if ($saisi === null) {
            return RelanceService::INACTIF_JOURS;
        }

        return max(7, min(365, $saisi));
    }
}
