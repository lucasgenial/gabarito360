<?php

namespace App\Actions\Gabaritos;

use App\Enums\GabaritoOficialStatus;
use App\Enums\ProvaStatus;
use App\Models\GabaritoOficial;
use App\Models\Prova;
use App\Models\User;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateGabaritoOficialAction
{
    public function __construct(
        private AuditService $audit,
    ) {}

    public function execute(Prova $exam, User $actor): GabaritoOficial
    {
        return DB::transaction(function () use ($exam, $actor): GabaritoOficial {
            $lockedExam = Prova::query()->lockForUpdate()->findOrFail($exam->id);

            if ($lockedExam->status !== ProvaStatus::DRAFT) {
                throw ValidationException::withMessages([
                    'prova' => ['Gabaritos somente podem ser criados para prova em rascunho.'],
                ]);
            }

            $version = ((int) $lockedExam->gabaritosOficiais()->max('versao')) + 1;
            $answerKey = $lockedExam->gabaritosOficiais()->create([
                'versao' => $version,
                'status' => GabaritoOficialStatus::DRAFT,
                'criado_por' => $actor->id,
            ])->refresh();

            $this->audit->record(
                action: AuditAction::ANSWER_KEY_CREATED,
                entityType: 'gabarito_oficial',
                entityId: $answerKey->id,
                actorUserId: $actor->id,
                after: $answerKey->only(['prova_id', 'versao', 'status']),
                nucleoId: $lockedExam->ownerNucleoId(),
                escolaId: $lockedExam->escola_id,
            );

            return $answerKey;
        });
    }
}
