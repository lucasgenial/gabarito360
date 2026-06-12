<?php

namespace App\Http\Controllers\Web\Portal;

use App\Http\Controllers\Controller;
use App\Models\Escola;
use App\Models\User;
use App\Services\Authorization\PortalScope;
use App\Services\Authorization\UserAdministrationScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrganizationController extends Controller
{
    public function index(Request $request, PortalScope $scope): View
    {
        $user = $this->actor($request);
        $schools = $scope->applySchools(
            Escola::query()
                ->with('nucleo')
                ->withCount(['turmas', 'alunos', 'aplicacoes']),
            $user,
        )->orderBy('nome')->paginate(20);

        abort_if($schools->isEmpty() && ! $scope->canViewApplications($user), 403);

        return view('portal.organization.index', compact('schools'));
    }

    public function show(Request $request, Escola $escola, PortalScope $scope): View
    {
        $user = $this->actor($request);
        abort_unless($scope->canViewSchool($user, $escola), 404);

        $escola->load([
            'nucleo',
            'turmas' => fn ($query) => $query->withCount('matriculas')->orderBy('nome'),
            'aplicacoes' => fn ($query) => $query->with(['prova', 'turma'])->latest('inicio_previsto_at')->limit(8),
        ])->loadCount(['alunos', 'provas', 'lotacoes']);

        return view('portal.organization.show', compact('escola'));
    }

    public function team(
        Request $request,
        Escola $escola,
        UserAdministrationScope $scope,
    ): View {
        $user = $this->actor($request);
        abort_unless($scope->canAccessSchool($user, $escola), 403);

        $members = $scope->apply(
            User::query()->whereHas('perfilVinculos', fn (Builder $links) => $links
                ->where('escola_id', $escola->id)
                ->whereNull('fim_at')),
            $user,
        )
            ->with(['perfilVinculos' => fn ($links) => $links
                ->where('escola_id', $escola->id)
                ->whereNull('fim_at')
                ->with('perfil')])
            ->orderBy('nome')
            ->paginate(20);

        return view('portal.organization.team', compact('escola', 'members'));
    }

    public function createMember(
        Request $request,
        Escola $escola,
        UserAdministrationScope $scope,
    ): RedirectResponse {
        abort_unless($scope->canAccessSchool($this->actor($request), $escola), 403);

        return redirect()->route('admin.usuarios.index', ['escola_id' => $escola->id]);
    }

    public function editMember(
        Request $request,
        Escola $escola,
        User $usuario,
        UserAdministrationScope $scope,
    ): RedirectResponse {
        $actor = $this->actor($request);
        abort_unless($scope->canAccessSchool($actor, $escola) && $scope->canView($actor, $usuario), 403);

        return redirect()->route('admin.usuarios.edit', $usuario);
    }

    private function actor(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return $user;
    }
}
