<?php

namespace App\Actions\Provas;

use App\Models\Prova;
use App\Models\User;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use App\Services\Provas\ProvaConfigurationValidator;
use Illuminate\Support\Facades\DB;

class UpdateProvaAction
{
    public function __construct(
        private AuditService $audit,
        private ProvaConfigurationValidator $configurationValidator,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function execute(Prova $exam, array $attributes, User $actor): Prova
    {
        return DB::transaction(function () use ($exam, $attributes, $actor): Prova {
            $lockedExam = Prova::query()->lockForUpdate()->findOrFail($exam->id);
            $configuration = [
                ...$lockedExam->only([
                    'nucleo_id',
                    'escola_id',
                    'modelo_cartao_id',
                    'codigo',
                    'titulo',
                    'descricao',
                    'tipo',
                    'nivel',
                    'ano_referencia',
                    'quantidade_questoes',
                    'quantidade_alternativas',
                    'alternativas',
                ]),
                ...$attributes,
            ];
            $this->configurationValidator->assertValid($configuration, $lockedExam);
            $lockedExam->update($attributes);

            if ($lockedExam->wasChanged()) {
                $this->audit->record(
                    action: AuditAction::EXAM_UPDATED,
                    entityType: 'prova',
                    entityId: $lockedExam->id,
                    actorUserId: $actor->id,
                    metadata: ['campos_alterados' => array_keys($attributes)],
                    nucleoId: $lockedExam->ownerNucleoId(),
                    escolaId: $lockedExam->escola_id,
                );
            }

            return $lockedExam->refresh();
        });
    }
}
