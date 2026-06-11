<?php

namespace App\Http\Requests\Gabaritos;

use App\Enums\QuestaoStatus;
use App\Http\Requests\Provas\Concerns\ResolvesScopedProva;
use App\Models\GabaritoOficial;
use App\Models\Prova;
use App\Models\Questao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;

class UpsertGabaritoRespostaRequest extends FormRequest
{
    use ResolvesScopedProva;

    public function authorize(): bool
    {
        $exam = $this->resolveProva();
        $answerKey = $exam instanceof Prova ? $this->resolveGabarito($exam) : null;
        $question = $exam instanceof Prova ? $this->resolveQuestao($exam) : null;

        return $answerKey instanceof GabaritoOficial
            && $question instanceof Questao
            && ($this->user()?->can('update', $answerKey) ?? false);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'alternativa_correta' => ['nullable', 'string', 'max:10'],
            'anulada' => ['required', 'boolean'],
            'peso' => ['sometimes', 'required', 'numeric', 'min:0', 'max:999999'],
            'prova_id' => ['prohibited'],
            'gabarito_oficial_id' => ['prohibited'],
            'questao_id' => ['prohibited'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('alternativa_correta')) {
            $alternative = trim((string) $this->input('alternativa_correta'));
            $this->merge([
                'alternativa_correta' => $alternative === '' ? null : Str::upper($alternative),
            ]);
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if ($this->questao()->status !== QuestaoStatus::ACTIVE) {
                $validator->errors()->add('questao', 'A resposta oficial exige uma questao ativa.');
            }

            $annulled = (bool) $this->boolean('anulada');
            $alternative = $this->input('alternativa_correta');

            if ($annulled && $alternative !== null) {
                $validator->errors()->add('alternativa_correta', 'Questao anulada nao deve possuir alternativa correta.');
            }

            if (! $annulled && ! is_string($alternative)) {
                $validator->errors()->add('alternativa_correta', 'Informe a alternativa correta da questao.');
            } elseif (! $annulled && ! in_array($alternative, $this->prova()->alternativas, strict: true)) {
                $validator->errors()->add('alternativa_correta', 'A alternativa correta deve pertencer as alternativas da prova.');
            }
        });
    }
}
