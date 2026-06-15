<?php

namespace App\Http\Controllers\Api\V2\Lgpd;

use App\Actions\Lgpd\ProcessLgpdRequestAction;
use App\Enums\PermissionCode;
use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Resources\Api\V2\SolicitacaoLgpdResource;
use App\Models\SolicitacaoLgpd;
use App\Models\User;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use App\Services\Authorization\PortalScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SolicitacaoLgpdController extends BaseApiController
{
    public function __construct(private PortalScope $scope) {}

    /**
     * Lista solicitações: o solicitante vê as suas; admin de configurações vê todas.
     *
     * GET /api/v2/solicitacoes-lgpd
     */
    public function index(Request $request): JsonResponse
    {
        $actor = $this->actor($request);
        $query = SolicitacaoLgpd::query();

        if (! $this->isLgpdAdmin($actor)) {
            $query->where('solicitante_id', $actor->id);
        }

        if (($status = $request->query('status')) !== null) {
            $query->where('status', $status);
        }
        if (($tipo = $request->query('tipo')) !== null) {
            $query->where('tipo', $tipo);
        }

        $solicitacoes = $query->orderByDesc('created_at')->paginate(20);

        return $this->paginatedResponse($solicitacoes, SolicitacaoLgpdResource::class);
    }

    /**
     * Cria uma solicitação LGPD. O titular é a própria conta, salvo quando
     * `aluno_id` é informado (exige permissão de gestão de alunos). A "zona de
     * perigo" da tela de configurações usa este endpoint — nunca exclusão direta.
     *
     * POST /api/v2/solicitacoes-lgpd
     */
    public function store(Request $request, AuditService $audit): JsonResponse
    {
        $validated = $request->validate([
            'tipo' => ['required', 'string', 'in:acesso,correcao,portabilidade,anonimizacao,exclusao'],
            'descricao' => ['required', 'string', 'max:2000'],
            'aluno_id' => ['nullable', 'uuid', 'exists:alunos,id'],
        ]);

        $actor = $this->actor($request);

        if (isset($validated['aluno_id'])) {
            abort_unless($this->scope->hasAnyPermission($actor, PermissionCode::MANAGE_CLASSES_STUDENTS), 403);
            $titularTipo = 'aluno';
            $titularId = $validated['aluno_id'];
        } else {
            $titularTipo = 'usuario';
            $titularId = $actor->id;
        }

        $prazoDias = (int) config('gabarito360.lgpd.prazo_resposta_dias', 15);

        $solicitacao = SolicitacaoLgpd::query()->create([
            'tipo' => $validated['tipo'],
            'titular_tipo' => $titularTipo,
            'titular_referencia_hash' => hash('sha256', $titularTipo.':'.$titularId),
            'titular_id' => $titularId,
            'solicitante_id' => $actor->id,
            'status' => 'aberta',
            'descricao' => $validated['descricao'],
            'prazo_at' => now()->addDays($prazoDias)->toDateString(),
        ]);

        $audit->record(
            AuditAction::LGPD_REQUEST_CREATED,
            'solicitacao_lgpd',
            $solicitacao->id,
            $actor->id,
            after: $solicitacao->only(['tipo', 'titular_tipo', 'status']),
        );

        return $this->successResponse(new SolicitacaoLgpdResource($solicitacao), 201);
    }

    /**
     * Detalhe (solicitante dono ou admin de configurações).
     *
     * GET /api/v2/solicitacoes-lgpd/{solicitacao}
     */
    public function show(Request $request, SolicitacaoLgpd $solicitacao): JsonResponse
    {
        $actor = $this->actor($request);
        abort_unless(
            $solicitacao->solicitante_id === $actor->id || $this->isLgpdAdmin($actor),
            403,
        );

        return $this->successResponse(new SolicitacaoLgpdResource($solicitacao->load('execucoes')));
    }

    /**
     * Processa a solicitação (admin de configurações). Anonimiza/inativa o titular
     * para pedidos de exclusão/anonimização e registra a trilha de descarte.
     *
     * POST /api/v2/solicitacoes-lgpd/{solicitacao}/processar
     */
    public function processar(Request $request, SolicitacaoLgpd $solicitacao, ProcessLgpdRequestAction $action): JsonResponse
    {
        $actor = $this->actor($request);
        abort_unless($this->isLgpdAdmin($actor), 403);

        $validated = $request->validate([
            'decisao' => ['required', 'string', 'max:2000'],
        ]);

        abort_if($solicitacao->status === 'concluida', 422);

        $processada = $action->execute($solicitacao, $actor, $validated['decisao']);

        return $this->successResponse(new SolicitacaoLgpdResource($processada->load('execucoes')));
    }

    private function isLgpdAdmin(User $user): bool
    {
        return $this->scope->hasAnyPermission($user, PermissionCode::MANAGE_SETTINGS);
    }
}
