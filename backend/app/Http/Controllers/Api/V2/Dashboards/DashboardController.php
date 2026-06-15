<?php

namespace App\Http\Controllers\Api\V2\Dashboards;

use App\Actions\Relatorios\GenerateIndicatorSnapshotAction;
use App\Http\Controllers\Api\V2\BaseApiController;
use App\Models\Aplicacao;
use App\Models\Prova;
use App\Models\Resultado;
use App\Services\Authorization\PortalScope;
use App\Services\Relatorios\ProvaReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends BaseApiController
{
    /**
     * Dashboard de uma aplicação: progresso de leituras + indicadores de resultados.
     *
     * GET /api/v2/dashboards/aplicacao/{aplicacao}
     */
    public function aplicacao(Request $request, Aplicacao $aplicacao, PortalScope $scope): JsonResponse
    {
        $actor = $this->actor($request);

        abort_unless(
            $scope->applyApplications(Aplicacao::query()->whereKey($aplicacao), $actor)->exists(),
            403,
        );

        $totalAlunos = $aplicacao->alunos()->count();
        $totalLidos = $aplicacao->leituras()->whereNull('cancelada_at')->count();
        $totalConfirmados = $aplicacao->alunos()->where('status', 'confirmado')->count();
        $totalPendentes = $aplicacao->leituras()
            ->where('requer_revisao', true)
            ->whereNull('confirmada_at')
            ->whereNull('cancelada_at')
            ->count();

        $resultados = Resultado::query()
            ->where('aplicacao_id', $aplicacao->id)
            ->where('status', 'vigente')
            ->selectRaw('
                COUNT(*) as total,
                AVG(nota_percentual) as media_nota,
                MIN(nota_percentual) as menor_nota,
                MAX(nota_percentual) as maior_nota,
                SUM(acertos) as total_acertos,
                SUM(erros) as total_erros,
                SUM(brancos) as total_brancos,
                SUM(duplas) as total_duplas,
                SUM(anuladas) as total_anuladas
            ')
            ->first();

        $distribuicao = $this->distribuicaoFaixas($aplicacao->id);

        return $this->successResponse([
            'aplicacao_id' => $aplicacao->id,
            'status' => $aplicacao->status,
            'progresso' => [
                'total_alunos' => $totalAlunos,
                'total_lidos' => $totalLidos,
                'total_confirmados' => $totalConfirmados,
                'total_pendentes_revisao' => $totalPendentes,
                'percentual_lido' => $totalAlunos > 0 ? round($totalLidos / $totalAlunos * 100, 2) : 0,
                'percentual_confirmado' => $totalAlunos > 0 ? round($totalConfirmados / $totalAlunos * 100, 2) : 0,
            ],
            'resultados' => $resultados ? [
                'total' => (int) $resultados->total,
                'media_nota' => $resultados->media_nota !== null ? round((float) $resultados->media_nota, 2) : null,
                'menor_nota' => $resultados->menor_nota !== null ? (float) $resultados->menor_nota : null,
                'maior_nota' => $resultados->maior_nota !== null ? (float) $resultados->maior_nota : null,
                'total_acertos' => (int) $resultados->total_acertos,
                'total_erros' => (int) $resultados->total_erros,
                'total_brancos' => (int) $resultados->total_brancos,
                'total_duplas' => (int) $resultados->total_duplas,
                'total_anuladas' => (int) $resultados->total_anuladas,
            ] : null,
            'distribuicao_notas' => $distribuicao,
        ]);
    }

    /**
     * Dashboard consolidado de uma prova com indicadores por turma.
     *
     * GET /api/v2/dashboards/prova/{prova}
     */
    public function prova(Request $request, Prova $prova, PortalScope $scope): JsonResponse
    {
        $actor = $this->actor($request);

        // Verifica que o ator tem visibilidade sobre ao menos uma aplicação da prova.
        $applicationIds = $scope->applyApplications(
            Aplicacao::query()->where('prova_id', $prova->id),
            $actor,
        )->pluck('id');

        abort_if($applicationIds->isEmpty(), 404);

        $porTurma = DB::table('resultados')
            ->join('aplicacoes', 'resultados.aplicacao_id', '=', 'aplicacoes.id')
            ->join('turmas', 'aplicacoes.turma_id', '=', 'turmas.id')
            ->whereIn('resultados.aplicacao_id', $applicationIds)
            ->where('resultados.status', 'vigente')
            ->groupBy('aplicacoes.turma_id', 'turmas.nome')
            ->selectRaw('
                aplicacoes.turma_id,
                turmas.nome as turma_nome,
                COUNT(resultados.id) as total,
                AVG(resultados.nota_percentual) as media_nota,
                MIN(resultados.nota_percentual) as menor_nota,
                MAX(resultados.nota_percentual) as maior_nota
            ')
            ->get();

        $geral = Resultado::query()
            ->whereIn('aplicacao_id', $applicationIds)
            ->where('status', 'vigente')
            ->selectRaw('
                COUNT(*) as total,
                AVG(nota_percentual) as media_nota,
                MIN(nota_percentual) as menor_nota,
                MAX(nota_percentual) as maior_nota
            ')
            ->first();

        return $this->successResponse([
            'prova_id' => $prova->id,
            'geral' => $geral ? [
                'total' => (int) $geral->total,
                'media_nota' => $geral->media_nota !== null ? round((float) $geral->media_nota, 2) : null,
                'menor_nota' => $geral->menor_nota !== null ? (float) $geral->menor_nota : null,
                'maior_nota' => $geral->maior_nota !== null ? (float) $geral->maior_nota : null,
            ] : null,
            'por_turma' => $porTurma->map(fn ($row): array => [
                'turma_id' => $row->turma_id,
                'turma_nome' => $row->turma_nome,
                'total' => (int) $row->total,
                'media_nota' => $row->media_nota !== null ? round((float) $row->media_nota, 2) : null,
                'menor_nota' => $row->menor_nota !== null ? (float) $row->menor_nota : null,
                'maior_nota' => $row->maior_nota !== null ? (float) $row->maior_nota : null,
            ])->values()->all(),
        ]);
    }

    /**
     * Gera e persiste uma fotografia (snapshot) dos indicadores da prova,
     * retornando o snapshot recém-criado.
     *
     * GET /api/v2/dashboards/prova/{prova}/snapshot
     */
    public function snapshot(
        Request $request,
        Prova $prova,
        ProvaReportService $reports,
        GenerateIndicatorSnapshotAction $action,
    ): JsonResponse {
        $actor = $this->actor($request);
        $applicationIds = $reports->visibleApplicationIds($prova, $actor);

        abort_if($applicationIds->isEmpty(), 404);

        $snapshot = $action->execute($prova, $applicationIds, $actor);

        return $this->successResponse([
            'id' => $snapshot->id,
            'escopo_tipo' => $snapshot->escopo_tipo,
            'prova_id' => $snapshot->prova_id,
            'total_resultados' => $snapshot->total_resultados,
            'media_nota' => $snapshot->media_nota !== null ? (float) $snapshot->media_nota : null,
            'indicadores' => $snapshot->indicadores,
            'gerado_at' => $snapshot->gerado_at?->toIso8601String(),
        ], 201);
    }

    /**
     * Distribui notas em faixas de 10% para histograma.
     *
     * @return list<array{faixa: string, total: int}>
     */
    private function distribuicaoFaixas(string $aplicacaoId): array
    {
        $faixas = [];
        for ($i = 0; $i < 10; $i++) {
            $min = $i * 10;
            $max = $min + 10;
            $label = $i === 9 ? '90-100' : "{$min}-{$max}";
            $faixas[] = ['faixa' => $label, 'min' => $min, 'max' => $max === 100 ? 100.0001 : (float) $max];
        }

        $counts = DB::table('resultados')
            ->where('aplicacao_id', $aplicacaoId)
            ->where('status', 'vigente')
            ->selectRaw('nota_percentual')
            ->pluck('nota_percentual');

        return array_map(function (array $faixa) use ($counts): array {
            $total = $counts->filter(fn ($nota) => (float) $nota >= $faixa['min'] && (float) $nota < $faixa['max'])->count();

            return ['faixa' => $faixa['faixa'], 'total' => $total];
        }, $faixas);
    }
}
