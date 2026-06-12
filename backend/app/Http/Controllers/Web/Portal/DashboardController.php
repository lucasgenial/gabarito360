<?php

namespace App\Http\Controllers\Web\Portal;

use App\Http\Controllers\Controller;
use App\Models\Aluno;
use App\Models\Aplicacao;
use App\Models\Escola;
use App\Models\Prova;
use App\Models\Resultado;
use App\Models\Turma;
use App\Models\User;
use App\Services\Authorization\AlunoScope;
use App\Services\Authorization\PortalScope;
use App\Services\Authorization\TurmaScope;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        PortalScope $portal,
        TurmaScope $classes,
        AlunoScope $students,
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $applications = $portal->applyApplications(Aplicacao::query(), $user);
        $results = $portal->applyResults(Resultado::query(), $user);
        $metrics = [
            'escolas' => $portal->applySchools(Escola::query(), $user)->count(),
            'turmas' => $classes->apply(Turma::query(), $user)->count(),
            'alunos' => $students->apply(Aluno::query(), $user)->count(),
            'provas' => $portal->applyExams(Prova::query(), $user)->count(),
            'aplicacoes' => (clone $applications)->count(),
            'resultados' => (clone $results)->count(),
        ];
        $recentApplications = $applications
            ->with(['prova', 'turma', 'escola'])
            ->withCount(['alunos', 'leituras', 'resultados'])
            ->orderByDesc('inicio_previsto_at')
            ->limit(8)
            ->get();
        $statusSeries = $portal->applyApplications(Aplicacao::query(), $user)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->orderBy('status')
            ->get()
            ->map(fn (Aplicacao $application): array => [
                'label' => ucfirst(str_replace('_', ' ', $application->status)),
                'value' => (int) $application->total,
            ])
            ->all();

        return view('portal.dashboard', compact('metrics', 'recentApplications', 'statusSeries'));
    }
}
