<?php

namespace App\Http\Controllers\Api\V2\Alunos;

use App\Actions\Alunos\CreateAlunoComMatriculaAction;
use App\Actions\Alunos\SyncResponsavelAction;
use App\Actions\Alunos\TransferAlunoAction;
use App\Actions\Alunos\UpdateAlunoAction;
use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\V2\Alunos\StoreAlunoRequest;
use App\Http\Requests\Api\V2\Alunos\UpdateAlunoRequest;
use App\Http\Requests\Api\V2\Alunos\UpdateFotoAlunoRequest;
use App\Http\Resources\Api\V2\AlunoResource;
use App\Models\Aluno;
use App\Models\Arquivo;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use App\Services\Authorization\AlunoScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AlunoController extends BaseApiController
{
    public function store(StoreAlunoRequest $request, CreateAlunoComMatriculaAction $action): JsonResponse
    {
        $student = $action->execute(
            $request->turma(),
            $request->mappedAttributes(),
            $request->input('responsavel'),
            $request->input('numero_chamada'),
            $this->actor($request),
        );

        return $this->successResponse(AlunoResource::make($this->load($student)), 201);
    }

    public function show(Request $request, string $aluno, AlunoScope $scope): JsonResponse
    {
        $student = $scope->apply(Aluno::query(), $this->actor($request))
            ->with(['matriculasTurmas', 'responsaveis.responsavel'])
            ->findOrFail($aluno);

        return $this->successResponse(AlunoResource::make($student));
    }

    public function update(
        UpdateAlunoRequest $request,
        Aluno $aluno,
        UpdateAlunoAction $update,
        TransferAlunoAction $transfer,
        SyncResponsavelAction $sync,
    ): JsonResponse {
        $actor = $this->actor($request);
        $mapped = $request->mappedAttributes();

        if ($mapped !== []) {
            $update->execute($aluno, $mapped, $actor);
        }

        if ($request->has('turma_id') && ($destino = $request->turmaDestino()) !== null) {
            $transfer->execute($aluno, $destino, $request->input('numero_chamada'), $actor);
        }

        if ($request->filled('responsavel')) {
            $sync->execute($aluno, (string) $request->input('responsavel'));
        }

        return $this->successResponse(AlunoResource::make($this->load($aluno->refresh())));
    }

    public function avaliacoes(Request $request, string $aluno, AlunoScope $scope): JsonResponse
    {
        // Garante acesso (404 fora de escopo). Avaliações dependem de resultados (B5/B6) → vazio.
        $scope->apply(Aluno::query(), $this->actor($request))->findOrFail($aluno);

        return $this->successResponse([], 200, ['page' => 1, 'per_page' => 15, 'total' => 0]);
    }

    public function foto(UpdateFotoAlunoRequest $request, Aluno $aluno, AuditService $audit): JsonResponse
    {
        $file = $request->file('foto');
        $disk = (string) config('filesystems.private');
        $actor = $this->actor($request);

        $aluno = DB::transaction(function () use ($aluno, $file, $disk, $actor) {
            $path = Storage::disk($disk)->putFile('alunos/fotos', $file);

            $arquivo = Arquivo::query()->create([
                'disco' => $disk,
                'caminho' => $path,
                'nome_original' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType(),
                'tamanho_bytes' => $file->getSize(),
                'checksum' => hash_file('sha256', $file->getRealPath()),
                'classificacao' => 'interno',
                'proprietario_tipo' => 'aluno',
                'proprietario_id' => $aluno->id,
                'criado_por_id' => $actor->id,
            ]);

            $anterior = $aluno->fotoArquivo;
            $aluno->update(['foto_arquivo_id' => $arquivo->id]);

            if ($anterior !== null) {
                Storage::disk($anterior->disco)->delete($anterior->caminho);
                $anterior->delete();
            }

            return $aluno;
        });

        $audit->record(
            action: AuditAction::STUDENT_UPDATED,
            entityType: 'aluno',
            entityId: $aluno->id,
            actorUserId: $actor->id,
            metadata: ['campo' => 'foto'],
        );

        return $this->successResponse(AlunoResource::make($this->load($aluno)));
    }

    private function load(Aluno $aluno): Aluno
    {
        return $aluno->load(['matriculasTurmas', 'responsaveis.responsavel']);
    }
}
