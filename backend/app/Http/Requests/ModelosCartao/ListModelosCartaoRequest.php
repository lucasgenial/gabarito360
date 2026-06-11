<?php

namespace App\Http\Requests\ModelosCartao;

use App\Enums\ModeloCartaoStatus;
use App\Models\ModeloCartao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListModelosCartaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', ModeloCartao::class) ?? false;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'nucleo_id' => ['sometimes', 'nullable', 'uuid'],
            'status' => ['sometimes', Rule::enum(ModeloCartaoStatus::class)],
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
