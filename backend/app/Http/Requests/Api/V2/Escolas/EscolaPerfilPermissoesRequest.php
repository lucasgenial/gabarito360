<?php

namespace App\Http\Requests\Api\V2\Escolas;

use App\Enums\PermissionCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EscolaPerfilPermissoesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'permissoes' => ['required', 'array'],
            'permissoes.*.chave' => ['required', 'string', Rule::in(array_column(PermissionCode::cases(), 'value'))],
            'permissoes.*.permitido' => ['required', 'boolean'],
        ];
    }
}
