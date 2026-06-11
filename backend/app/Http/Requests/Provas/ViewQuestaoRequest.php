<?php

namespace App\Http\Requests\Provas;

use App\Http\Requests\Provas\Concerns\ResolvesScopedProva;
use App\Models\Prova;
use App\Models\Questao;
use Illuminate\Foundation\Http\FormRequest;

class ViewQuestaoRequest extends FormRequest
{
    use ResolvesScopedProva;

    public function authorize(): bool
    {
        $exam = $this->resolveProva();
        $question = $exam instanceof Prova ? $this->resolveQuestao($exam) : null;

        return $question instanceof Questao
            && ($this->user()?->can('view', $exam) ?? false);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [];
    }
}
