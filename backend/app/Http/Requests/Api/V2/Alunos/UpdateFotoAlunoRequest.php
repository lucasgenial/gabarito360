<?php

namespace App\Http\Requests\Api\V2\Alunos;

use App\Models\Aluno;
use Illuminate\Foundation\Http\FormRequest;

class UpdateFotoAlunoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $aluno = $this->route('aluno');

        return $aluno instanceof Aluno
            && ($this->user()?->can('update', $aluno) ?? false);
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'foto' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:5120'],
        ];
    }
}
