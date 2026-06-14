<?php

namespace App\Http\Controllers\Api\V2\Integracoes;

use App\Actions\Integracoes\ConnectIntegracaoAction;
use App\Actions\Integracoes\DisconnectIntegracaoAction;
use App\Actions\Integracoes\TestIntegracaoAction;
use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\V2\Integracoes\ConnectIntegracaoRequest;
use App\Http\Resources\Api\V2\IntegracaoResource;
use App\Models\Integracao;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class IntegracaoController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Integracao::class);

        return $this->paginatedResponse(
            Integracao::query()->orderBy('chave')->paginate(15),
            IntegracaoResource::class,
        );
    }

    public function store(ConnectIntegracaoRequest $request, ConnectIntegracaoAction $action): JsonResponse
    {
        $integracao = $action->execute(
            [
                'escopo' => 'global',
                'nucleo_id' => null,
                'escola_id' => null,
                'chave' => $request->input('chave'),
                'nome' => $request->input('nome'),
                'descricao' => $request->input('descricao'),
            ],
            $request->credenciais(),
            $this->actor($request),
        );

        return $this->successResponse(IntegracaoResource::make($integracao), 201);
    }

    public function destroy(Request $request, Integracao $integracao, DisconnectIntegracaoAction $action): Response
    {
        Gate::authorize('delete', $integracao);

        $action->execute($integracao, $this->actor($request));

        return response()->noContent();
    }

    public function testar(Request $request, Integracao $integracao, TestIntegracaoAction $action): JsonResponse
    {
        Gate::authorize('test', $integracao);

        return $this->successResponse($action->execute($integracao, $this->actor($request)));
    }
}
