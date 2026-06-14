<?php

namespace App\Http\Controllers\Api\V2\Escolas;

use App\Actions\Usuarios\CreateMembroAction;
use App\Actions\Usuarios\SuspendMembroAction;
use App\Actions\Usuarios\UpdateUsuarioAction;
use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\V2\Membros\ListMembrosRequest;
use App\Http\Requests\Api\V2\Membros\StoreMembroRequest;
use App\Http\Requests\Api\V2\Membros\UpdateMembroRequest;
use App\Http\Resources\Api\V2\MembroResource;
use App\Models\Escola;
use App\Models\User;
use App\Services\Authorization\UserAdministrationScope;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MembroController extends BaseApiController
{
    public function index(ListMembrosRequest $request, Escola $escola, UserAdministrationScope $scope): JsonResponse
    {
        abort_unless($scope->canAccessSchool($this->actor($request), $escola), 403);

        $vinculo = $this->vinculoConstraint($escola);

        $query = User::query()
            ->whereHas('perfilVinculos', $vinculo)
            ->with(['perfilVinculos' => fn ($links) => $vinculo($links)->with('perfil')]);

        if (is_string($q = $request->input('q')) && $q !== '') {
            $query->where(function ($where) use ($q): void {
                $where->where('nome', 'like', '%'.$q.'%')->orWhere('email', 'like', '%'.$q.'%');
            });
        }

        return $this->paginatedResponse(
            $query->orderBy('nome')->paginate(15),
            MembroResource::class,
        );
    }

    public function store(StoreMembroRequest $request, Escola $escola, CreateMembroAction $action): JsonResponse
    {
        $membro = $action->execute(
            $escola,
            $request->userAttributes(),
            $request->assignmentAttributes(),
            $request->extras(),
            $this->actor($request),
        );

        return $this->successResponse(MembroResource::make($this->loadVinculo($membro, $escola)), 201);
    }

    public function show(Request $request, Escola $escola, User $membro, UserAdministrationScope $scope): JsonResponse
    {
        abort_unless($scope->canAccessSchool($this->actor($request), $escola), 403);
        abort_unless($this->pertenceAEscola($membro, $escola), 404);

        return $this->successResponse(MembroResource::make($this->loadVinculo($membro, $escola)));
    }

    public function update(UpdateMembroRequest $request, Escola $escola, User $membro, UpdateUsuarioAction $action): JsonResponse
    {
        abort_unless($this->pertenceAEscola($membro, $escola), 404);

        $membro = $action->execute($membro, $request->mappedAttributes(), $this->actor($request));

        return $this->successResponse(MembroResource::make($this->loadVinculo($membro, $escola)));
    }

    public function suspend(Request $request, Escola $escola, User $membro, SuspendMembroAction $action, UserAdministrationScope $scope): JsonResponse
    {
        $actor = $this->actor($request);
        abort_unless($scope->canManage($actor, $membro), 403);
        abort_unless($this->pertenceAEscola($membro, $escola), 404);

        $membro = $action->execute($membro, $actor);

        return $this->successResponse(MembroResource::make($this->loadVinculo($membro, $escola)));
    }

    private function vinculoConstraint(Escola $escola): Closure
    {
        return fn ($query) => $query
            ->where('escola_id', $escola->id)
            ->where('inicio_at', '<=', now())
            ->whereNull('fim_at');
    }

    private function pertenceAEscola(User $membro, Escola $escola): bool
    {
        return ($this->vinculoConstraint($escola))($membro->perfilVinculos())->exists();
    }

    private function loadVinculo(User $membro, Escola $escola): User
    {
        $vinculo = $this->vinculoConstraint($escola);

        return $membro->load([
            'perfilVinculos' => fn ($links) => $vinculo($links)->with('perfil'),
        ]);
    }
}
