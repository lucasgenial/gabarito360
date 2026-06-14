<?php

namespace App\Http\Requests\Api\V2\Turmas;

use App\Enums\StatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Mapeia o shape do contrato V2 (serie/periodo_letivo) para as colunas da
 * tabela `turmas` (serie_ano/ano_letivo) e gera o código quando ausente.
 */
abstract class TurmaApiRequest extends FormRequest
{
    /** @return array<string, mixed> */
    protected function fieldRules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:120'],
            'serie' => ['required', 'string', 'max:60'],
            'escola_id' => [
                'required', 'uuid',
                Rule::exists('escolas', 'id')->where('status', StatusEnum::ACTIVE->value)->whereNull('deleted_at'),
            ],
            'periodo_letivo' => ['required', 'string', 'max:20', 'regex:/\d{4}/'],
            'turno' => ['nullable', Rule::in(['matutino', 'vespertino', 'noturno', 'integral'])],
            'codigo' => ['nullable', 'string', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->has('periodo_letivo')) {
                $ano = $this->anoLetivo();

                if ($ano < 2000 || $ano > 2100) {
                    $validator->errors()->add('periodo_letivo', 'Periodo letivo deve conter um ano entre 2000 e 2100.');
                }
            }
        });
    }

    protected function anoLetivo(): int
    {
        preg_match('/(\d{4})/', (string) $this->input('periodo_letivo'), $matches);

        return (int) ($matches[1] ?? 0);
    }

    /** @return array<string, mixed> */
    protected function mappedTurmaAttributes(): array
    {
        $codigo = $this->input('codigo');

        return [
            'nome' => trim((string) $this->input('nome')),
            'serie_ano' => trim((string) $this->input('serie')),
            'escola_id' => $this->input('escola_id'),
            'ano_letivo' => $this->anoLetivo(),
            'turno' => $this->input('turno'),
            'codigo' => is_string($codigo) && $codigo !== ''
                ? Str::upper(trim($codigo))
                : 'TUR-'.Str::upper(Str::random(6)),
            'status' => StatusEnum::ACTIVE->value,
        ];
    }
}
