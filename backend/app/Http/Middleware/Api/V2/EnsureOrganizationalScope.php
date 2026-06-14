<?php

namespace App\Http\Middleware\Api\V2;

use App\Models\User;
use App\Support\Authorization\PrimaryRoleResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationalScope
{
    /**
     * Resolve o escopo organizacional do usuário autenticado (global → núcleo
     * → escola) a partir do vínculo de perfil principal e o anexa à requisição
     * em `organizational_scope`, para uso por controllers/resources.
     *
     * Não bloqueia: a autorização efetiva continua por Policy/Scope em cada
     * recurso. Apenas disponibiliza o contexto resolvido.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User) {
            $link = PrimaryRoleResolver::resolve($user);

            $request->attributes->set('organizational_scope', [
                'scope' => $link?->perfil?->escopo_permitido?->value,
                'nucleo_id' => $link?->nucleo_id,
                'escola_id' => $link?->escola_id,
            ]);
        }

        return $next($request);
    }
}
