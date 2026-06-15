<?php

namespace App\Http\Controllers\Api\V2\Correcao;

use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Resources\Api\V2\PendenciaResource;
use App\Models\Aplicacao;
use App\Models\AplicacaoAluno;
use App\Models\LeituraCartao;
use App\Models\Prova;
use App\Models\RespostaDetectada;
use App\Services\Authorization\PortalScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CorrecaoController extends BaseApiController
{
    public function progresso(Request $request, Prova $prova, PortalScope $scope): JsonResponse
    {
        $turmaId = $request->query('turma');
        $scoped = $scope->applyApplications(
            Aplicacao::query()->where('prova_id', $prova->id),
            $this->actor($request),
        );

        if (is_string($turmaId) && $turmaId !== '') {
            $scoped->where('turma_id', $turmaId);
        }

        $applicationIds = $scoped->pluck('id');

        // 404 quando a prova nao tem aplicacoes visiveis ao usuario.
        abort_if($applicationIds->isEmpty(), 404);

        $leituraIds = LeituraCartao::query()->whereIn('aplicacao_id', $applicationIds)->pluck('id');
        $total = AplicacaoAluno::query()->whereIn('aplicacao_id', $applicationIds)->count();
        $lidos = LeituraCartao::query()->whereIn('id', $leituraIds)->whereNull('cancelada_at')->count();
        $pendentes = LeituraCartao::query()->whereIn('id', $leituraIds)->where('requer_revisao', true)->count();
        $ambiguos = RespostaDetectada::query()
            ->whereIn('leitura_cartao_id', $leituraIds)
            ->where('tipo_deteccao', 'ambigua')
            ->count();

        return $this->successResponse([
            'prova_id' => $prova->id,
            'turma_id' => is_string($turmaId) && $turmaId !== '' ? $turmaId : null,
            'total' => $total,
            'lidos' => $lidos,
            'pendentes' => $pendentes,
            'ambiguos' => $ambiguos,
            'percentual' => $total > 0 ? round(($lidos / $total) * 100, 2) : 0,
        ]);
    }

    public function pendencias(Request $request, Prova $prova, PortalScope $scope): JsonResponse
    {
        $applicationIds = $scope->applyApplications(
            Aplicacao::query()->where('prova_id', $prova->id),
            $this->actor($request),
        )->pluck('id');

        abort_if($applicationIds->isEmpty(), 404);

        $pendencias = LeituraCartao::query()
            ->whereIn('aplicacao_id', $applicationIds)
            ->where('requer_revisao', true)
            ->whereNull('confirmada_at')
            ->whereNull('cancelada_at')
            ->with(['aplicacaoAluno.aluno', 'aplicacao.turma', 'respostasDetectadas.questao'])
            ->orderBy('created_at')
            ->paginate(20);

        return $this->paginatedResponse($pendencias, PendenciaResource::class);
    }
}
