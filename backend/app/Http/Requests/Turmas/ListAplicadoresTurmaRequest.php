<?php

namespace App\Http\Requests\Turmas;

use App\Enums\AplicadorTurmaPapel;
use App\Models\Turma;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListAplicadoresTurmaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $class = $this->route('turma');

        return $class instanceof Turma
            && ($this->user()?->can('closeStaff', $class) ?? false);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'papel' => ['sometimes', Rule::enum(AplicadorTurmaPapel::class)],
            'ativos' => ['sometimes', 'boolean'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
