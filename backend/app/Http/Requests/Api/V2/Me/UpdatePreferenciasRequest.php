<?php

namespace App\Http\Requests\Api\V2\Me;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePreferenciasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'tema' => ['sometimes', Rule::in(['light', 'dark', 'contrast', 'system'])],
            'idioma' => ['sometimes', 'string', 'max:10'],
            'regiao' => ['sometimes', 'nullable', 'string', 'max:40'],
            'acessibilidade' => ['sometimes', 'array'],
            'acessibilidade.*' => ['boolean'],
            'notificacoes' => ['sometimes', 'array'],
            'notificacoes.*' => ['boolean'],
        ];
    }
}
