<?php

namespace App\Observers;

use App\Models\UsuarioPerfil;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;

class UsuarioPerfilObserver
{
    public function __construct(
        private AuditService $audit,
    ) {}

    public function created(UsuarioPerfil $link): void
    {
        $this->audit->record(
            action: AuditAction::PROFILE_GRANTED,
            entityType: 'usuario_perfil',
            entityId: $link->id,
            actorUserId: $link->concedido_por,
            after: $this->allowedAttributes($link),
            nucleoId: $link->nucleo_id,
            escolaId: $link->escola_id,
        );
    }

    public function updated(UsuarioPerfil $link): void
    {
        $action = $link->wasChanged('fim_at') && $link->fim_at !== null
            ? AuditAction::PROFILE_REVOKED
            : AuditAction::PROFILE_CHANGED;

        $this->audit->record(
            action: $action,
            entityType: 'usuario_perfil',
            entityId: $link->id,
            before: $this->allowedOriginalAttributes($link),
            after: $this->allowedAttributes($link),
            nucleoId: $link->nucleo_id,
            escolaId: $link->escola_id,
        );
    }

    public function deleted(UsuarioPerfil $link): void
    {
        $this->audit->record(
            action: AuditAction::PROFILE_REMOVED,
            entityType: 'usuario_perfil',
            entityId: $link->id,
            before: $this->allowedAttributes($link),
            nucleoId: $link->nucleo_id,
            escolaId: $link->escola_id,
        );
    }

    /** @return array<string, mixed> */
    private function allowedAttributes(UsuarioPerfil $link): array
    {
        return $link->only([
            'usuario_id',
            'perfil_id',
            'nucleo_id',
            'escola_id',
            'inicio_at',
            'fim_at',
        ]);
    }

    /** @return array<string, mixed> */
    private function allowedOriginalAttributes(UsuarioPerfil $link): array
    {
        return collect($link->getOriginal())
            ->only([
                'usuario_id',
                'perfil_id',
                'nucleo_id',
                'escola_id',
                'inicio_at',
                'fim_at',
            ])
            ->all();
    }
}
