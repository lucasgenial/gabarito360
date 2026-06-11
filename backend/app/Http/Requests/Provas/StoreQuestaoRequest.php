<?php

namespace App\Http\Requests\Provas;

use App\Http\Requests\Provas\Concerns\ResolvesScopedProva;
use App\Models\Prova;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreQuestaoRequest extends FormRequest
{
    use ResolvesScopedProva;

    public function authorize(): bool
    {
        $exam = $this->resolveProva();

        return $exam instanceof Prova
            && ($this->user()?->can('update', $exam) ?? false);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'numero' => [
                'required',
                'integer',
                'min:1',
                'max:'.$this->prova()->quantidade_questoes,
                Rule::unique('questoes', 'numero')->where('prova_id', $this->prova()->id),
            ],
            'codigo' => ['nullable', 'string', 'max:50', 'regex:/^[A-Z0-9._-]+$/'],
            'peso_padrao' => ['sometimes', 'numeric', 'min:0', 'max:999999'],
            'prova_id' => ['prohibited'],
            'status' => ['prohibited'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $code = trim((string) $this->input('codigo'));

        $this->merge([
            'codigo' => $code === '' ? null : Str::upper($code),
        ]);
    }
}
