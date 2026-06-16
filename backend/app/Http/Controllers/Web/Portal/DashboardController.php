<?php

namespace App\Http\Controllers\Web\Portal;

use App\Http\Controllers\Controller;
use App\Models\Aluno;
use App\Models\Aplicacao;
use App\Models\AtividadeRecente;
use App\Models\Escola;
use App\Models\LeituraCartao;
use App\Models\Resultado;
use App\Models\Turma;
use App\Models\User;
use App\Services\Authorization\PortalScope;
use App\Support\Web\PortalUiContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, PortalScope $scope, PortalUiContext $portalUiContext): View
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $portalUi = $portalUiContext->forUser($user);
        $applicationIds = $scope->applyApplications(Aplicacao::query(), $user)->pluck('id');

        $resultAgg = Resultado::query()
            ->whereIn('aplicacao_id', $applicationIds)
            ->where('status', 'vigente')
            ->selectRaw('COUNT(*) as total, AVG(nota_percentual) as media')
            ->first();

        $pendencias = $this->pendingReadings($applicationIds);

        $kpis = [
            'provas_aplicadas' => Aplicacao::query()
                ->whereIn('id', $applicationIds)
                ->whereIn('status', ['em_andamento', 'finalizada'])
                ->count(),
            'cartoes_corrigidos' => (int) ($resultAgg->total ?? 0),
            'media_geral' => $resultAgg->media !== null ? round((float) $resultAgg->media / 10, 1) : null,
            'pendencias' => $pendencias,
        ];

        $disciplinaSeries = DB::table('resultados')
            ->join('provas', 'resultados.prova_id', '=', 'provas.id')
            ->join('disciplinas', 'provas.disciplina_id', '=', 'disciplinas.id')
            ->whereIn('resultados.aplicacao_id', $applicationIds)
            ->where('resultados.status', 'vigente')
            ->groupBy('disciplinas.id', 'disciplinas.nome')
            ->selectRaw('disciplinas.nome as l, ROUND(AVG(resultados.nota_percentual)) as v')
            ->orderByDesc('v')
            ->limit(6)
            ->get()
            ->map(fn ($row): array => ['l' => Str::limit((string) $row->l, 8, ''), 'v' => (int) $row->v])
            ->all();

        $atividades = AtividadeRecente::query()
            ->when(! $scope->isGlobalViewer($user), function ($query) use ($scope, $user): void {
                $escolaIds = $scope->accessibleSchoolIds($user);
                $nucleoIds = $scope->accessibleNucleoIds($user);
                $query->where(function ($scoped) use ($escolaIds, $nucleoIds, $user): void {
                    $scoped->whereIn('escola_id', $escolaIds)
                        ->orWhereIn('nucleo_id', $nucleoIds)
                        ->orWhere('ator_id', $user->id);
                });
            })
            ->orderByDesc('created_at')
            ->limit(6)
            ->get();

        return view('portal.dashboard', [
            'greeting' => $this->greeting($user->nome),
            'scopeLabel' => $portalUi['scopeLabel'],
            'portalUi' => $portalUi,
            'dashboardVariant' => $portalUi['dashboardVariant'],
            'kpis' => $kpis,
            'disciplinaSeries' => $disciplinaSeries,
            'atividades' => $atividades,
            'adminDashboard' => $this->adminDashboard($scope, $user, $applicationIds, $resultAgg, $pendencias),
            'applicatorDashboard' => $this->applicatorDashboard($scope, $user, $applicationIds, $pendencias),
        ]);
    }

    private function greeting(string $nome): string
    {
        $hora = (int) now()->format('H');
        $saudacao = $hora < 12 ? 'Bom dia' : ($hora < 18 ? 'Boa tarde' : 'Boa noite');
        $primeiro = Str::of($nome)->explode(' ')->first() ?: '';

        return trim("{$saudacao}, {$primeiro}");
    }

    /** @param Collection<int, string> $applicationIds */
    private function pendingReadings(Collection $applicationIds): int
    {
        return LeituraCartao::query()
            ->whereIn('aplicacao_id', $applicationIds)
            ->where('requer_revisao', true)
            ->whereNull('confirmada_at')
            ->whereNull('cancelada_at')
            ->count();
    }

    /**
     * @param Collection<int, string> $applicationIds
     * @return array<string, mixed>
     */
    private function adminDashboard(
        PortalScope $scope,
        User $user,
        Collection $applicationIds,
        object $resultAgg,
        int $pendingReadings,
    ): array {
        $schoolIds = $scope->applySchools(Escola::query(), $user)->pluck('id');
        $classIds = $scope->visibleClassIds($user);
        $students = Aluno::query()->whereIn('escola_id', $schoolIds)->count();
        $applications = Aplicacao::query()->whereIn('id', $applicationIds)->count();
        $inProgress = Aplicacao::query()
            ->whereIn('id', $applicationIds)
            ->where('status', 'em_andamento')
            ->count();
        $average = $resultAgg->media !== null ? round((float) $resultAgg->media / 10, 1) : null;

        return [
            'kpis' => [
                'schools' => $schoolIds->count(),
                'classes' => $classIds->count(),
                'students' => $students,
                'applications' => $applications,
                'users' => User::query()->count(),
                'average' => $average,
                'pending_readings' => $pendingReadings,
            ],
            'alerts' => [
                [
                    'title' => $pendingReadings > 0
                        ? "{$pendingReadings} leitura(s) aguardando revisão"
                        : 'Leituras sem pendências abertas',
                    'meta' => 'Fila OMR',
                    'tone' => $pendingReadings > 0 ? 'warn' : 'success',
                ],
                [
                    'title' => $inProgress > 0
                        ? "{$inProgress} aplicação(ões) em andamento"
                        : 'Nenhuma aplicação em andamento',
                    'meta' => 'Operação da rede',
                    'tone' => $inProgress > 0 ? 'info' : 'muted',
                ],
                [
                    'title' => $average !== null && $average < 7
                        ? 'Média geral abaixo do ponto de atenção'
                        : 'Desempenho consolidado dentro do esperado',
                    'meta' => $average !== null ? 'Média '.$average.'/10' : 'Sem resultados consolidados',
                    'tone' => $average !== null && $average < 7 ? 'warn' : 'success',
                ],
            ],
            'recent_users' => User::query()
                ->with(['perfilVinculos' => fn ($links) => $links
                    ->whereNull('fim_at')
                    ->with('perfil')])
                ->orderByDesc('ultimo_acesso_at')
                ->orderBy('nome')
                ->limit(5)
                ->get(),
        ];
    }

    /**
     * @param Collection<int, string> $applicationIds
     * @return array<string, mixed>
     */
    private function applicatorDashboard(
        PortalScope $scope,
        User $user,
        Collection $applicationIds,
        int $pendingReadings,
    ): array {
        $classIds = $scope->visibleClassIds($user);
        $confirmedReadings = LeituraCartao::query()
            ->whereIn('aplicacao_id', $applicationIds)
            ->whereNotNull('confirmada_at')
            ->whereNull('cancelada_at')
            ->count();
        $ongoing = Aplicacao::query()
            ->whereIn('id', $applicationIds)
            ->where('status', 'em_andamento')
            ->count();

        return [
            'kpis' => [
                'linked_classes' => $classIds->count(),
                'applications' => $applicationIds->count(),
                'confirmed_readings' => $confirmedReadings,
                'pending_readings' => $pendingReadings,
                'ongoing' => $ongoing,
            ],
            'applications' => $scope->applyApplications(
                Aplicacao::query()
                    ->with(['prova.disciplina', 'turma.escola', 'escola'])
                    ->withCount(['alunos', 'leituras', 'resultados']),
                $user,
            )
                ->orderByDesc('inicio_previsto_at')
                ->limit(6)
                ->get(),
            'classes' => Turma::query()
                ->with('escola')
                ->withCount('matriculas')
                ->whereIn('id', $classIds)
                ->orderBy('nome')
                ->limit(5)
                ->get(),
        ];
    }
}
