<?php

namespace App\Actions\Alunos;

use App\Models\Aluno;
use App\Models\User;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

class UpdateAlunoAction
{
    public function __construct(
        private AuditService $audit,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function execute(Aluno $student, array $attributes, User $actor): Aluno
    {
        return DB::transaction(function () use ($student, $attributes, $actor): Aluno {
            $student->update($attributes);

            if ($student->wasChanged()) {
                $student->loadMissing('escola');
                $this->audit->record(
                    action: AuditAction::STUDENT_UPDATED,
                    entityType: 'aluno',
                    entityId: $student->id,
                    actorUserId: $actor->id,
                    metadata: [
                        'campos_alterados' => collect($attributes)->keys()->values()->all(),
                        'valores_pessoais_omitidos' => true,
                    ],
                    nucleoId: $student->escola->nucleo_id,
                    escolaId: $student->escola_id,
                );
            }

            return $student->refresh();
        });
    }
}
