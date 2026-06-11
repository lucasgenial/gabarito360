<?php

namespace App\Http\Requests\Alunos;

use App\Enums\StatusEnum;
use App\Models\Aluno;
use App\Models\Escola;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreAlunoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $schoolId = $this->input('escola_id');

        if (is_string($schoolId) && Str::isUuid($schoolId)) {
            $school = Escola::query()->find($schoolId);

            return $school instanceof Escola
                && ($this->user()?->can('create', [Aluno::class, $school]) ?? false);
        }

        return $this->user()?->can('manageAny', Aluno::class) ?? false;
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
            'matricula' => [
                'required',
                'string',
                'max:80',
                Rule::unique('alunos', 'matricula')
                    ->where('escola_id', $this->input('escola_id'))
                    ->whereNull('deleted_at'),
            ],
            'codigo_interno' => ['nullable', 'string', 'max:80'],
            'nome' => ['required', 'string', 'max:180'],
            'data_nascimento' => ['prohibited'],
            'documento' => ['prohibited'],
            'observacoes' => ['prohibited'],
            'status' => ['prohibited'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'matricula' => Str::upper(trim((string) $this->input('matricula'))),
            'codigo_interno' => $this->nullableTrimmed('codigo_interno'),
            'nome' => trim((string) $this->input('nome')),
        ]);
    }

    private function nullableTrimmed(string $field): ?string
    {
        $value = trim((string) $this->input($field));

        return $value === '' ? null : $value;
    }
}
