<?php

namespace App\Http\Controllers\Api\V2\Me;

use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Resources\Api\V2\SessaoResource;
use App\Models\SessaoUsuario;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SessaoController extends BaseApiController
{
    public function __construct(
        private AuditService $audit,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $sessoes = $request->user()
            ->sessoes()
            ->ativas()
            ->with('token')
            ->orderByDesc('criado_em')
            ->get();

        return $this->successResponse(SessaoResource::collection($sessoes));
    }

    public function destroy(Request $request, SessaoUsuario $sessao): Response
    {
        abort_unless($sessao->usuario_id === $request->user()->id, 404);

        $sessao->token?->delete();
        $sessao->update(['encerrado_at' => now()]);

        $this->audit->record(
            action: AuditAction::SESSION_REVOKED,
            entityType: 'sessao_usuario',
            entityId: $sessao->id,
            actorUserId: $request->user()->id,
        );

        return response()->noContent();
    }
}
