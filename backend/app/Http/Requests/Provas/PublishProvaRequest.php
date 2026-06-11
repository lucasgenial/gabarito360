<?php

namespace App\Http\Requests\Provas;

use App\Http\Requests\Provas\Concerns\ResolvesScopedProva;
use App\Models\GabaritoOficial;
use App\Models\Prova;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PublishProvaRequest extends FormRequest
{
    use ResolvesScopedProva;

    public function authorize(): bool
    {
        $exam = $this->resolveProva();

        return $exam instanceof Prova
            && ($this->user()?->can('publish', $exam) ?? false);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'gabarito_oficial_id' => ['required', 'uuid'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $answerKey = GabaritoOficial::query()
                ->where('prova_id', $this->prova()->id)
                ->find($this->input('gabarito_oficial_id'));

            if (! $answerKey instanceof GabaritoOficial) {
                $validator->errors()->add(
                    'gabarito_oficial_id',
                    'O gabarito oficial deve pertencer a prova informada.',
                );

                return;
            }

            $this->attributes->set('selected_answer_key', $answerKey);
        });
    }

    public function gabaritoOficial(): GabaritoOficial
    {
        /** @var GabaritoOficial $answerKey */
        $answerKey = $this->attributes->get('selected_answer_key');

        return $answerKey;
    }
}
