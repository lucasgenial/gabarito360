<?php

namespace App\Actions\Escolas;

use App\Models\Escola;
use App\Models\User;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

class UpdateEscolaAction
{
    public function __construct(
        private AuditService $audit,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function execute(Escola $escola, array $attributes, User $actor): Escola
    {
        return DB::transaction(function () use ($escola, $attributes, $actor): Escola {
            $fields = ['nucleo_id', 'codigo', 'nome', 'municipio', 'estado', 'endereco', 'email', 'telefone', 'status'];
            $before = $escola->only($fields);

            $escola->update($attributes);

            if ($escola->wasChanged()) {
                $this->audit->record(
                    action: AuditAction::SCHOOL_UPDATED,
                    entityType: 'escola',
                    entityId: $escola->id,
                    actorUserId: $actor->id,
                    before: $before,
                    after: $escola->only($fields),
                    nucleoId: $escola->nucleo_id,
                    escolaId: $escola->id,
                );
            }

            return $escola->refresh();
        });
    }
}
