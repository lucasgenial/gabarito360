<?php

namespace App\Observers;

use App\Models\DispositivoMobile;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;

class DispositivoMobileObserver
{
    public function __construct(
        private AuditService $audit,
    ) {}

    public function updated(DispositivoMobile $device): void
    {
        if (! $device->wasChanged('revogado_at') || $device->revogado_at === null) {
            return;
        }

        $this->audit->record(
            action: AuditAction::DEVICE_REVOKED,
            entityType: 'dispositivo_mobile',
            entityId: $device->id,
            metadata: ['usuario_id_alvo' => $device->usuario_id],
        );
    }
}
