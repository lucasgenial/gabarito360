<?php

namespace App\Actions\Nucleos;

use App\Models\Nucleo;
use App\Models\User;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

class UpdateNucleoAction
{
    public function __construct(
        private AuditService $audit,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function execute(Nucleo $nucleo, array $attributes, User $actor): Nucleo
    {
        return DB::transaction(function () use ($nucleo, $attributes, $actor): Nucleo {
            $before = $nucleo->only(['codigo', 'nome', 'municipio', 'estado', 'email', 'telefone', 'status']);

            $nucleo->update($attributes);

            if ($nucleo->wasChanged()) {
                $this->audit->record(
                    action: AuditAction::EDUCATION_CENTER_UPDATED,
                    entityType: 'nucleo',
                    entityId: $nucleo->id,
                    actorUserId: $actor->id,
                    before: $before,
                    after: $nucleo->only(['codigo', 'nome', 'municipio', 'estado', 'email', 'telefone', 'status']),
                    nucleoId: $nucleo->id,
                );
            }

            return $nucleo->refresh();
        });
    }
}
