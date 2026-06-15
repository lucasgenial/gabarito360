<?php

namespace App\Http\Controllers\Api\V2\Notificacoes;

use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Resources\Api\V2\NotificacaoResource;
use App\Models\Notificacao;
use App\Models\PreferenciaNotificacao;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificacaoController extends BaseApiController
{
    /**
     * Lista as notificações do ator (mais recentes primeiro). `?nao_lidas=1`
     * filtra apenas as não lidas; `meta.nao_lidas` traz a contagem do sino.
     *
     * GET /api/v2/notificacoes
     */
    public function index(Request $request): JsonResponse
    {
        $actor = $this->actor($request);

        $query = Notificacao::query()->where('usuario_id', $actor->id);
        if ($request->boolean('nao_lidas')) {
            $query->whereNull('lida_at');
        }

        $paginator = $query->orderByDesc('created_at')->paginate(20);
        $naoLidas = Notificacao::query()
            ->where('usuario_id', $actor->id)
            ->whereNull('lida_at')
            ->count();

        return $this->successResponse(
            NotificacaoResource::collection($paginator->getCollection()),
            200,
            [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'nao_lidas' => $naoLidas,
            ],
        );
    }

    /**
     * Marca uma notificação como lida (somente do próprio usuário).
     *
     * POST /api/v2/notificacoes/{notificacao}/ler
     */
    public function marcarLida(Request $request, Notificacao $notificacao): JsonResponse
    {
        abort_unless($notificacao->usuario_id === $this->actor($request)->id, 403);

        if ($notificacao->lida_at === null) {
            $notificacao->update(['lida_at' => now()]);
        }

        return $this->successResponse(new NotificacaoResource($notificacao->refresh()));
    }

    /**
     * Marca todas as notificações não lidas do ator como lidas.
     *
     * POST /api/v2/notificacoes/ler-todas
     */
    public function marcarTodas(Request $request): JsonResponse
    {
        $marcadas = Notificacao::query()
            ->where('usuario_id', $this->actor($request)->id)
            ->whereNull('lida_at')
            ->update(['lida_at' => now()]);

        return $this->successResponse(['marcadas' => $marcadas]);
    }

    /**
     * Preferências de notificação do ator (evento × canal).
     *
     * GET /api/v2/notificacoes/preferencias
     */
    public function preferencias(Request $request): JsonResponse
    {
        $prefs = PreferenciaNotificacao::query()
            ->where('usuario_id', $this->actor($request)->id)
            ->orderBy('evento')
            ->orderBy('canal')
            ->get()
            ->map(fn (PreferenciaNotificacao $pref): array => [
                'evento' => $pref->evento,
                'canal' => $pref->canal,
                'habilitada' => $pref->habilitada,
            ]);

        return $this->successResponse($prefs->values()->all());
    }

    /**
     * Cria/atualiza preferências de notificação do ator.
     *
     * PUT /api/v2/notificacoes/preferencias
     * Body: { "preferencias": [{ "evento": "...", "canal": "sistema|email|push", "habilitada": true }] }
     */
    public function atualizarPreferencias(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'preferencias' => ['required', 'array', 'min:1'],
            'preferencias.*.evento' => ['required', 'string', 'max:80'],
            'preferencias.*.canal' => ['required', 'string', 'in:sistema,email,push'],
            'preferencias.*.habilitada' => ['required', 'boolean'],
        ]);

        $actor = $this->actor($request);

        foreach ($validated['preferencias'] as $pref) {
            PreferenciaNotificacao::query()->updateOrCreate(
                ['usuario_id' => $actor->id, 'evento' => $pref['evento'], 'canal' => $pref['canal']],
                ['habilitada' => $pref['habilitada']],
            );
        }

        return $this->preferencias($request);
    }
}
