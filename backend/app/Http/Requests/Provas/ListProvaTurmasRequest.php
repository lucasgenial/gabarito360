<?php

namespace App\Http\Requests\Provas;

use App\Http\Requests\Provas\Concerns\ResolvesScopedProvaTurma;
use App\Models\Prova;
use Illuminate\Foundation\Http\FormRequest;

class ListProvaTurmasRequest extends FormRequest
{
    use ResolvesScopedProvaTurma;

    public function authorize(): bool
    {
        $exam = $this->resolveScopedProva();

        return $exam instanceof Prova
            && ($this->user()?->can('viewClassLinks', $exam) ?? false);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
