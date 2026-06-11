<?php

namespace App\Http\Requests\Turmas;

use App\Models\Turma;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateTurmaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $turma = $this->route('turma');

        return $turma instanceof Turma
            && ($this->user()?->can('update', $turma) ?? false);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        /** @var Turma $turma */
        $turma = $this->route('turma');

        return [
            'escola_id' => ['prohibited'],
            'ano_letivo' => ['prohibited'],
            'status' => ['prohibited'],
            'codigo' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z0-9._-]+$/',
                Rule::unique('turmas', 'codigo')
                    ->where('escola_id', $turma->escola_id)
                    ->where('ano_letivo', $turma->ano_letivo)
                    ->whereNull('deleted_at')
                    ->ignore($turma->id),
            ],
            'nome' => ['sometimes', 'required', 'string', 'max:120'],
            'serie_ano' => ['sometimes', 'required', 'string', 'max:60'],
            'turno' => ['sometimes', 'nullable', Rule::in(['matutino', 'vespertino', 'noturno', 'integral'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];

        foreach (['nome', 'serie_ano'] as $field) {
            if ($this->has($field)) {
                $normalized[$field] = trim((string) $this->input($field));
            }
        }

        if ($this->has('codigo')) {
            $normalized['codigo'] = Str::upper(trim((string) $this->input('codigo')));
        }

        if ($this->has('turno')) {
            $turno = trim((string) $this->input('turno'));
            $normalized['turno'] = $turno === '' ? null : Str::lower($turno);
        }

        $this->merge($normalized);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! collect(['codigo', 'nome', 'serie_ano', 'turno'])
                ->contains(fn (string $field): bool => $this->exists($field))) {
                $validator->errors()->add('payload', 'Informe ao menos um campo editavel.');
            }
        });
    }
}
