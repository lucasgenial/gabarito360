<?php

namespace App\Http\Requests\Alunos;

use App\Models\Aluno;
use App\Services\Authorization\AlunoScope;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateAlunoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $student = app(AlunoScope::class)
            ->applyManageable(Aluno::query(), $this->user())
            ->find($this->route('aluno'));

        if (! $student instanceof Aluno) {
            throw (new ModelNotFoundException)->setModel(Aluno::class, [$this->route('aluno')]);
        }

        $this->attributes->set('managed_student', $student);

        return $this->user()?->can('update', $student) ?? false;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        $student = $this->student();

        return [
            'escola_id' => ['prohibited'],
            'status' => ['prohibited'],
            'matricula' => [
                'sometimes',
                'required',
                'string',
                'max:80',
                Rule::unique('alunos', 'matricula')
                    ->where('escola_id', $student->escola_id)
                    ->whereNull('deleted_at')
                    ->ignore($student->id),
            ],
            'codigo_interno' => ['sometimes', 'nullable', 'string', 'max:80'],
            'nome' => ['sometimes', 'required', 'string', 'max:180'],
            'data_nascimento' => ['prohibited'],
            'documento' => ['prohibited'],
            'observacoes' => ['prohibited'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];

        if ($this->has('matricula')) {
            $normalized['matricula'] = Str::upper(trim((string) $this->input('matricula')));
        }

        if ($this->has('codigo_interno')) {
            $value = trim((string) $this->input('codigo_interno'));
            $normalized['codigo_interno'] = $value === '' ? null : $value;
        }

        if ($this->has('nome')) {
            $normalized['nome'] = trim((string) $this->input('nome'));
        }

        $this->merge($normalized);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! collect(['matricula', 'codigo_interno', 'nome'])
                ->contains(fn (string $field): bool => $this->exists($field))) {
                $validator->errors()->add('payload', 'Informe ao menos um campo editavel.');
            }
        });
    }

    public function student(): Aluno
    {
        /** @var Aluno $student */
        $student = $this->attributes->get('managed_student');

        return $student;
    }
}
