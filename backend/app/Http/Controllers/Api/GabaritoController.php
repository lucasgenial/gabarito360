<?php

namespace App\Http\Controllers\Api;

use App\Actions\Gabaritos\CreateGabaritoOficialAction;
use App\Actions\Gabaritos\UpsertGabaritoRespostaAction;
use App\Http\Requests\Gabaritos\ListGabaritoRespostasRequest;
use App\Http\Requests\Gabaritos\ListGabaritosRequest;
use App\Http\Requests\Gabaritos\StoreGabaritoRequest;
use App\Http\Requests\Gabaritos\UpsertGabaritoRespostaRequest;
use App\Http\Requests\Gabaritos\ViewGabaritoRequest;
use App\Http\Resources\GabaritoOficialResource;
use App\Http\Resources\GabaritoRespostaResource;
use App\Models\User;
use App\Services\Gabaritos\GabaritoCompletenessValidator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GabaritoController extends BaseApiController
{
    public function index(ListGabaritosRequest $request): JsonResponse
    {
        $paginator = $request->prova()
            ->gabaritosOficiais()
            ->orderByDesc('versao')
            ->paginate($request->validated('per_page', 20));

        return $this->paginatedResponse($request, $paginator, GabaritoOficialResource::class);
    }

    public function store(StoreGabaritoRequest $request, CreateGabaritoOficialAction $action): JsonResponse
    {
        $answerKey = $action->execute($request->prova(), $this->actor($request->user()));

        return $this->successResponse(GabaritoOficialResource::make($answerKey)->resolve($request), 201);
    }

    public function show(ViewGabaritoRequest $request): JsonResponse
    {
        return $this->successResponse(GabaritoOficialResource::make($request->gabarito())->resolve($request));
    }

    public function responses(ListGabaritoRespostasRequest $request): JsonResponse
    {
        $paginator = $request->gabarito()
            ->respostas()
            ->with('questao')
            ->join('questoes', 'questoes.id', '=', 'gabarito_respostas.questao_id')
            ->select('gabarito_respostas.*')
            ->orderBy('questoes.numero')
            ->paginate($request->validated('per_page', 100));

        return $this->paginatedResponse($request, $paginator, GabaritoRespostaResource::class);
    }

    public function upsertResponse(
        UpsertGabaritoRespostaRequest $request,
        UpsertGabaritoRespostaAction $action,
    ): JsonResponse {
        $response = $action->execute(
            $request->prova(),
            $request->gabarito(),
            $request->questao(),
            $request->validated(),
            $this->actor($request->user()),
        )->load('questao');

        return $this->successResponse(GabaritoRespostaResource::make($response)->resolve($request));
    }

    public function validation(
        ViewGabaritoRequest $request,
        GabaritoCompletenessValidator $validator,
    ): JsonResponse {
        return $this->successResponse($validator->validate($request->gabarito()));
    }

    private function actor(mixed $actor): User
    {
        abort_unless($actor instanceof User, 401);

        return $actor;
    }

    /** @param class-string<GabaritoOficialResource|GabaritoRespostaResource> $resource */
    private function paginatedResponse(Request $request, LengthAwarePaginator $paginator, string $resource): JsonResponse
    {
        return $this->successResponse([
            'items' => $resource::collection($paginator->getCollection())->resolve($request),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }
}
