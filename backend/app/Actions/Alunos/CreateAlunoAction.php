<?php

namespace App\Actions\Alunos;

use App\Models\Aluno;
use App\Models\User;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

class CreateAlunoAction
{
    public function __construct(
        private AuditService $audit,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function execute(array $attributes, User $actor): Aluno
    {
        return DB::transaction(function () use ($attributes, $actor): Aluno {
            $student = Aluno::query()->create($attributes)->refresh();
            $student->load('escola');

            $this->audit->record(
                action: AuditAction::STUDENT_CREATED,
                entityType: 'aluno',
                entityId: $student->id,
                actorUserId: $actor->id,
                after: $student->only(['escola_id', 'codigo_interno', 'status']),
                metadata: ['campos_pessoais_omitidos' => true],
                nucleoId: $student->escola->nucleo_id,
                escolaId: $student->escola_id,
            );

            return $student;
        });
    }
}
