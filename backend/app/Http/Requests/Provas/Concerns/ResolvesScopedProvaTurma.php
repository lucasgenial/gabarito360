<?php

namespace App\Http\Requests\Provas\Concerns;

use App\Models\Prova;
use App\Models\ProvaTurma;
use App\Models\Turma;
use App\Models\User;
use App\Services\Authorization\ProvaTurmaScope;
use Illuminate\Database\Eloquent\ModelNotFoundException;

trait ResolvesScopedProvaTurma
{
    protected function resolveScopedProva(): ?Prova
    {
        $actor = $this->user();
        $examId = $this->routeId('prova', Prova::class);

        if (! $actor instanceof User || $examId === null) {
            return null;
        }

        $exam = app(ProvaTurmaScope::class)
            ->applyProvas(Prova::query(), $actor)
            ->find($examId);

        if (! $exam instanceof Prova) {
            throw (new ModelNotFoundException)->setModel(Prova::class, [$examId]);
        }

        $this->attributes->set('scoped_class_exam', $exam);

        return $exam;
    }

    protected function resolveProvaTurma(Prova $exam): ?ProvaTurma
    {
        $classId = $this->routeId('turma', Turma::class);

        if ($classId === null) {
            return null;
        }

        $link = $exam->provaTurmas()
            ->with('turma.escola')
            ->where('turma_id', $classId)
            ->first();

        if (! $link instanceof ProvaTurma) {
            throw (new ModelNotFoundException)->setModel(ProvaTurma::class, [$classId]);
        }

        $this->attributes->set('scoped_exam_class_link', $link);

        return $link;
    }

    public function prova(): Prova
    {
        /** @var Prova $exam */
        $exam = $this->attributes->get('scoped_class_exam');

        return $exam;
    }

    public function provaTurma(): ProvaTurma
    {
        /** @var ProvaTurma $link */
        $link = $this->attributes->get('scoped_exam_class_link');

        return $link;
    }

    /** @param class-string<Prova|Turma> $modelClass */
    private function routeId(string $parameter, string $modelClass): ?string
    {
        $value = $this->route($parameter);

        return $value instanceof $modelClass
            ? $value->id
            : (is_string($value) ? $value : null);
    }
}
