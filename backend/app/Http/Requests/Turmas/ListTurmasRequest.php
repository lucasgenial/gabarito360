<?php

namespace App\Http\Requests\Turmas;

use App\Enums\StatusEnum;
use App\Models\Turma;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListTurmasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Turma::class) ?? false;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'escola_id' => ['sometimes', 'uuid'],
            'ano_letivo' => ['sometimes', 'integer', 'between:2000,2100'],
            'status' => ['sometimes', Rule::enum(StatusEnum::class)],
            'search' => ['sometimes', 'string', 'max:120'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('search')) {
            $this->merge(['search' => trim((string) $this->input('search'))]);
        }
    }
}
