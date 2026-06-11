<?php

namespace App\Actions\Provas;

use App\Models\Prova;
use App\Models\Questao;
use App\Models\User;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

class UpdateQuestaoAction
{
    public function __construct(
        private AuditService $audit,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function execute(Prova $exam, Questao $question, array $attributes, User $actor): Questao
    {
        return DB::transaction(function () use ($exam, $question, $attributes, $actor): Questao {
            $question->update($attributes);

            if ($question->wasChanged()) {
                $this->audit->record(
                    action: AuditAction::QUESTION_UPDATED,
                    entityType: 'questao',
                    entityId: $question->id,
                    actorUserId: $actor->id,
                    metadata: [
                        'prova_id' => $exam->id,
                        'campos_alterados' => array_keys($attributes),
                    ],
                    nucleoId: $exam->ownerNucleoId(),
                    escolaId: $exam->escola_id,
                );
            }

            return $question->refresh();
        });
    }
}
