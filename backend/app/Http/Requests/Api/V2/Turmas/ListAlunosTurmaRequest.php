<?php

namespace App\Http\Requests\Api\V2\Turmas;

use App\Models\Turma;
use Illuminate\Foundation\Http\FormRequest;

class ListAlunosTurmaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $turma = $this->route('turma');

        return $turma instanceof Turma
            && ($this->user()?->can('view', $turma) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'q' => ['sometimes', 'string', 'max:120'],
            'status' => ['sometimes', 'string', 'max:30'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
