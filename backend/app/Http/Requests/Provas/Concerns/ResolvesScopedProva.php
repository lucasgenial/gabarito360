<?php

namespace App\Http\Requests\Provas\Concerns;

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

    /** @param class-string<Prova|Questao> $modelClass */
    private function routeId(string $parameter, string $modelClass): ?string
    {
        $value = $this->route($parameter);

        return $value instanceof $modelClass
            ? $value->id
            : (is_string($value) ? $value : null);
    }
}
