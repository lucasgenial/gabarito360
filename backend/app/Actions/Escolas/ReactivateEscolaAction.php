<?php

namespace App\Actions\Escolas;

use App\Enums\StatusEnum;
use App\Models\Escola;
use App\Models\User;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

class ReactivateEscolaAction
{
    public function __construct(
        private AuditService $audit,
    ) {}

    public function execute(Escola $escola, User $actor): Escola
    {
        return DB::transaction(function () use ($escola, $actor): Escola {
            if ($escola->status === StatusEnum::ACTIVE) {
                return $escola;
            }

            $escola->update(['status' => StatusEnum::ACTIVE]);

            $this->audit->record(
                action: AuditAction::SCHOOL_REACTIVATED,
                entityType: 'escola',
                entityId: $escola->id,
                actorUserId: $actor->id,
                before: ['status' => StatusEnum::INACTIVE],
                after: ['status' => StatusEnum::ACTIVE],
                nucleoId: $escola->nucleo_id,
                escolaId: $escola->id,
            );

            return $escola->refresh();
        });
    }
}
