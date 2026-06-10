<?php

namespace App\Actions\Nucleos;

use App\Enums\StatusEnum;
use App\Models\Nucleo;
use App\Models\User;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

class InactivateNucleoAction
{
    public function __construct(
        private AuditService $audit,
    ) {}

    public function execute(Nucleo $nucleo, User $actor): Nucleo
    {
        return DB::transaction(function () use ($nucleo, $actor): Nucleo {
            if ($nucleo->status === StatusEnum::INACTIVE) {
                return $nucleo;
            }

            $nucleo->update(['status' => StatusEnum::INACTIVE]);

            $this->audit->record(
                action: AuditAction::EDUCATION_CENTER_INACTIVATED,
                entityType: 'nucleo',
                entityId: $nucleo->id,
                actorUserId: $actor->id,
                before: ['status' => StatusEnum::ACTIVE],
                after: ['status' => StatusEnum::INACTIVE],
                nucleoId: $nucleo->id,
            );

            return $nucleo->refresh();
        });
    }
}
