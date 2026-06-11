<?php

namespace App\Http\Requests\Alunos;

use App\Models\Aluno;
use App\Services\Authorization\AlunoScope;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Http\FormRequest;

class ManageAlunoRequest extends FormRequest
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

        return $this->user()?->can('delete', $student) ?? false;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [];
    }

    public function student(): Aluno
    {
        /** @var Aluno $student */
        $student = $this->attributes->get('managed_student');

        return $student;
    }
}
