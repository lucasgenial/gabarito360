<?php

namespace App\Http\Controllers\Api\V2\Provas;

use App\Actions\Provas\LinkProvaTurmaAction;
use App\Actions\Provas\UnlinkProvaTurmaAction;
use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\V2\ProvaTurmas\ListProvaTurmasRequest;
use App\Http\Requests\Api\V2\ProvaTurmas\StoreProvaTurmaRequest;
use App\Http\Resources\Api\V2\ProvaTurmaResource;
use App\Models\Prova;
use App\Models\Turma;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class ProvaTurmaController extends BaseApiController
{
    public function index(ListProvaTurmasRequest $request, Prova $prova): JsonResponse
    {
        $links = $prova->provaTurmas()->with('turma')->get();

        return $this->successResponse(ProvaTurmaResource::collection($links));
    }

    public function store(StoreProvaTurmaRequest $request, Prova $prova, LinkProvaTurmaAction $action): JsonResponse
    {
        $link = $action->execute(
            $prova,
            $request->turma(),
            ['data_prevista' => $request->input('data_prevista')],
            $this->actor($request),
        );

        return $this->successResponse(ProvaTurmaResource::make($link->load('turma')), 201);
    }

    public function destroy(Request $request, Prova $prova, Turma $turma, UnlinkProvaTurmaAction $action): Response
    {
        Gate::authorize('unlinkClass', [$prova, $turma]);

        $link = $prova->provaTurmas()->where('turma_id', $turma->id)->first();
        abort_if($link === null, 404);

        $action->execute($prova, $link, $this->actor($request));

        return response()->noContent();
    }
}
