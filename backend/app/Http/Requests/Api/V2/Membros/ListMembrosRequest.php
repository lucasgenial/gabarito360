<?php

namespace App\Http\Requests\Api\V2\Membros;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class ListMembrosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', User::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'q' => ['sometimes', 'string', 'max:120'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
