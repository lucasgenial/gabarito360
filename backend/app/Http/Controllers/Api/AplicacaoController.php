<?php

namespace App\Http\Controllers\Api;

use App\Actions\Aplicacoes\CreateAplicacaoAction;
use App\Actions\Aplicacoes\FinishAplicacaoAction;
use App\Actions\Aplicacoes\StartAplicacaoAction;
use App\Http\Requests\Aplicacoes\RunAplicacaoRequest;
use App\Http\Requests\Aplicacoes\StoreAplicacaoRequest;
use App\Http\Resources\AplicacaoResource;
use App\Models\Aplicacao;
use App\Models\GabaritoOficial;
use App\Models\Prova;
use App\Models\Turma;
use App\Models\User;
use App\Services\Authorization\PortalScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AplicacaoController extends BaseApiController
{
    public function index(Request $request, PortalScope $scope): JsonResponse
    {
        $paginator = $scope->applyApplications(Aplicacao::query(), $this->actor($request))
            ->withCount(['alunos', 'leituras', 'resultados'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return $this->successResponse([
            'items' => AplicacaoResource::collection($paginator->getCollection())->resolve($request),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function store(StoreAplicacaoRequest $request, CreateAplicacaoAction $action): JsonResponse
    {
        $data = $request->validated();
        $application = $action->execute(
            Prova::query()->findOrFail($data['prova_id']),
            Turma::query()->findOrFail($data['turma_id']),
            GabaritoOficial::query()->findOrFail($data['gabarito_oficial_id']),
            $data,
            $this->actor($request),
        );

        return $this->successResponse(AplicacaoResource::make($application)->resolve($request), 201);
    }

    public function show(Request $request, Aplicacao $aplicacao): JsonResponse
    {
        Gate::authorize('view', $aplicacao);
        $aplicacao->loadCount(['alunos', 'leituras', 'resultados']);

        return $this->successResponse(AplicacaoResource::make($aplicacao)->resolve($request));
    }

    public function students(Request $request, Aplicacao $aplicacao): JsonResponse
    {
        Gate::authorize('view', $aplicacao);

        return $this->successResponse($aplicacao->alunos()
            ->with('aluno:id,nome,nome_social,matricula')
            ->orderBy('id')
            ->get()
            ->map(fn ($student): array => [
                'id' => $student->id,
                'aluno_id' => $student->aluno_id,
                'nome' => $student->aluno->nome_social ?: $student->aluno->nome,
                'matricula' => $student->aluno->matricula,
                'status' => $student->status,
                'resultado_vigente_id' => $student->resultado_vigente_id,
            ]));
    }

    public function start(RunAplicacaoRequest $request, Aplicacao $aplicacao, StartAplicacaoAction $action): JsonResponse
    {
        return $this->successResponse(AplicacaoResource::make(
            $action->execute($aplicacao, $this->actor($request))
        )->resolve($request));
    }

    public function finish(RunAplicacaoRequest $request, Aplicacao $aplicacao, FinishAplicacaoAction $action): JsonResponse
    {
        return $this->successResponse(AplicacaoResource::make(
            $action->execute($aplicacao, $this->actor($request))
        )->resolve($request));
    }

    private function actor(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return $user;
    }
}
