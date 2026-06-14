<?php

namespace App\Http\Requests\Api\V2\Turmas;

use App\Models\Turma;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListTurmasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Turma::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'q' => ['sometimes', 'string', 'max:120'],
            'serie' => ['sometimes', 'string', 'max:60'],
            'status' => ['sometimes', Rule::in(['em-dia', 'recuperacao', 'pendencia'])],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
