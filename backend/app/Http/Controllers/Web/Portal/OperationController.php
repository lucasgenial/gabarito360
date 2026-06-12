<?php

namespace App\Http\Controllers\Web\Portal;

use App\Actions\Aplicacoes\FinishAplicacaoAction;
use App\Actions\Aplicacoes\StartAplicacaoAction;
use App\Actions\Leituras\ConfirmReadingAction;
use App\Actions\Leituras\ReviewReadingAction;
use App\Actions\Relatorios\GenerateApplicationCsvReportAction;
use App\Enums\PermissionCode;
use App\Http\Controllers\Controller;
use App\Models\Aplicacao;
use App\Models\LeituraCartao;
use App\Models\User;
use App\Services\Authorization\PortalScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OperationController extends Controller
{
    public function index(Request $request, PortalScope $scope): View
    {
        $user = $this->actor($request);
        abort_unless($scope->canViewApplications($user), 403);
        $applications = $scope->applyApplications(
            Aplicacao::query()
                ->with(['prova', 'turma', 'escola'])
                ->withCount(['alunos', 'leituras', 'resultados']),
            $user,
        )->orderByDesc('inicio_previsto_at')->paginate(20);

        return view('portal.operations.index', compact('applications'));
    }

    public function show(Request $request, Aplicacao $aplicacao, PortalScope $scope): View
    {
        $user = $this->actor($request);
        abort_unless(
            $scope->applyApplications(Aplicacao::query()->whereKey($aplicacao), $user)->exists(),
            404,
        );
        $aplicacao->load([
            'prova',
            'turma',
            'escola',
            'aplicadores.usuario',
            'alunos.aluno',
            'alunos.resultadoVigente',
            'leituras.respostasDetectadas.questao',
        ])->loadCount(['alunos', 'leituras', 'resultados']);
        $canViewResults = $scope->hasAnyPermission($user, PermissionCode::VIEW_RESULTS);
        $canRun = $user->can('run', $aplicacao);
        $canConfirm = $user->can('confirmReading', $aplicacao);
        $canReview = $user->can('correctReading', $aplicacao);
        $canExport = $scope->hasAnyPermission($user, PermissionCode::VIEW_REPORTS) && $user->can('view', $aplicacao);

        return view('portal.operations.show', compact(
            'aplicacao',
            'canViewResults',
            'canRun',
            'canConfirm',
            'canReview',
            'canExport',
        ));
    }

    public function start(Request $request, Aplicacao $aplicacao, StartAplicacaoAction $action): RedirectResponse
    {
        Gate::authorize('run', $aplicacao);
        $action->execute($aplicacao, $this->actor($request));

        return back()->with('status', 'Aplicacao iniciada.');
    }

    public function finish(Request $request, Aplicacao $aplicacao, FinishAplicacaoAction $action): RedirectResponse
    {
        Gate::authorize('run', $aplicacao);
        $action->execute($aplicacao, $this->actor($request));

        return back()->with('status', 'Aplicacao finalizada.');
    }

    public function review(Request $request, LeituraCartao $leitura, ReviewReadingAction $action): RedirectResponse
    {
        Gate::authorize('correctReading', $leitura->aplicacao);
        $validated = $request->validate([
            'motivo' => ['required', 'string', 'min:5', 'max:500'],
            'respostas' => ['required', 'array', 'min:1'],
            'respostas.*' => ['nullable', 'string', 'max:10'],
        ]);
        $answers = collect($validated['respostas'])
            ->map(fn (?string $answer, string $questionId): array => [
                'questao_id' => $questionId,
                'alternativa_final' => $answer ?: null,
                'tipo_deteccao' => $answer ? 'marcada' : 'branco',
            ])->values()->all();
        $action->execute($leitura, ['motivo' => $validated['motivo'], 'respostas' => $answers], $this->actor($request));

        return back()->with('status', 'Leitura revisada e auditada.');
    }

    public function confirm(Request $request, LeituraCartao $leitura, ConfirmReadingAction $action): RedirectResponse
    {
        Gate::authorize('confirmReading', $leitura->aplicacao);
        $action->execute($leitura, (string) Str::uuid(), $this->actor($request));

        return back()->with('status', 'Leitura confirmada e resultado calculado.');
    }

    public function exportCsv(Request $request, Aplicacao $aplicacao, GenerateApplicationCsvReportAction $action): StreamedResponse
    {
        Gate::authorize('view', $aplicacao);
        abort_unless(app(PortalScope::class)->hasAnyPermission($this->actor($request), PermissionCode::VIEW_REPORTS), 403);
        $aplicacao->loadMissing('escola');
        $report = $action->execute($aplicacao, $this->actor($request));
        $file = $report->arquivo()->firstOrFail();

        return Storage::disk($file->disco)->download($file->caminho, $file->nome_original);
    }

    private function actor(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return $user;
    }
}
