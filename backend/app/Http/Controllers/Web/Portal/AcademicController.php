<?php

namespace App\Http\Controllers\Web\Portal;

use App\Actions\Alunos\CreateAlunoAction;
use App\Actions\Alunos\UpdateAlunoAction;
use App\Actions\StudentImports\CreateStudentImportAction;
use App\Actions\Turmas\CreateMatriculaTurmaAction;
use App\Actions\Turmas\CreateTurmaAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Alunos\StoreAlunoRequest;
use App\Http\Requests\Alunos\UpdateAlunoRequest;
use App\Http\Requests\StudentImports\StoreStudentImportRequest;
use App\Http\Requests\Turmas\StoreTurmaRequest;
use App\Models\Aluno;
use App\Models\Escola;
use App\Models\Resultado;
use App\Models\Turma;
use App\Models\User;
use App\Services\Authorization\AlunoScope;
use App\Services\Authorization\PortalScope;
use App\Services\Authorization\TurmaScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AcademicController extends Controller
{
    public function index(
        Request $request,
        TurmaScope $scope,
        PortalScope $portal,
    ): View {
        $user = $this->actor($request);
        Gate::authorize('viewAny', Turma::class);
        $classes = $scope->apply(
            Turma::query()->with('escola.nucleo')->withCount(['matriculas', 'aplicacoes']),
            $user,
        )->orderByDesc('ano_letivo')->orderBy('nome')->paginate(20);
        $schools = $portal->applySchools(Escola::query()->with('nucleo'), $user)
            ->get()
            ->filter(fn (Escola $school): bool => Gate::forUser($user)->allows('create', [Turma::class, $school]));

        return view('portal.academic.index', compact('classes', 'schools'));
    }

    public function storeClass(StoreTurmaRequest $request, CreateTurmaAction $action): RedirectResponse
    {
        $class = $action->execute($request->validated(), $this->actor($request));

        return redirect()->route('portal.classes.show', $class)->with('success', 'Turma criada com sucesso.');
    }

    public function showClass(Request $request, Turma $turma, TurmaScope $scope): View
    {
        $user = $this->actor($request);
        abort_unless($scope->canView($user, $turma), 404);
        $turma->load([
            'escola.nucleo',
            'matriculas' => fn ($query) => $query->with('aluno')->orderBy('numero_chamada'),
            'aplicadores.usuario',
            'provaTurmas.prova',
            'aplicacoes' => fn ($query) => $query->withCount(['alunos', 'leituras', 'resultados'])->latest('inicio_previsto_at'),
            'importacoesAlunos' => fn ($query) => $query->latest()->limit(5),
        ]);

        return view('portal.academic.show', compact('turma'));
    }

    public function createStudent(Request $request, Turma $turma, TurmaScope $scope): View
    {
        $user = $this->actor($request);
        abort_unless($scope->canManage($user, $turma), 403);
        $turma->load('escola');

        return view('portal.students.create', compact('turma'));
    }

    public function storeStudent(
        StoreAlunoRequest $request,
        Turma $turma,
        CreateAlunoAction $createStudent,
        CreateMatriculaTurmaAction $enroll,
    ): RedirectResponse {
        abort_unless($request->validated('escola_id') === $turma->escola_id, 422);
        $user = $this->actor($request);

        $student = DB::transaction(function () use ($createStudent, $enroll, $request, $turma, $user): Aluno {
            $student = $createStudent->execute($request->validated(), $user);
            $enroll->execute($turma, [
                'aluno_id' => $student->id,
                'numero_chamada' => null,
                'inicio_em' => today()->toDateString(),
            ], $user);

            return $student;
        });

        return redirect()->route('portal.students.show', $student)->with('success', 'Aluno cadastrado e matriculado.');
    }

    public function showStudent(
        Request $request,
        Aluno $aluno,
        AlunoScope $scope,
        PortalScope $portal,
    ): View {
        $user = $this->actor($request);
        abort_unless($scope->apply(Aluno::query()->whereKey($aluno), $user)->exists(), 404);
        $resultIds = $portal->applyResults(
            Resultado::query()->where('aluno_id', $aluno->id),
            $user,
        )->pluck('id');
        $aluno->load([
            'escola.nucleo',
            'matriculasTurmas.turma',
            'resultados' => fn ($query) => $query
                ->whereIn('id', $resultIds)
                ->with('prova')
                ->latest('calculado_at'),
        ]);

        return view('portal.students.show', compact('aluno'));
    }

    public function editStudent(Request $request, Aluno $aluno, AlunoScope $scope): View
    {
        $user = $this->actor($request);
        abort_unless($scope->canManage($user, $aluno->load('escola')), 403);

        return view('portal.students.edit', compact('aluno'));
    }

    public function updateStudent(
        UpdateAlunoRequest $request,
        UpdateAlunoAction $action,
    ): RedirectResponse {
        $student = $action->execute($request->student(), $request->validated(), $this->actor($request));

        return redirect()->route('portal.students.show', $student)->with('success', 'Aluno atualizado com sucesso.');
    }

    public function importStudents(
        StoreStudentImportRequest $request,
        CreateStudentImportAction $action,
    ): RedirectResponse {
        $import = $action->execute(
            $request->file('arquivo'),
            $request->school(),
            $request->class(),
            $this->actor($request),
        );

        return back()->with(
            $import->status->value === 'validada' ? 'success' : 'warning',
            $import->status->value === 'validada'
                ? 'Arquivo validado. A confirmacao permanece no fluxo controlado da API.'
                : 'Arquivo recebido com inconsistencias. Revise o resumo apresentado.',
        );
    }

    private function actor(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return $user;
    }
}
