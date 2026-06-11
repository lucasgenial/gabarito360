<?php

namespace App\Services\Gabaritos;

use App\Enums\QuestaoStatus;
use App\Models\GabaritoOficial;

class GabaritoCompletenessValidator
{
    /** @return array<string, mixed> */
    public function validate(GabaritoOficial $answerKey): array
    {
        $answerKey->loadMissing('prova');
        $exam = $answerKey->prova;
        $questions = $exam->questoes()
            ->where('status', QuestaoStatus::ACTIVE)
            ->orderBy('numero')
            ->get(['id', 'numero']);
        $answeredQuestionIds = $answerKey->respostas()
            ->whereIn('questao_id', $questions->pluck('id'))
            ->pluck('questao_id');
        $missingNumbers = $questions
            ->whereNotIn('id', $answeredQuestionIds)
            ->pluck('numero')
            ->values()
            ->all();
        $problems = [];

        if ($questions->count() !== $exam->quantidade_questoes) {
            $problems[] = 'QUANTIDADE_QUESTOES_ATIVAS_DIVERGENTE';
        }

        if ($missingNumbers !== []) {
            $problems[] = 'RESPOSTAS_OFICIAIS_INCOMPLETAS';
        }

        return [
            'completo' => $problems === [],
            'questoes_esperadas' => $exam->quantidade_questoes,
            'questoes_ativas' => $questions->count(),
            'respostas_registradas' => $answeredQuestionIds->count(),
            'questoes_sem_resposta' => $missingNumbers,
            'problemas' => $problems,
        ];
    }
}
