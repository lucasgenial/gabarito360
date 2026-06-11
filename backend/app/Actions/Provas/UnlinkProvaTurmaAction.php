<?php

namespace App\Actions\Provas;

use App\Models\Prova;
use App\Models\ProvaTurma;
use App\Models\User;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

class UnlinkProvaTurmaAction
{
    public function __construct(
        private AuditService $audit,
    ) {}

    public function execute(Prova $exam, ProvaTurma $link, User $actor): void
    {
        DB::transaction(function () use ($exam, $link, $actor): void {
            $lockedLink = ProvaTurma::query()
                ->with('turma.escola')
                ->where('prova_id', $exam->id)
                ->lockForUpdate()
                ->findOrFail($link->id);
            $before = $lockedLink->only(['prova_id', 'turma_id', 'data_prevista']);
            $class = $lockedLink->turma;

            $lockedLink->delete();

            $this->audit->record(
                action: AuditAction::EXAM_CLASS_UNLINKED,
                entityType: 'prova_turma',
                entityId: $lockedLink->id,
                actorUserId: $actor->id,
                before: $before,
                nucleoId: $class->escola->nucleo_id,
                escolaId: $class->escola_id,
            );
        });
    }
}
