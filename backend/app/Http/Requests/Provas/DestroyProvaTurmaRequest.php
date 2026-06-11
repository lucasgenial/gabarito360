<?php

namespace App\Http\Requests\Provas;

use App\Http\Requests\Provas\Concerns\ResolvesScopedProvaTurma;
use App\Models\Prova;
use App\Models\ProvaTurma;
use Illuminate\Foundation\Http\FormRequest;

class DestroyProvaTurmaRequest extends FormRequest
{
    use ResolvesScopedProvaTurma;

    public function authorize(): bool
    {
        $exam = $this->resolveScopedProva();
        $link = $exam instanceof Prova ? $this->resolveProvaTurma($exam) : null;

        return $link instanceof ProvaTurma
            && ($this->user()?->can('unlinkClass', [$exam, $link->turma]) ?? false);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [];
    }
}
