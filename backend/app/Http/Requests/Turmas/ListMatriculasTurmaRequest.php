<?php

namespace App\Http\Requests\Turmas;

use App\Enums\MatriculaTurmaStatus;
use App\Models\Turma;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListMatriculasTurmaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $turma = $this->route('turma');

        return $turma instanceof Turma
            && ($this->user()?->can('view', $turma) ?? false);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', Rule::enum(MatriculaTurmaStatus::class)],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
