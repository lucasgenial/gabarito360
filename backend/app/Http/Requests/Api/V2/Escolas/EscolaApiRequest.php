<?php

namespace App\Http\Requests\Api\V2\Escolas;

use App\Enums\StatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Base das requests de escola que mapeiam o shape do contrato V2
 * (cidade/uf/endereco/rede/diretor/ativa) para as colunas da tabela
 * (municipio/estado/endereco_texto/rede/diretor/status).
 */
abstract class EscolaApiRequest extends FormRequest
{
    /** @return array<string, mixed> */
    protected function fieldRules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:180'],
            'inep' => ['nullable', 'string', 'max:20'],
            'rede' => ['nullable', Rule::in(['estadual', 'municipal', 'federal', 'privada'])],
            'endereco' => ['nullable', 'string', 'max:255'],
            'cidade' => ['required', 'string', 'max:120'],
            'uf' => ['required', 'string', 'size:2', 'regex:/^[A-Z]{2}$/'],
            'telefone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email:rfc', 'max:254'],
            'diretor' => ['nullable', 'string', 'max:180'],
            'ativa' => ['sometimes', 'boolean'],
        ];
    }

    protected function normalize(): void
    {
        $this->merge(array_filter([
            'nome' => $this->has('nome') ? trim((string) $this->input('nome')) : null,
            'cidade' => $this->has('cidade') ? trim((string) $this->input('cidade')) : null,
            'uf' => $this->has('uf') ? Str::upper(trim((string) $this->input('uf'))) : null,
            'email' => $this->has('email') ? Str::lower(trim((string) $this->input('email'))) : null,
        ], fn ($value) => $value !== null));
    }

    /** @return array<string, mixed> */
    protected function mappedEscolaAttributes(): array
    {
        $ativa = $this->input('ativa', true);

        return [
            'nome' => $this->input('nome'),
            'inep' => $this->input('inep'),
            'rede' => $this->input('rede'),
            'municipio' => $this->input('cidade'),
            'estado' => $this->input('uf'),
            'endereco_texto' => $this->input('endereco'),
            'diretor' => $this->input('diretor'),
            'email' => $this->input('email'),
            'telefone' => $this->input('telefone'),
            'status' => filter_var($ativa, FILTER_VALIDATE_BOOLEAN)
                ? StatusEnum::ACTIVE->value
                : StatusEnum::INACTIVE->value,
        ];
    }
}
