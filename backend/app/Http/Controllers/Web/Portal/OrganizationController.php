<?php

namespace App\Http\Controllers\Web\Portal;

use App\Actions\Escolas\CreateEscolaAction;
use App\Actions\Escolas\ReactivateEscolaAction;
use App\Actions\Escolas\UpdateEscolaAction;
use App\Enums\StatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V2\Escolas\StoreEscolaRequest;
use App\Http\Requests\Api\V2\Escolas\UpdateEscolaRequest;
use App\Models\Aluno;
use App\Models\Escola;
use App\Models\Nucleo;
use App\Models\Turma;
use App\Models\User;
use App\Services\Authorization\PortalScope;
use App\Services\Authorization\UserAdministrationScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class OrganizationController extends Controller
{
    public function index(Request $request, PortalScope $scope): View
    {
        $user = $this->actor($request);
        $search = trim((string) $request->string('q')->toString());
        $scopedSchools = $scope->applySchools(Escola::query(), $user);
        $schoolIds = (clone $scopedSchools)->pluck('id');
        $hasScopedSchools = $schoolIds->isNotEmpty();
        $schoolList = $scope->applySchools(
            Escola::query()
                ->with('nucleo')
                ->withCount([
                    'alunos',
                    'provas',
                    'turmas',
                    'turmas as turmas_ativas_count' => fn (Builder $query): Builder => $query
                        ->where('status', StatusEnum::ACTIVE->value),
                ]),
            $user,
        );

        if ($search !== '') {
            $schoolList->where(function (Builder $query) use ($search): void {
                $query
                    ->where('nome', 'like', '%'.$search.'%')
                    ->orWhere('codigo', 'like', '%'.$search.'%')
                    ->orWhere('inep', 'like', '%'.$search.'%');
            });
        }

        $schools = $schoolList
            ->orderBy('nome')
            ->paginate(12)
            ->withQueryString();

        abort_if(! $hasScopedSchools && ! $scope->canViewApplications($user), 403);

        $manageableNuclei = Nucleo::query()
            ->where('status', StatusEnum::ACTIVE->value)
            ->orderBy('nome')
            ->get()
            ->filter(fn (Nucleo $nucleo): bool => Gate::forUser($user)->allows('create', [Escola::class, $nucleo]))
            ->values();

        return view('portal.organization.index', [
            'schools' => $schools,
            'scopeLabel' => $this->scopeLabel($scope, $user),
            'search' => $search,
            'manageableNuclei' => $manageableNuclei,
            'canCreateSchool' => $manageableNuclei->isNotEmpty(),
            'kpis' => [
                'total' => $schoolIds->count(),
                'active' => Escola::query()
                    ->whereIn('id', $schoolIds)
                    ->where('status', StatusEnum::ACTIVE->value)
                    ->count(),
                'students' => Aluno::query()->whereIn('escola_id', $schoolIds)->count(),
                'active_classes' => Turma::query()
                    ->whereIn('escola_id', $schoolIds)
                    ->where('status', StatusEnum::ACTIVE->value)
                    ->count(),
            ],
        ]);
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

    public function store(
        StoreEscolaRequest $request,
        CreateEscolaAction $action,
    ): RedirectResponse {
        $action->execute($request->mappedAttributes(), $this->actor($request));

        return redirect()
            ->route('portal.schools.index')
            ->with('school_success', 'Escola criada com sucesso.');
    }

    public function update(
        UpdateEscolaRequest $request,
        Escola $escola,
        UpdateEscolaAction $action,
    ): RedirectResponse {
        $action->execute($escola, $request->mappedAttributes(), $this->actor($request));

        return redirect()
            ->route('portal.schools.index')
            ->with('school_success', 'Escola atualizada com sucesso.');
    }

    public function reactivate(
        Request $request,
        Escola $escola,
        ReactivateEscolaAction $action,
    ): RedirectResponse {
        Gate::authorize('update', $escola);

        $action->execute($escola, $this->actor($request));

        return redirect()
            ->route('portal.schools.index')
            ->with('school_success', 'Escola reativada com sucesso.');
    }

    private function actor(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return $user;
    }

    private function scopeLabel(PortalScope $scope, User $user): string
    {
        if ($scope->isGlobalViewer($user)) {
            return 'Rede';
        }

        $nucleoId = $scope->accessibleNucleoIds($user)->first();

        return $nucleoId !== null
            ? (string) (Nucleo::query()->whereKey($nucleoId)->value('nome') ?? 'Meu nucleo')
            : 'Minhas escolas';
    }
}
