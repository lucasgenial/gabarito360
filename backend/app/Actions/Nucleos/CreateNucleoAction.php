<?php

namespace App\Actions\Nucleos;

use App\Models\Nucleo;
use App\Models\User;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

class CreateNucleoAction
{
    public function __construct(
        private AuditService $audit,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function execute(array $attributes, User $actor): Nucleo
    {
        return DB::transaction(function () use ($attributes, $actor): Nucleo {
            $nucleo = Nucleo::query()->create($attributes)->refresh();

            $this->audit->record(
                action: AuditAction::EDUCATION_CENTER_CREATED,
                entityType: 'nucleo',
                entityId: $nucleo->id,
                actorUserId: $actor->id,
                after: $nucleo->only(['codigo', 'nome', 'municipio', 'estado', 'email', 'telefone', 'status']),
                nucleoId: $nucleo->id,
            );

            return $nucleo;
        });
    }
}
