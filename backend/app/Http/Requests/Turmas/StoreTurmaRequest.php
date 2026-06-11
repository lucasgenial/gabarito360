<?php

namespace App\Http\Requests\Turmas;

use App\Enums\StatusEnum;
use App\Models\Escola;
use App\Models\Turma;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreTurmaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $schoolId = $this->input('escola_id');

        if (is_string($schoolId) && Str::isUuid($schoolId)) {
            $school = Escola::query()->find($schoolId);

            return $school instanceof Escola
                && ($this->user()?->can('create', [Turma::class, $school]) ?? false);
        }

        return $this->user()?->can('viewAny', Turma::class) ?? false;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'escola_id' => [
                'required',
                'uuid',
                Rule::exists('escolas', 'id')
                    ->where('status', StatusEnum::ACTIVE->value)
                    ->whereNull('deleted_at'),
            ],
            'codigo' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z0-9._-]+$/',
                Rule::unique('turmas', 'codigo')
                    ->where('escola_id', $this->input('escola_id'))
                    ->where('ano_letivo', $this->input('ano_letivo'))
                    ->whereNull('deleted_at'),
            ],
            'nome' => ['required', 'string', 'max:120'],
            'serie_ano' => ['required', 'string', 'max:60'],
            'turno' => ['nullable', Rule::in(['matutino', 'vespertino', 'noturno', 'integral'])],
            'ano_letivo' => ['required', 'integer', 'between:2000,2100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'codigo' => Str::upper(trim((string) $this->input('codigo'))),
            'nome' => trim((string) $this->input('nome')),
            'serie_ano' => trim((string) $this->input('serie_ano')),
            'turno' => $this->nullableLowercase('turno'),
        ]);
    }

    private function nullableLowercase(string $field): ?string
    {
        $value = trim((string) $this->input($field));

        return $value === '' ? null : Str::lower($value);
    }
}
