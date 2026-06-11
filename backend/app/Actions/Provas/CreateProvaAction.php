<?php

namespace App\Actions\Provas;

use App\Enums\ProvaStatus;
use App\Models\Prova;
use App\Models\User;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

class CreateProvaAction
{
    public function __construct(
        private AuditService $audit,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function execute(array $attributes, User $actor): Prova
    {
        return DB::transaction(function () use ($attributes, $actor): Prova {
            $exam = Prova::query()->create([
                ...$attributes,
                'status' => ProvaStatus::DRAFT,
                'criado_por' => $actor->id,
            ])->refresh();

            $this->audit->record(
                action: AuditAction::EXAM_CREATED,
                entityType: 'prova',
                entityId: $exam->id,
                actorUserId: $actor->id,
                after: $exam->only([
                    'nucleo_id',
                    'escola_id',
                    'modelo_cartao_id',
                    'codigo',
                    'quantidade_questoes',
                    'quantidade_alternativas',
                    'status',
                ]),
                nucleoId: $exam->ownerNucleoId(),
                escolaId: $exam->escola_id,
            );

            return $exam;
        });
    }
}
