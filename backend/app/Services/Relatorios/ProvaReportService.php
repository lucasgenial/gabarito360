<?php

namespace App\Services\Relatorios;

use App\Models\Aplicacao;
use App\Models\LeituraCartao;
use App\Models\Prova;
use App\Models\Resultado;
use App\Models\User;
use App\Services\Authorization\PortalScope;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Consolida o relatório de uma prova (KPIs, acertos por tema, aproveitamento e
 * resultado por aluno) sobre resultados vigentes, respeitando o escopo do ator.
 *
 * Reutilizado pelo endpoint de dados e pelas exportações (csv/pdf/xlsx) para
 * garantir que tela e arquivo exibam exatamente os mesmos números.
 */
class ProvaReportService
{
    public function __construct(private PortalScope $scope) {}

    /**
     * IDs das aplicações da prova visíveis ao ator (opcionalmente por turma).
     *
     * @return Collection<int, string>
     */
    public function visibleApplicationIds(Prova $prova, User $actor, ?string $turmaId = null): Collection
    {
        $query = $this->scope->applyApplications(
            Aplicacao::query()->where('prova_id', $prova->id),
            $actor,
        );

        if (is_string($turmaId) && $turmaId !== '') {
            $query->where('turma_id', $turmaId);
        }

        return $query->pluck('id');
    }

    /**
     * Monta o relatório completo para as aplicações informadas.
     *
     * @param  Collection<int, string>  $applicationIds
     * @return array<string, mixed>
     */
    public function build(Prova $prova, Collection $applicationIds, ?string $turmaId = null): array
    {
        $meta = (float) config('gabarito360.resultados.meta_aprovacao', 60.0);

        return [
            'prova_id' => $prova->id,
            'turma_id' => is_string($turmaId) && $turmaId !== '' ? $turmaId : null,
            'meta_aprovacao' => $meta,
            'kpis' => $this->kpis($applicationIds, $meta),
            'acertos_por_tema' => $this->acertosPorTema($applicationIds),
            'resultado_por_aluno' => $this->resultadoPorAluno($applicationIds, $meta),
        ];
    }

    /**
     * @param  Collection<int, string>  $applicationIds
     * @return array<string, mixed>
     */
    private function kpis(Collection $applicationIds, float $meta): array
    {
        $agg = Resultado::query()
            ->whereIn('aplicacao_id', $applicationIds)
            ->where('status', 'vigente')
            ->selectRaw('
                COUNT(*) as total,
                AVG(nota_percentual) as media_nota,
                MIN(nota_percentual) as menor_nota,
                MAX(nota_percentual) as maior_nota,
                SUM(CASE WHEN nota_percentual >= ? THEN 1 ELSE 0 END) as aprovados
            ', [$meta])
            ->first();

        $totalAlunos = DB::table('aplicacao_alunos')->whereIn('aplicacao_id', $applicationIds)->count();
        $corrigidos = (int) ($agg->total ?? 0);
        $aprovados = (int) ($agg->aprovados ?? 0);
        $pendencias = LeituraCartao::query()
            ->whereIn('aplicacao_id', $applicationIds)
            ->where('requer_revisao', true)
            ->whereNull('confirmada_at')
            ->whereNull('cancelada_at')
            ->count();

        return [
            'total_alunos' => $totalAlunos,
            'cartoes_corrigidos' => $corrigidos,
            'pendencias_leitura' => $pendencias,
            'media_nota' => $agg && $agg->media_nota !== null ? round((float) $agg->media_nota, 2) : null,
            'menor_nota' => $agg && $agg->menor_nota !== null ? (float) $agg->menor_nota : null,
            'maior_nota' => $agg && $agg->maior_nota !== null ? (float) $agg->maior_nota : null,
            'aprovados' => $aprovados,
            'aprovacao_percentual' => $corrigidos > 0 ? round($aprovados / $corrigidos * 100, 2) : 0.0,
        ];
    }

    /**
     * Acertos por tema/habilidade (tema principal da questão) sobre as questões
     * dos resultados vigentes.
     *
     * @param  Collection<int, string>  $applicationIds
     * @return list<array{tema_id: string, tema_nome: string, total: int, acertos: int, percentual: float}>
     */
    private function acertosPorTema(Collection $applicationIds): array
    {
        if ($applicationIds->isEmpty()) {
            return [];
        }

        $rows = DB::table('resultado_questoes as rq')
            ->join('resultados as r', 'rq.resultado_id', '=', 'r.id')
            ->join('questao_temas as qt', function ($join): void {
                $join->on('qt.questao_id', '=', 'rq.questao_id')->where('qt.principal', '=', true);
            })
            ->join('temas_habilidades as t', 't.id', '=', 'qt.tema_habilidade_id')
            ->whereIn('r.aplicacao_id', $applicationIds)
            ->where('r.status', 'vigente')
            ->groupBy('t.id', 't.nome')
            ->selectRaw('
                t.id as tema_id,
                t.nome as tema_nome,
                COUNT(*) as total,
                SUM(CASE WHEN rq.situacao = ? THEN 1 ELSE 0 END) as acertos
            ', ['correta'])
            ->orderBy('t.nome')
            ->get();

        return $rows->map(fn ($row): array => [
            'tema_id' => $row->tema_id,
            'tema_nome' => $row->tema_nome,
            'total' => (int) $row->total,
            'acertos' => (int) $row->acertos,
            'percentual' => $row->total > 0 ? round((int) $row->acertos / (int) $row->total * 100, 2) : 0.0,
        ])->values()->all();
    }

    /**
     * @param  Collection<int, string>  $applicationIds
     * @return list<array{aluno_id: string, aluno_nome: ?string, matricula: ?string, turma_id: string, turma_nome: ?string, nota_percentual: float, acertos: int, status: string}>
     */
    private function resultadoPorAluno(Collection $applicationIds, float $meta): array
    {
        if ($applicationIds->isEmpty()) {
            return [];
        }

        return Resultado::query()
            ->whereIn('aplicacao_id', $applicationIds)
            ->where('status', 'vigente')
            ->with(['aluno:id,nome,nome_social,matricula', 'aplicacao:id,turma_id', 'aplicacao.turma:id,nome'])
            ->orderByDesc('nota_percentual')
            ->get()
            ->map(fn (Resultado $resultado): array => [
                'aluno_id' => $resultado->aluno_id,
                'aluno_nome' => $resultado->aluno?->nome_social ?: $resultado->aluno?->nome,
                'matricula' => $resultado->aluno?->matricula,
                'turma_id' => $resultado->aplicacao?->turma_id,
                'turma_nome' => $resultado->aplicacao?->turma?->nome,
                'nota_percentual' => (float) $resultado->nota_percentual,
                'acertos' => (int) $resultado->acertos,
                'status' => (float) $resultado->nota_percentual >= $meta ? 'aprovado' : 'recuperacao',
            ])
            ->values()
            ->all();
    }
}
