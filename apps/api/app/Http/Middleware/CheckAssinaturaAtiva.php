<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use App\Models\Assinatura;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAssinaturaAtiva
{
    /**
     * Bloqueia acesso se a assinatura da rede do usuário estiver atrasada ou cancelada.
     * O titular ainda acessa /v1/assinaturas/minha para regularizar.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();
        if (!$usuario) {
            return $next($request);
        }

        // Admins sem rede vinculada (ex.: seeder de dev) passam livremente
        $escopo = $usuario->escopos()->whereIn('escopo_tipo', ['rede', 'escola'])->first();
        if (!$escopo) {
            return $next($request);
        }

        $redeId = $escopo->escopo_tipo === 'rede'
            ? $escopo->escopo_id
            : \App\Models\Escola::find($escopo->escopo_id)?->nucleo?->rede_id;

        if (!$redeId) {
            return $next($request);
        }

        $assinatura = Assinatura::where('rede_id', $redeId)
            ->whereIn('status', ['trial', 'ativa', 'atrasada', 'cancelada'])
            ->latest()
            ->first();

        if (!$assinatura || $assinatura->estaAtiva()) {
            return $next($request);
        }

        // Acesso bloqueado: titular pode acessar apenas /v1/assinaturas/minha
        $rotaPermitida = $request->is('api/v1/assinaturas/minha');
        if ($assinatura->titular_id === $usuario->id && $rotaPermitida) {
            return $next($request);
        }

        return ApiResponse::error(
            'Assinatura ' . $assinatura->status . '. Acesse "Minha Assinatura" para regularizar.',
            [],
            402
        );
    }
}
