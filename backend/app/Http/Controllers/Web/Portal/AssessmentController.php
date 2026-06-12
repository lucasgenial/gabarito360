<?php

namespace App\Http\Controllers\Web\Portal;

use App\Actions\Provas\CreateProvaAction;
use App\Enums\ModeloCartaoStatus;
use App\Enums\PermissionCode;
use App\Enums\StatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Provas\StoreProvaRequest;
use App\Models\Escola;
use App\Models\ModeloCartao;
use App\Models\Nucleo;
use App\Models\Prova;
use App\Models\User;
use App\Services\Authorization\ModeloCartaoScope;
use App\Services\Authorization\PortalScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AssessmentController extends Controller
{
    public function index(Request $request, PortalScope $scope): View
    {
        $user = $this->actor($request);
        $exams = $scope->applyExams(
            Prova::query()
                ->with(['nucleo', 'escola', 'disciplina', 'serieAno', 'modeloCartao'])
                ->withCount(['questoes', 'provaTurmas', 'aplicacoes']),
            $user,
        )->orderByDesc('created_at')->paginate(20);

        abort_if($exams->isEmpty() && ! $scope->hasAnyPermission($user, PermissionCode::MANAGE_EXAMS_ANSWER_KEYS), 403);

        return view('portal.assessments.index', compact('exams'));
    }

    public function show(Request $request, Prova $prova, PortalScope $scope): View
    {
        $user = $this->actor($request);
        abort_unless($scope->applyExams(Prova::query()->whereKey($prova), $user)->exists(), 404);
        $prova->load([
            'nucleo',
            'escola',
            'disciplina',
            'serieAno',
            'modeloCartao',
            'questoes.temasHabilidades',
            'gabaritosOficiais',
            'provaTurmas.turma.escola',
        ])->loadCount('aplicacoes');
        $canViewAnswerKey = $scope->hasAnyPermission($user, PermissionCode::MANAGE_EXAMS_ANSWER_KEYS);
        $canViewReports = $scope->hasAnyPermission($user, PermissionCode::VIEW_REPORTS);

        return view('portal.assessments.show', compact('prova', 'canViewAnswerKey', 'canViewReports'));
    }

    public function create(Request $request, ModeloCartaoScope $modelScope): View
    {
        $user = $this->actor($request);
        Gate::authorize('viewAny', Prova::class);
        $nuclei = Nucleo::query()
            ->where('status', StatusEnum::ACTIVE->value)
            ->orderBy('nome')
            ->get()
            ->filter(fn (Nucleo $nucleus): bool => Gate::forUser($user)->allows('createForNucleo', [Prova::class, $nucleus]));
        $schools = Escola::query()
            ->with('nucleo')
            ->where('status', StatusEnum::ACTIVE->value)
            ->orderBy('nome')
            ->get()
            ->filter(fn (Escola $school): bool => Gate::forUser($user)->allows('createForSchool', [Prova::class, $school]));
        $models = $modelScope->apply(
            ModeloCartao::query()->where('status', ModeloCartaoStatus::APPROVED->value),
            $user,
        )->orderBy('nome')->get();

        return view('portal.assessments.create', compact('nuclei', 'schools', 'models'));
    }

    public function store(StoreProvaRequest $request, CreateProvaAction $action): RedirectResponse
    {
        $exam = $action->execute($request->validated(), $this->actor($request));

        return redirect()->route('portal.exams.show', $exam)->with('success', 'Prova criada em rascunho.');
    }

    public function answerKey(Request $request, Prova $prova, PortalScope $scope): View
    {
        $user = $this->actor($request);
        abort_unless($scope->hasAnyPermission($user, PermissionCode::MANAGE_EXAMS_ANSWER_KEYS), 403);
        abort_unless($scope->applyExams(Prova::query()->whereKey($prova), $user)->exists(), 404);
        $prova->load([
            'gabaritosOficiais' => fn ($query) => $query
                ->with(['respostas.questao'])
                ->orderByDesc('versao'),
        ]);

        return view('portal.assessments.answer-key', compact('prova'));
    }

    private function actor(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return $user;
    }
}
