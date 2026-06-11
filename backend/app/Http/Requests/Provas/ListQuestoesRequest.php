<?php

namespace App\Http\Requests\Provas;

use App\Enums\QuestaoStatus;
use App\Http\Requests\Provas\Concerns\ResolvesScopedProva;
use App\Models\Prova;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListQuestoesRequest extends FormRequest
{
    use ResolvesScopedProva;

    public function authorize(): bool
    {
        $exam = $this->resolveProva();

        return $exam instanceof Prova
            && ($this->user()?->can('view', $exam) ?? false);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', Rule::enum(QuestaoStatus::class)],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
