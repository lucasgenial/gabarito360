<?php

namespace App\Actions\Alunos;

use App\Enums\StatusEnum;
use App\Models\Aluno;
use App\Models\User;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

class InactivateAlunoAction
{
    public function __construct(
        private AuditService $audit,
    ) {}

    public function execute(Aluno $student, User $actor): Aluno
    {
        return DB::transaction(function () use ($student, $actor): Aluno {
            if ($student->status === StatusEnum::INACTIVE) {
                return $student;
            }

            $student->update(['status' => StatusEnum::INACTIVE]);
            $student->loadMissing('escola');

            $this->audit->record(
                action: AuditAction::STUDENT_INACTIVATED,
                entityType: 'aluno',
                entityId: $student->id,
                actorUserId: $actor->id,
                before: ['status' => StatusEnum::ACTIVE],
                after: ['status' => StatusEnum::INACTIVE],
                nucleoId: $student->escola->nucleo_id,
                escolaId: $student->escola_id,
            );

            return $student->refresh();
        });
    }
}
