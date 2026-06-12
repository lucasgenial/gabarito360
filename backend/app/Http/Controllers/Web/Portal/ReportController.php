<?php

namespace App\Http\Controllers\Web\Portal;

use App\Enums\PermissionCode;
use App\Http\Controllers\Controller;
use App\Models\Prova;
use App\Models\Resultado;
use App\Models\Turma;
use App\Models\User;
use App\Services\Authorization\PortalScope;
use App\Services\Authorization\TurmaScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function result(Request $request, Resultado $resultado, PortalScope $scope): View
    {
        $user = $this->actor($request);
        abort_unless($scope->applyResults(Resultado::query()->whereKey($resultado), $user)->exists(), 404);
        $resultado->load([
            'aluno.escola',
            'prova',
            'aplicacao.turma',
            'questoes.questao.temasHabilidades',
        ]);

        return view('portal.reporting.result', compact('resultado'));
    }

    public function exam(Request $request, Prova $prova, PortalScope $scope): View
    {
        $user = $this->actor($request);
        abort_unless($scope->hasAnyPermission($user, PermissionCode::VIEW_REPORTS), 403);
        abort_unless($scope->applyExams(Prova::query()->whereKey($prova), $user)->exists(), 404);

        $results = $scope->applyResults(
            Resultado::query()->where('prova_id', $prova->id)->with(['aluno', 'aplicacao.turma']),
            $user,
        )->orderByDesc('nota_percentual')->get();
        $prova->load(['disciplina', 'serieAno']);
        $summary = $this->summary($results);

        return view('portal.reporting.report', [
            'title' => 'Relatorio da prova',
            'context' => $prova->titulo,
            'results' => $results,
            'summary' => $summary,
        ]);
    }

    public function classExam(
        Request $request,
        Turma $turma,
        Prova $prova,
        PortalScope $scope,
        TurmaScope $classes,
    ): View {
        $user = $this->actor($request);
        abort_unless($scope->hasAnyPermission($user, PermissionCode::VIEW_EXPORT_CLASS_REPORT), 403);
        abort_unless($classes->canView($user, $turma), 404);
        abort_unless($scope->applyExams(Prova::query()->whereKey($prova), $user)->exists(), 404);

        $results = $scope->applyResults(
            Resultado::query()
                ->where('prova_id', $prova->id)
                ->whereHas('aplicacao', fn (Builder $query) => $query->where('turma_id', $turma->id))
                ->with(['aluno', 'aplicacao.turma']),
            $user,
        )->orderByDesc('nota_percentual')->get();
        $summary = $this->summary($results);

        return view('portal.reporting.report', [
            'title' => 'Relatorio por turma',
            'context' => $turma->nome.' / '.$prova->titulo,
            'results' => $results,
            'summary' => $summary,
        ]);
    }

    /** @param Collection<int, Resultado> $results */
    private function summary($results): array
    {
        return [
            'count' => $results->count(),
            'average' => $results->isEmpty() ? 0 : round((float) $results->avg('nota_percentual'), 2),
            'highest' => $results->isEmpty() ? 0 : round((float) $results->max('nota_percentual'), 2),
            'lowest' => $results->isEmpty() ? 0 : round((float) $results->min('nota_percentual'), 2),
        ];
    }

    private function actor(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return $user;
    }
}
