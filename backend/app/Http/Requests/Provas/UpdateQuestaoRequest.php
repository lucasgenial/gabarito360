<?php

namespace App\Http\Requests\Provas;

use App\Http\Requests\Provas\Concerns\ResolvesScopedProva;
use App\Models\Prova;
use App\Models\Questao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateQuestaoRequest extends FormRequest
{
    use ResolvesScopedProva;

    public function authorize(): bool
    {
        $exam = $this->resolveProva();
        $question = $exam instanceof Prova ? $this->resolveQuestao($exam) : null;

        return $question instanceof Questao
            && ($this->user()?->can('update', $exam) ?? false);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'numero' => [
                'sometimes',
                'required',
                'integer',
                'min:1',
                'max:'.$this->prova()->quantidade_questoes,
                Rule::unique('questoes', 'numero')
                    ->where('prova_id', $this->prova()->id)
                    ->ignore($this->questao()->id),
            ],
            'codigo' => ['sometimes', 'nullable', 'string', 'max:50', 'regex:/^[A-Z0-9._-]+$/'],
            'peso_padrao' => ['sometimes', 'numeric', 'min:0', 'max:999999'],
            'prova_id' => ['prohibited'],
            'status' => ['prohibited'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('codigo')) {
            $code = trim((string) $this->input('codigo'));
            $this->merge(['codigo' => $code === '' ? null : Str::upper($code)]);
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! collect(['numero', 'codigo', 'peso_padrao'])
                ->contains(fn (string $field): bool => $this->exists($field))) {
                $validator->errors()->add('payload', 'Informe ao menos um campo editavel.');
            }
        });
    }
}
