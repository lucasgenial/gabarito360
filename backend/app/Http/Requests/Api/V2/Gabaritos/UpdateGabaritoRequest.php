<?php

namespace App\Http\Requests\Api\V2\Gabaritos;

use App\Models\Prova;
use Illuminate\Foundation\Http\FormRequest;

class UpdateGabaritoRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Autoriza pela prova (o gabarito pode ainda não existir); só editável em rascunho.
        $prova = $this->route('prova');

        return $prova instanceof Prova
            && ($this->user()?->can('update', $prova) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'respostas' => ['required', 'array'],
            'respostas.*.questao' => ['required', 'integer', 'min:1'],
            'respostas.*.correta' => ['nullable', 'string', 'size:1', 'regex:/^[A-Za-z]$/'],
        ];
    }

    /**
     * @return array<int, array{questao: int, correta: ?string}>
     */
    public function respostas(): array
    {
        return array_values((array) $this->input('respostas', []));
    }
}
