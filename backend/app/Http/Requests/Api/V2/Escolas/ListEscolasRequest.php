<?php

namespace App\Http\Requests\Api\V2\Escolas;

use App\Models\Escola;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListEscolasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Escola::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'q' => ['sometimes', 'string', 'max:120'],
            'status' => ['sometimes', Rule::in(['ativa', 'inativa'])],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
