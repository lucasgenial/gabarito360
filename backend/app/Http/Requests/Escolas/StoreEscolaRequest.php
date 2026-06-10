<?php

namespace App\Http\Requests\Escolas;

use App\Enums\StatusEnum;
use App\Models\Escola;
use App\Models\Nucleo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreEscolaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $nucleoId = $this->input('nucleo_id');

        if (is_string($nucleoId) && Str::isUuid($nucleoId)) {
            $nucleo = Nucleo::query()->find($nucleoId);

            return $nucleo instanceof Nucleo
                && ($this->user()?->can('create', [Escola::class, $nucleo]) ?? false);
        }

        return $this->user()?->can('viewAny', Escola::class) ?? false;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'nucleo_id' => [
                'required',
                'uuid',
                Rule::exists('nucleos', 'id')
                    ->where('status', StatusEnum::ACTIVE->value)
                    ->whereNull('deleted_at'),
            ],
            'codigo' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z0-9._-]+$/',
                Rule::unique('escolas', 'codigo')
                    ->where('nucleo_id', $this->input('nucleo_id'))
                    ->whereNull('deleted_at'),
            ],
            'nome' => ['required', 'string', 'max:180'],
            'municipio' => ['required', 'string', 'max:120'],
            'estado' => ['required', 'string', 'size:2', 'regex:/^[A-Z]{2}$/'],
            'endereco' => ['nullable', 'array:logradouro,numero,complemento,bairro,cep'],
            'endereco.logradouro' => ['nullable', 'string', 'max:180'],
            'endereco.numero' => ['nullable', 'string', 'max:30'],
            'endereco.complemento' => ['nullable', 'string', 'max:120'],
            'endereco.bairro' => ['nullable', 'string', 'max:120'],
            'endereco.cep' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'string', 'email:rfc', 'max:254'],
            'telefone' => ['nullable', 'string', 'max:30'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalized = [
            'codigo' => Str::upper(trim((string) $this->input('codigo'))),
            'nome' => trim((string) $this->input('nome')),
            'municipio' => trim((string) $this->input('municipio')),
            'estado' => Str::upper(trim((string) $this->input('estado'))),
            'email' => $this->nullableLowercase('email'),
            'telefone' => $this->nullableTrimmed('telefone'),
        ];

        if (is_array($this->input('endereco'))) {
            $normalized['endereco'] = $this->normalizedAddress();
        }

        $this->merge($normalized);
    }

    /** @return array<string, string|null> */
    private function normalizedAddress(): array
    {
        return collect($this->input('endereco'))
            ->map(fn (mixed $value): ?string => is_string($value) && trim($value) !== '' ? trim($value) : null)
            ->all();
    }

    private function nullableTrimmed(string $field): ?string
    {
        $value = trim((string) $this->input($field));

        return $value === '' ? null : $value;
    }

    private function nullableLowercase(string $field): ?string
    {
        $value = $this->nullableTrimmed($field);

        return $value === null ? null : Str::lower($value);
    }
}
