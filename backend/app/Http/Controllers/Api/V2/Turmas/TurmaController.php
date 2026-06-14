<?php

namespace App\Http\Controllers\Api\V2\Turmas;

use App\Actions\StudentImports\CreateStudentImportAction;
use App\Actions\StudentImports\ProcessStudentImportSyncAction;
use App\Actions\Turmas\CreateTurmaAction;
use App\Enums\MatriculaTurmaStatus;
use App\Enums\StudentImportStatus;
use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\V2\Turmas\ImportarTurmaRequest;
use App\Http\Requests\Api\V2\Turmas\ListAlunosTurmaRequest;
use App\Http\Requests\Api\V2\Turmas\ListTurmasRequest;
use App\Http\Requests\Api\V2\Turmas\StoreTurmaRequest;
use App\Http\Resources\Api\V2\AlunoResource;
use App\Http\Resources\Api\V2\TurmaResource;
use App\Models\Aluno;
use App\Models\Turma;
use App\Services\Authorization\AlunoScope;
use App\Services\Authorization\TurmaScope;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TurmaController extends BaseApiController
{
    public function index(ListTurmasRequest $request, TurmaScope $scope): JsonResponse
    {
        $query = $scope->apply(Turma::query(), $this->actor($request))
            ->withCount(['matriculas as alunos_ativos' => fn (Builder $q) => $q->where('status', MatriculaTurmaStatus::ACTIVE->value)]);

        if (is_string($q = $request->input('q')) && $q !== '') {
            $query->where('nome', 'like', '%'.$q.'%');
        }

        if (is_string($serie = $request->input('serie')) && $serie !== '') {
            $query->where('serie_ano', $serie);
        }

        // status (em-dia/recuperacao/pendencia) é derivado de resultados (B6) → no-op por ora.

        return $this->paginatedResponse($query->orderBy('nome')->paginate(15), TurmaResource::class);
    }

    public function store(StoreTurmaRequest $request, CreateTurmaAction $action): JsonResponse
    {
        $turma = $action->execute($request->mappedAttributes(), $this->actor($request));

        return $this->successResponse(TurmaResource::make($turma), 201);
    }

    public function show(Request $request, Turma $turma): JsonResponse
    {
        Gate::authorize('view', $turma);

        return $this->successResponse(TurmaResource::make($turma));
    }

    public function alunos(ListAlunosTurmaRequest $request, Turma $turma, AlunoScope $scope): JsonResponse
    {
        $query = $scope->apply(Aluno::query(), $this->actor($request))
            ->whereHas('matriculasTurmas', fn (Builder $m) => $m->where('turma_id', $turma->id))
            ->with(['matriculasTurmas', 'responsaveis.responsavel']);

        if (is_string($q = $request->input('q')) && $q !== '') {
            $query->where('nome', 'like', '%'.$q.'%');
        }

        if (is_string($status = $request->input('status')) && $status !== '') {
            $query->where('status', $status);
        }

        return $this->paginatedResponse($query->orderBy('nome')->paginate(15), AlunoResource::class);
    }

    public function importar(
        ImportarTurmaRequest $request,
        CreateStudentImportAction $create,
        ProcessStudentImportSyncAction $process,
    ): JsonResponse {
        $import = $create->execute(
            $request->file('arquivo'),
            $request->school(),
            $request->class(),
            $this->actor($request),
        );

        if ($import->status === StudentImportStatus::HAS_ERRORS) {
            return $this->errorResponse(
                code: 'VALIDATION_ERROR',
                message: 'O arquivo de importacao contem erros.',
                errors: ['arquivo' => array_map(
                    fn (array $erro): string => ($erro['linha'] ?? '?').': '.($erro['mensagem'] ?? ''),
                    $import->erros,
                )],
                status: 422,
            );
        }

        $import = $process->execute($import, $this->actor($request));

        return $this->successResponse([
            'status' => $import->status->value,
            'resumo' => $import->resumo,
            'erros' => $import->erros,
        ], 202);
    }
}
