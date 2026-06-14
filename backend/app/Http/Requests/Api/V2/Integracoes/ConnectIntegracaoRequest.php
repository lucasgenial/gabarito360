<?php

namespace App\Http\Requests\Api\V2\Integracoes;

use App\Models\Integracao;
use Illuminate\Foundation\Http\FormRequest;

class ConnectIntegracaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Integracao::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'chave' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9_\-]+$/'],
            'nome' => ['sometimes', 'nullable', 'string', 'max:180'],
            'descricao' => ['sometimes', 'nullable', 'string', 'max:500'],
            'credenciais' => ['sometimes', 'array'],
            'credenciais.*' => ['string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function credenciais(): array
    {
        /** @var array<string, string> $credenciais */
        $credenciais = (array) $this->input('credenciais', []);

        return $credenciais;
    }
}
