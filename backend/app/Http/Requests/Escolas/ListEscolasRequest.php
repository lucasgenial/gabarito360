<?php

namespace App\Http\Requests\Escolas;

use App\Enums\StatusEnum;
use App\Models\Escola;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListEscolasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Escola::class) ?? false;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'nucleo_id' => ['sometimes', 'uuid'],
            'status' => ['sometimes', Rule::enum(StatusEnum::class)],
            'search' => ['sometimes', 'string', 'max:180'],
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
