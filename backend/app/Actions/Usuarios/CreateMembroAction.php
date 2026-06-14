<?php

namespace App\Actions\Usuarios;

use App\Models\Escola;
use App\Models\User;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

class CreateMembroAction
{
    public function __construct(
        private AuditService $audit,
    ) {}

    /**
     * Cria um membro vinculado a uma escola: usuário + vínculo de perfil
     * escola-scoped + disciplinas (quando informadas).
     *
     * @param  array<string, mixed>  $userAttributes
     * @param  array{perfil_id: string, inicio_at: \DateTimeInterface|string|null}  $assignmentAttributes
     * @param  array{disciplinas?: list<string>, inicio_em?: string}  $extras
     */
    public function execute(
        Escola $escola,
        array $userAttributes,
        array $assignmentAttributes,
        array $extras,
        User $actor,
    ): User {
        return DB::transaction(function () use ($escola, $userAttributes, $assignmentAttributes, $extras, $actor): User {
            $user = User::query()->create($userAttributes)->refresh();

            $link = $user->perfilVinculos()->create([
                'perfil_id' => $assignmentAttributes['perfil_id'],
                'nucleo_id' => null,
                'escola_id' => $escola->id,
                'concedido_por' => $actor->id,
                'inicio_at' => $assignmentAttributes['inicio_at'] ?? now(),
            ]);

            $disciplinas = $extras['disciplinas'] ?? [];
            $inicioEm = $extras['inicio_em'] ?? now()->toDateString();

            foreach ($disciplinas as $disciplinaId) {
                $user->disciplinasVinculadas()->create([
                    'disciplina_id' => $disciplinaId,
                    'escola_id' => $escola->id,
                    'inicio_em' => $inicioEm,
                ]);
            }

            $this->audit->record(
                action: AuditAction::USER_CREATED,
                entityType: 'usuario',
                entityId: $user->id,
                actorUserId: $actor->id,
                after: $user->only(['nome', 'email', 'documento', 'telefone', 'status']),
                metadata: [
                    'vinculo_inicial_id' => $link->id,
                    'disciplinas' => $disciplinas,
                ],
                escolaId: $escola->id,
            );

            return $user;
        });
    }
}
