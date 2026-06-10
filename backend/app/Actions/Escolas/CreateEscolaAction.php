<?php

namespace App\Actions\Escolas;

use App\Models\Escola;
use App\Models\User;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

class CreateEscolaAction
{
    public function __construct(
        private AuditService $audit,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function execute(array $attributes, User $actor): Escola
    {
        return DB::transaction(function () use ($attributes, $actor): Escola {
            $escola = Escola::query()->create($attributes)->refresh();

            $this->audit->record(
                action: AuditAction::SCHOOL_CREATED,
                entityType: 'escola',
                entityId: $escola->id,
                actorUserId: $actor->id,
                after: $escola->only(['nucleo_id', 'codigo', 'nome', 'municipio', 'estado', 'endereco', 'email', 'telefone', 'status']),
                nucleoId: $escola->nucleo_id,
                escolaId: $escola->id,
            );

            return $escola;
        });
    }
}
