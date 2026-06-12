<?php

namespace App\Http\Controllers\Web\Admin;

use App\Actions\Usuarios\AssignUsuarioPerfilAction;
use App\Actions\Usuarios\CreateUsuarioAction;
use App\Actions\Usuarios\InactivateUsuarioAction;
use App\Actions\Usuarios\RevokeUsuarioPerfilAction;
use App\Actions\Usuarios\UpdateUsuarioAction;
use App\Enums\StatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Usuarios\AssignUsuarioPerfilRequest;
use App\Http\Requests\Usuarios\ListUsuariosRequest;
use App\Http\Requests\Usuarios\StoreUsuarioRequest;
use App\Http\Requests\Usuarios\UpdateUsuarioRequest;
use App\Models\Escola;
use App\Models\Nucleo;
use App\Models\Perfil;
use App\Models\User;
use App\Models\UsuarioPerfil;
use App\Services\Authorization\UserAdministrationScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    public function index(ListUsuariosRequest $request, UserAdministrationScope $scope): View
    {
        Gate::authorize('viewAny', User::class);

        $actor = $this->actor($request->user());
        $filters = $request->validated();
        $query = $scope->apply(User::query(), $actor)
            ->orderBy('nome')
            ->orderBy('id');

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['nucleo_id'])) {
            $this->filterByLink($query, $scope, $actor, 'nucleo_id', $filters['nucleo_id']);
        }

        if (isset($filters['escola_id'])) {
            $this->filterByLink($query, $scope, $actor, 'escola_id', $filters['escola_id']);
        }

        if (isset($filters['search'])) {
            $query->where(function (Builder $search) use ($filters): void {
                $search
                    ->where('nome', 'like', '%'.$filters['search'].'%')
                    ->orWhere('email', 'like', '%'.$filters['search'].'%');
            });
        }

        $usuarios = $query->paginate($filters['per_page'] ?? 20)->withQueryString();
        $usuarios->getCollection()->transform(
            fn (User $user): User => $this->loadScopedProfiles($user, $actor, $scope),
        );
        $options = $this->assignmentOptions($actor, $scope);

        return view('admin.usuarios.index', compact('usuarios', 'filters', 'options'));
    }

    public function store(
        StoreUsuarioRequest $request,
        CreateUsuarioAction $action,
        UserAdministrationScope $scope,
    ): RedirectResponse {
        Gate::authorize('create', User::class);
        $action->execute(
            $request->userAttributes(),
            $request->assignmentAttributes(),
            $this->actor($request->user()),
        );

        return back()->with('success', 'Usuario criado com sucesso.');
    }

    public function edit(Request $request, User $usuario, UserAdministrationScope $scope): View
    {
        Gate::authorize('view', $usuario);

        $actor = $this->actor($request->user());
        $usuario = $this->loadScopedProfiles($usuario, $actor, $scope);
        $options = $this->assignmentOptions($actor, $scope);

        return view('admin.usuarios.edit', compact('usuario', 'options'));
    }

    public function update(
        UpdateUsuarioRequest $request,
        User $usuario,
        UpdateUsuarioAction $action,
    ): RedirectResponse {
        Gate::authorize('update', $usuario);
        $action->execute($usuario, $request->validated(), $this->actor($request->user()));

        return back()->with('success', 'Usuario atualizado com sucesso.');
    }

    public function assignProfile(
        AssignUsuarioPerfilRequest $request,
        User $usuario,
        AssignUsuarioPerfilAction $action,
    ): RedirectResponse {
        Gate::authorize('assignProfile', $usuario);
        $action->execute($usuario, $request->assignmentAttributes(), $this->actor($request->user()));

        return back()->with('success', 'Perfil concedido com sucesso.');
    }

    public function revokeProfile(
        Request $request,
        User $usuario,
        UsuarioPerfil $vinculo,
        RevokeUsuarioPerfilAction $action,
    ): RedirectResponse {
        abort_unless($vinculo->usuario_id === $usuario->id, 404);
        Gate::authorize('revokeProfile', [$usuario, $vinculo]);
        $action->execute($vinculo);

        return back()->with('success', 'Perfil revogado com sucesso.');
    }

    public function inactivate(
        Request $request,
        User $usuario,
        InactivateUsuarioAction $action,
    ): RedirectResponse {
        Gate::authorize('delete', $usuario);
        $action->execute($usuario, $this->actor($request->user()));

        return redirect()->route('admin.usuarios.index')->with('success', 'Usuario inativado com sucesso.');
    }

    /**
     * @return array{
     *     perfis: Collection<int, Perfil>,
     *     nucleos: Collection<int, Nucleo>,
     *     escolas: Collection<int, Escola>
     * }
     */
    private function assignmentOptions(User $actor, UserAdministrationScope $scope): array
    {
        $nucleos = Nucleo::query()
            ->where('status', StatusEnum::ACTIVE->value)
            ->orderBy('nome')
            ->get()
            ->filter(fn (Nucleo $nucleo): bool => $scope->hasGlobalAccess($actor)
                || $scope->canAccessEducationCenter($actor, $nucleo->id))
            ->values();

        $escolas = Escola::query()
            ->with('nucleo')
            ->where('status', StatusEnum::ACTIVE->value)
            ->whereHas('nucleo', fn (Builder $query): Builder => $query->where('status', StatusEnum::ACTIVE->value))
            ->orderBy('nome')
            ->get()
            ->filter(fn (Escola $escola): bool => $scope->hasGlobalAccess($actor)
                || $scope->canAccessSchool($actor, $escola))
            ->values();

        $perfis = Perfil::query()
            ->where('status', StatusEnum::ACTIVE->value)
            ->orderBy('nome')
            ->get()
            ->filter(fn (Perfil $perfil): bool => $scope->canAssign($actor, $perfil, null, null)
                || $nucleos->contains(fn (Nucleo $nucleo): bool => $scope->canAssign($actor, $perfil, $nucleo, null))
                || $escolas->contains(fn (Escola $escola): bool => $scope->canAssign($actor, $perfil, null, $escola)))
            ->values();

        return compact('perfis', 'nucleos', 'escolas');
    }

    private function loadScopedProfiles(User $target, User $actor, UserAdministrationScope $scope): User
    {
        return $target->load([
            'perfilVinculos' => fn (HasMany $links): Builder => $scope
                ->applyLinks($links->getQuery(), $actor)
                ->with(['perfil', 'nucleo', 'escola'])
                ->orderBy('inicio_at'),
        ]);
    }

    /** @param Builder<User> $query */
    private function filterByLink(
        Builder $query,
        UserAdministrationScope $scope,
        User $actor,
        string $field,
        string $value,
    ): void {
        $query->whereHas(
            'perfilVinculos',
            fn (Builder $links): Builder => $scope->applyLinks($links, $actor)->where($field, $value),
        );
    }

    private function actor(mixed $actor): User
    {
        abort_unless($actor instanceof User, 401);

        return $actor;
    }
}
