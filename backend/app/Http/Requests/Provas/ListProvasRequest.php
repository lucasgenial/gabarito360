<?php

namespace App\Http\Requests\Provas;

use App\Enums\ProvaStatus;
use App\Models\Prova;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListProvasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Prova::class) ?? false;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'nucleo_id' => ['sometimes', 'uuid'],
            'escola_id' => ['sometimes', 'uuid'],
            'modelo_cartao_id' => ['sometimes', 'uuid'],
            'status' => ['sometimes', Rule::enum(ProvaStatus::class)],
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
