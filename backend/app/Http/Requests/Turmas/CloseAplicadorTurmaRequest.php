<?php

namespace App\Http\Requests\Turmas;

use App\Models\AplicadorTurma;
use App\Models\Turma;
use Illuminate\Foundation\Http\FormRequest;

class CloseAplicadorTurmaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $class = $this->route('turma');
        $link = $this->route('vinculo');

        return $class instanceof Turma
            && $link instanceof AplicadorTurma
            && $link->fim_em === null
            && ($this->user()?->can('closeStaff', $class) ?? false);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        /** @var AplicadorTurma $link */
        $link = $this->route('vinculo');

        return [
            'fim_em' => ['required', 'date', 'after_or_equal:'.$link->inicio_em->toDateString()],
            'turma_id' => ['prohibited'],
            'usuario_id' => ['prohibited'],
            'papel' => ['prohibited'],
            'inicio_em' => ['prohibited'],
            'vinculado_por' => ['prohibited'],
        ];
    }
}
