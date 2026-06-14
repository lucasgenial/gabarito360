<?php

namespace App\Actions\Nucleos;

use App\Enums\StatusEnum;
use App\Models\Nucleo;
use App\Models\User;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

class ReactivateNucleoAction
{
    public function __construct(
        private AuditService $audit,
    ) {}

    public function execute(Nucleo $nucleo, User $actor): Nucleo
    {
        return DB::transaction(function () use ($nucleo, $actor): Nucleo {
            if ($nucleo->status === StatusEnum::ACTIVE) {
                return $nucleo;
            }

            $nucleo->update(['status' => StatusEnum::ACTIVE]);

            $this->audit->record(
                action: AuditAction::EDUCATION_CENTER_REACTIVATED,
                entityType: 'nucleo',
                entityId: $nucleo->id,
                actorUserId: $actor->id,
                before: ['status' => StatusEnum::INACTIVE],
                after: ['status' => StatusEnum::ACTIVE],
                nucleoId: $nucleo->id,
            );

            return $nucleo->refresh();
        });
    }
}
