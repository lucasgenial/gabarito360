<?php

namespace App\Http\Controllers\Api;

use App\Actions\Usuarios\AssignUsuarioPerfilAction;
use App\Actions\Usuarios\CreateUsuarioAction;
use App\Actions\Usuarios\InactivateUsuarioAction;
use App\Actions\Usuarios\RevokeUsuarioPerfilAction;
use App\Actions\Usuarios\UpdateUsuarioAction;
use App\Http\Requests\Usuarios\AssignUsuarioPerfilRequest;
use App\Http\Requests\Usuarios\ListUsuariosRequest;
use App\Http\Requests\Usuarios\StoreUsuarioRequest;
use App\Http\Requests\Usuarios\UpdateUsuarioRequest;
use App\Http\Resources\UsuarioResource;
use App\Models\User;
use App\Models\UsuarioPerfil;
use App\Services\Authorization\UserAdministrationScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UsuarioController extends BaseApiController
{
    public function index(ListUsuariosRequest $request, UserAdministrationScope $scope): JsonResponse
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
                    ->where('nome', 'ilike', '%'.$filters['search'].'%')
                    ->orWhere('email', 'ilike', '%'.$filters['search'].'%');
            });
        }

        $paginator = $query->paginate($filters['per_page'] ?? 20);
        $items = $paginator->getCollection()
            ->map(fn (User $user): User => $this->loadScopedProfiles($user, $actor, $scope));

        return $this->successResponse([
            'items' => UsuarioResource::collection($items)->resolve($request),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function store(
        StoreUsuarioRequest $request,
        CreateUsuarioAction $action,
        UserAdministrationScope $scope,
    ): JsonResponse {
        Gate::authorize('create', User::class);

        $actor = $this->actor($request->user());
        $target = $action->execute($request->userAttributes(), $request->assignmentAttributes(), $actor);

        return $this->successResponse(
            UsuarioResource::make($this->loadScopedProfiles($target, $actor, $scope))->resolve($request),
            201,
        );
    }

    public function show(Request $request, User $usuario, UserAdministrationScope $scope): JsonResponse
    {
        Gate::authorize('view', $usuario);

        return $this->successResponse(
            UsuarioResource::make(
                $this->loadScopedProfiles($usuario, $this->actor($request->user()), $scope),
            )->resolve($request),
        );
    }

    public function update(
        UpdateUsuarioRequest $request,
        User $usuario,
        UpdateUsuarioAction $action,
        UserAdministrationScope $scope,
    ): JsonResponse {
        Gate::authorize('update', $usuario);

        $actor = $this->actor($request->user());
        $target = $action->execute($usuario, $request->validated(), $actor);

        return $this->successResponse(
            UsuarioResource::make($this->loadScopedProfiles($target, $actor, $scope))->resolve($request),
        );
    }

    public function assignProfile(
        AssignUsuarioPerfilRequest $request,
        User $usuario,
        AssignUsuarioPerfilAction $action,
        UserAdministrationScope $scope,
    ): JsonResponse {
        Gate::authorize('assignProfile', $usuario);

        $actor = $this->actor($request->user());
        $action->execute($usuario, $request->assignmentAttributes(), $actor);

        return $this->successResponse(
            UsuarioResource::make($this->loadScopedProfiles($usuario, $actor, $scope))->resolve($request),
            201,
        );
    }

    public function revokeProfile(
        Request $request,
        User $usuario,
        UsuarioPerfil $vinculo,
        RevokeUsuarioPerfilAction $action,
        UserAdministrationScope $scope,
    ): JsonResponse {
        abort_unless($vinculo->usuario_id === $usuario->id, 404);
        Gate::authorize('revokeProfile', [$usuario, $vinculo]);

        $action->execute($vinculo);
        $actor = $this->actor($request->user());

        return $this->successResponse(
            UsuarioResource::make($this->loadScopedProfiles($usuario, $actor, $scope))->resolve($request),
        );
    }

    public function inactivate(
        Request $request,
        User $usuario,
        InactivateUsuarioAction $action,
        UserAdministrationScope $scope,
    ): JsonResponse {
        Gate::authorize('delete', $usuario);

        $actor = $this->actor($request->user());
        $target = $action->execute($usuario, $actor);

        return $this->successResponse(
            UsuarioResource::make($this->loadScopedProfiles($target, $actor, $scope))->resolve($request),
        );
    }

    private function loadScopedProfiles(User $target, User $actor, UserAdministrationScope $scope): User
    {
        return $target->load([
            'perfilVinculos' => fn (Builder $links): Builder => $scope
                ->applyLinks($links, $actor)
                ->with('perfil')
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
