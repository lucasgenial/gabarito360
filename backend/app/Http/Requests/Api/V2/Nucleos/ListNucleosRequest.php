<?php

namespace App\Http\Requests\Api\V2\Nucleos;

use App\Models\Nucleo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListNucleosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Nucleo::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'q' => ['sometimes', 'string', 'max:120'],
            'status' => ['sometimes', Rule::in(['ativo', 'inativo'])],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
