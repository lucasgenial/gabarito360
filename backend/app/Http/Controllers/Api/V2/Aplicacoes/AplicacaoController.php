<?php

namespace App\Http\Controllers\Api\V2\Aplicacoes;

use App\Actions\Aplicacoes\CreateAplicacaoAction;
use App\Actions\Aplicacoes\FinishAplicacaoAction;
use App\Actions\Aplicacoes\StartAplicacaoAction;
use App\Enums\GabaritoOficialStatus;
use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\V2\Aplicacoes\ListAplicacoesRequest;
use App\Http\Requests\Api\V2\Aplicacoes\StoreAplicacaoRequest;
use App\Http\Resources\Api\V2\AplicacaoResource;
use App\Models\Aplicacao;
use App\Models\GabaritoOficial;
use App\Models\Prova;
use App\Models\Turma;
use App\Services\Authorization\PortalScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class AplicacaoController extends BaseApiController
{
    public function index(ListAplicacoesRequest $request, PortalScope $scope): JsonResponse
    {
        $query = $scope->applyApplications(
            Aplicacao::query()->with(['prova', 'turma', 'escola']),
            $this->actor($request),
        );

        foreach (['prova' => 'prova_id', 'turma' => 'turma_id', 'escola' => 'escola_id'] as $param => $column) {
            if (is_string($value = $request->input($param)) && $value !== '') {
                $query->where($column, $value);
            }
        }

        if (is_string($status = $request->input('status')) && $status !== '') {
            $query->where('status', $status);
        }

        return $this->paginatedResponse(
            $query->orderByDesc('inicio_previsto_at')->paginate(15),
            AplicacaoResource::class,
        );
    }

    public function store(StoreAplicacaoRequest $request, CreateAplicacaoAction $action): JsonResponse
    {
        $exam = Prova::query()->findOrFail($request->input('prova_id'));
        $class = Turma::query()->findOrFail($request->input('turma_id'));
        $answerKey = $exam->gabaritosOficiais()
            ->where('status', GabaritoOficialStatus::CURRENT->value)
            ->latest('versao')
            ->first();

        if (! $answerKey instanceof GabaritoOficial) {
            throw ValidationException::withMessages([
                'gabarito' => ['A prova precisa de um gabarito vigente para ser aplicada.'],
            ]);
        }

        $application = $action->execute($exam, $class, $answerKey, [
            'titulo' => $request->input('titulo'),
            'inicio_previsto_at' => $request->input('inicio_previsto_at'),
            'fim_previsto_at' => $request->input('fim_previsto_at'),
        ], $this->actor($request));

        return $this->successResponse(AplicacaoResource::make($this->load($application))->withMetrics(), 201);
    }

    public function show(Request $request, Aplicacao $aplicacao): JsonResponse
    {
        Gate::authorize('view', $aplicacao);

        return $this->successResponse(AplicacaoResource::make($this->load($aplicacao))->withMetrics());
    }

    public function iniciar(Request $request, Aplicacao $aplicacao, StartAplicacaoAction $action): JsonResponse
    {
        Gate::authorize('run', $aplicacao);
        $action->execute($aplicacao, $this->actor($request));

        return $this->successResponse(AplicacaoResource::make($this->load($aplicacao->refresh()))->withMetrics());
    }

    public function finalizar(Request $request, Aplicacao $aplicacao, FinishAplicacaoAction $action): JsonResponse
    {
        Gate::authorize('run', $aplicacao);
        $action->execute($aplicacao, $this->actor($request));

        return $this->successResponse(AplicacaoResource::make($this->load($aplicacao->refresh()))->withMetrics());
    }

    private function load(Aplicacao $aplicacao): Aplicacao
    {
        return $aplicacao->load(['prova', 'turma', 'escola']);
    }
}
