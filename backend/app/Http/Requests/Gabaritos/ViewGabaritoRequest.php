<?php

namespace App\Http\Requests\Gabaritos;

use App\Http\Requests\Provas\Concerns\ResolvesScopedProva;
use App\Models\GabaritoOficial;
use App\Models\Prova;
use Illuminate\Foundation\Http\FormRequest;

class ViewGabaritoRequest extends FormRequest
{
    use ResolvesScopedProva;

    public function authorize(): bool
    {
        $exam = $this->resolveProva();
        $answerKey = $exam instanceof Prova ? $this->resolveGabarito($exam) : null;

        return $answerKey instanceof GabaritoOficial
            && ($this->user()?->can('view', $answerKey) ?? false);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [];
    }
}
