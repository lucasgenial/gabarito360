<?php

namespace App\Actions\Provas;

use App\Models\Prova;
use App\Models\User;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

class UpdateProvaAction
{
    public function __construct(
        private AuditService $audit,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function execute(Prova $exam, array $attributes, User $actor): Prova
    {
        return DB::transaction(function () use ($exam, $attributes, $actor): Prova {
            $exam->update($attributes);

            if ($exam->wasChanged()) {
                $this->audit->record(
                    action: AuditAction::EXAM_UPDATED,
                    entityType: 'prova',
                    entityId: $exam->id,
                    actorUserId: $actor->id,
                    metadata: ['campos_alterados' => array_keys($attributes)],
                    nucleoId: $exam->ownerNucleoId(),
                    escolaId: $exam->escola_id,
                );
            }

            return $exam->refresh();
        });
    }
}
