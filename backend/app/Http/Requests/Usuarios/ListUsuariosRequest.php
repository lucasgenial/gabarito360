<?php

namespace App\Http\Requests\Usuarios;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ListUsuariosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', User::class) ?? false;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', Rule::enum(UserStatus::class)],
            'nucleo_id' => ['sometimes', 'uuid'],
            'escola_id' => ['sometimes', 'uuid'],
            'search' => ['sometimes', 'string', 'max:180'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('search')) {
            $this->merge(['search' => Str::lower(trim((string) $this->input('search')))]);
        }
    }
}
