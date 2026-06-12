<?php

namespace App\Http\Controllers\Web\Portal;

use App\Enums\PermissionCode;
use App\Http\Controllers\Controller;
use App\Models\Aplicacao;
use App\Models\User;
use App\Services\Authorization\PortalScope;
use Illuminate\Http\Request;
use Illuminate\View\View;

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
            'leituras',
        ])->loadCount(['alunos', 'leituras', 'resultados']);
        $canViewResults = $scope->hasAnyPermission($user, PermissionCode::VIEW_RESULTS);

        return view('portal.operations.show', compact('aplicacao', 'canViewResults'));
    }

    private function actor(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return $user;
    }
}
