<?php

namespace App\Http\Requests\Provas\Concerns;

use App\Models\GabaritoOficial;
use App\Models\Prova;
use App\Models\Questao;
use App\Models\User;
use App\Services\Authorization\ProvaScope;
use Illuminate\Database\Eloquent\ModelNotFoundException;

trait ResolvesScopedProva
{
    protected function resolveProva(): ?Prova
    {
        $actor = $this->user();
        $examId = $this->routeId('prova', Prova::class);

        if (! $actor instanceof User || $examId === null) {
            return null;
        }

        $exam = app(ProvaScope::class)->apply(Prova::query(), $actor)->find($examId);

        if (! $exam instanceof Prova) {
            throw (new ModelNotFoundException)->setModel(Prova::class, [$examId]);
        }

        $this->attributes->set('scoped_exam', $exam);

        return $exam;
    }

    protected function resolveQuestao(Prova $exam): ?Questao
    {
        $questionId = $this->routeId('questao', Questao::class);

        if ($questionId === null) {
            return null;
        }

        $question = Questao::query()
            ->where('prova_id', $exam->id)
            ->find($questionId);

        if (! $question instanceof Questao) {
            throw (new ModelNotFoundException)->setModel(Questao::class, [$questionId]);
        }

        $this->attributes->set('scoped_question', $question);

        return $question;
    }

    protected function resolveGabarito(Prova $exam): ?GabaritoOficial
    {
        $answerKeyId = $this->routeId('gabarito', GabaritoOficial::class);

        if ($answerKeyId === null) {
            return null;
        }

        $answerKey = GabaritoOficial::query()
            ->where('prova_id', $exam->id)
            ->find($answerKeyId);

        if (! $answerKey instanceof GabaritoOficial) {
            throw (new ModelNotFoundException)->setModel(GabaritoOficial::class, [$answerKeyId]);
        }

        $this->attributes->set('scoped_answer_key', $answerKey);

        return $answerKey;
    }

    public function prova(): Prova
    {
        /** @var Prova $exam */
        $exam = $this->attributes->get('scoped_exam');

        return $exam;
    }

    public function questao(): Questao
    {
        /** @var Questao $question */
        $question = $this->attributes->get('scoped_question');

        return $question;
    }

    public function gabarito(): GabaritoOficial
    {
        /** @var GabaritoOficial $answerKey */
        $answerKey = $this->attributes->get('scoped_answer_key');

        return $answerKey;
    }

    /** @param class-string<Prova|Questao|GabaritoOficial> $modelClass */
    private function routeId(string $parameter, string $modelClass): ?string
    {
        $value = $this->route($parameter);

        return $value instanceof $modelClass
            ? $value->id
            : (is_string($value) ? $value : null);
    }
}
