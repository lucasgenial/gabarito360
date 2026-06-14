<?php

namespace App\Http\Controllers\Api\V2\Provas;

use App\Actions\Provas\CreateProvaComQuestoesAction;
use App\Actions\Provas\PublishProva;
use App\Actions\Provas\SyncProvaQuestoesAction;
use App\Actions\Provas\UpdateProvaAction;
use App\Enums\GabaritoOficialStatus;
use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\V2\Provas\ListProvasRequest;
use App\Http\Requests\Api\V2\Provas\StoreProvaRequest;
use App\Http\Requests\Api\V2\Provas\UpdateProvaRequest;
use App\Http\Resources\Api\V2\ProvaResource;
use App\Models\Prova;
use App\Services\Authorization\ProvaScope;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class ProvaController extends BaseApiController
{
    public function index(ListProvasRequest $request, ProvaScope $scope): JsonResponse
    {
        $query = $scope->apply(Prova::query(), $this->actor($request))
            ->with(['disciplina', 'serieAno', 'provaTurmas.turma', 'gabaritosOficiais']);

        if (is_string($q = $request->input('q')) && $q !== '') {
            $query->where('titulo', 'like', '%'.$q.'%');
        }

        if (is_string($d = $request->input('disciplina')) && $d !== '') {
            $query->whereHas('disciplina', fn (Builder $x) => $x->where('nome', 'like', '%'.$d.'%')->orWhere('codigo', Str::slug($d)));
        }

        if (is_string($status = $request->input('status'))) {
            $query->whereIn('status', $this->internalStatuses($status));
        }

        return $this->paginatedResponse($query->orderByDesc('created_at')->paginate(15), ProvaResource::class);
    }

    public function store(StoreProvaRequest $request, CreateProvaComQuestoesAction $action): JsonResponse
    {
        $prova = $action->execute($request->mappedAttributes(), $this->actor($request));

        return $this->successResponse(ProvaResource::make($this->load($prova)), 201);
    }

    public function show(Request $request, Prova $prova): JsonResponse
    {
        Gate::authorize('view', $prova);

        return $this->successResponse(ProvaResource::make($this->load($prova)));
    }

    public function update(UpdateProvaRequest $request, Prova $prova, UpdateProvaAction $update, SyncProvaQuestoesAction $sync): JsonResponse
    {
        $mapped = $request->mappedAttributes();

        if ($mapped !== []) {
            $update->execute($prova, $mapped, $this->actor($request));
        }

        if ($request->has('num_questoes')) {
            $sync->execute($prova);
        }

        return $this->successResponse(ProvaResource::make($this->load($prova->refresh())));
    }

    public function publicar(Request $request, Prova $prova, PublishProva $publish): JsonResponse
    {
        Gate::authorize('publish', $prova);

        $gabarito = $prova->gabaritosOficiais()
            ->where('status', GabaritoOficialStatus::DRAFT->value)
            ->latest('versao')
            ->first();

        if ($gabarito === null) {
            throw new ConflictHttpException('A prova nao possui gabarito rascunho para publicar.');
        }

        try {
            $result = $publish->execute($prova, $gabarito, $this->actor($request));
        } catch (ValidationException) {
            // Contrato: gabarito incompleto também é 409 (conflito de estado).
            throw new ConflictHttpException('O gabarito oficial esta incompleto.');
        }

        return $this->successResponse(ProvaResource::make($this->load($result['prova'])));
    }

    /**
     * @return list<string>
     */
    private function internalStatuses(string $contractStatus): array
    {
        return match ($contractStatus) {
            'rascunho' => ['rascunho'],
            'publicada' => ['publicada'],
            'corrigida' => ['finalizada', 'arquivada'],
            default => ['__none__'], // 'correcao' é derivado de aplicações (B5)
        };
    }

    private function load(Prova $prova): Prova
    {
        return $prova->load(['disciplina', 'serieAno', 'provaTurmas.turma', 'gabaritosOficiais']);
    }
}
