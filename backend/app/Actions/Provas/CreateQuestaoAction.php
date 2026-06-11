<?php

namespace App\Actions\Provas;

use App\Enums\QuestaoStatus;
use App\Models\Prova;
use App\Models\Questao;
use App\Models\User;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

class CreateQuestaoAction
{
    public function __construct(
        private AuditService $audit,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function execute(Prova $exam, array $attributes, User $actor): Questao
    {
        return DB::transaction(function () use ($exam, $attributes, $actor): Questao {
            $question = $exam->questoes()->create([
                ...$attributes,
                'status' => QuestaoStatus::ACTIVE,
            ])->refresh();

            $this->audit->record(
                action: AuditAction::QUESTION_CREATED,
                entityType: 'questao',
                entityId: $question->id,
                actorUserId: $actor->id,
                after: $question->only(['prova_id', 'numero', 'codigo', 'peso_padrao', 'status']),
                metadata: ['prova_id' => $exam->id],
                nucleoId: $exam->ownerNucleoId(),
                escolaId: $exam->escola_id,
            );

            return $question;
        });
    }
}
