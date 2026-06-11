<?php

namespace App\Actions\Turmas;

use App\Models\Turma;
use App\Models\User;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

class CreateTurmaAction
{
    public function __construct(
        private AuditService $audit,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function execute(array $attributes, User $actor): Turma
    {
        return DB::transaction(function () use ($attributes, $actor): Turma {
            $turma = Turma::query()->create($attributes)->refresh();
            $turma->load('escola');

            $this->audit->record(
                action: AuditAction::CLASS_CREATED,
                entityType: 'turma',
                entityId: $turma->id,
                actorUserId: $actor->id,
                after: $turma->only(['escola_id', 'codigo', 'nome', 'serie_ano', 'turno', 'ano_letivo', 'status']),
                nucleoId: $turma->escola->nucleo_id,
                escolaId: $turma->escola_id,
            );

            return $turma;
        });
    }
}
