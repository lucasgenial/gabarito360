<?php

namespace App\Actions\Provas;

use App\Models\Prova;
use App\Models\ProvaTurma;
use App\Models\Turma;
use App\Models\User;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use App\Services\Authorization\ProvaTurmaScope;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class LinkProvaTurmaAction
{
    public function __construct(
        private ProvaTurmaScope $scope,
        private AuditService $audit,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function execute(Prova $exam, Turma $class, array $attributes, User $actor): ProvaTurma
    {
        try {
            return DB::transaction(function () use ($exam, $class, $attributes, $actor): ProvaTurma {
                $lockedExam = Prova::query()->lockForUpdate()->findOrFail($exam->id);
                $lockedClass = Turma::query()->with('escola.nucleo')->lockForUpdate()->findOrFail($class->id);

                if (! $this->scope->canLink($actor, $lockedExam, $lockedClass)) {
                    throw ValidationException::withMessages([
                        'turma_id' => ['A prova e a turma nao estao disponiveis para este vinculo.'],
                    ]);
                }

                $link = ProvaTurma::query()->create([
                    'prova_id' => $lockedExam->id,
                    'turma_id' => $lockedClass->id,
                    'data_prevista' => $attributes['data_prevista'] ?? null,
                    'vinculado_por' => $actor->id,
                ])->load('turma.escola');

                $this->audit->record(
                    action: AuditAction::EXAM_CLASS_LINKED,
                    entityType: 'prova_turma',
                    entityId: $link->id,
                    actorUserId: $actor->id,
                    after: $link->only(['prova_id', 'turma_id', 'data_prevista']),
                    nucleoId: $lockedClass->escola->nucleo_id,
                    escolaId: $lockedClass->escola_id,
                );

                return $link;
            });
        } catch (UniqueConstraintViolationException) {
            throw new ConflictHttpException('A prova ja esta vinculada a turma informada.');
        }
    }
}
