<?php

namespace App\Policies;

use App\Enums\PermissionCode;
use App\Models\Resultado;
use App\Models\User;
use App\Services\Authorization\AuthorizationContext;

class ResultadoPolicy extends BasePolicy
{
    public function view(User $user, Resultado $resultado): bool
    {
        $resultado->loadMissing('aplicacao.escola', 'aplicacao.aplicadores', 'aplicacao.turma.aplicadores');
        $aplicacao = $resultado->aplicacao;
        $linked = $aplicacao->aplicadores->contains('usuario_id', $user->id)
            || $aplicacao->turma->aplicadores->contains('usuario_id', $user->id);

        return $this->authorize(
            $user,
            PermissionCode::VIEW_RESULTS,
            AuthorizationContext::school($aplicacao->escola->nucleo_id, $aplicacao->escola_id),
        ) || $this->authorize(
            $user,
            PermissionCode::VIEW_RESULTS,
            AuthorizationContext::operational($linked, $aplicacao->escola->nucleo_id, $aplicacao->escola_id),
        );
    }
}
