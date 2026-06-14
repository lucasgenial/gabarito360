<?php

namespace App\Http\Requests\Api\V2\Provas;

use App\Models\Prova;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListProvasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Prova::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'q' => ['sometimes', 'string', 'max:120'],
            'disciplina' => ['sometimes', 'string', 'max:120'],
            'status' => ['sometimes', Rule::in(['rascunho', 'publicada', 'correcao', 'corrigida'])],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
