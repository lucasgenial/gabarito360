<?php

namespace App\Http\Controllers\Api\V2\Provas;

use App\Actions\Gabaritos\SyncGabaritoAction;
use App\Enums\GabaritoOficialStatus;
use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\V2\Gabaritos\UpdateGabaritoRequest;
use App\Http\Resources\Api\V2\GabaritoResource;
use App\Models\Prova;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use App\Services\Provas\GabaritoPdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class GabaritoController extends BaseApiController
{
    public function show(Request $request, Prova $prova): JsonResponse
    {
        Gate::authorize('view', $prova);

        $gabarito = $prova->gabaritosOficiais()
            ->where('status', GabaritoOficialStatus::CURRENT->value)
            ->first()
            ?? $prova->gabaritosOficiais()
                ->where('status', GabaritoOficialStatus::DRAFT->value)
                ->latest('versao')
                ->first();

        if ($gabarito === null) {
            return $this->successResponse(['prova_id' => $prova->id, 'versao' => 0, 'respostas' => []]);
        }

        $gabarito->load('respostas.questao');

        return $this->successResponse(GabaritoResource::make($gabarito));
    }

    public function update(UpdateGabaritoRequest $request, Prova $prova, SyncGabaritoAction $sync): JsonResponse
    {
        $gabarito = $sync->execute($prova, $request->respostas(), $this->actor($request));
        $gabarito->load('respostas.questao');

        return $this->successResponse(GabaritoResource::make($gabarito));
    }

    public function pdf(Request $request, Prova $prova, GabaritoPdfService $service, AuditService $audit): Response
    {
        Gate::authorize('view', $prova);

        $prova->load(['disciplina', 'serieAno', 'gabaritosOficiais.respostas.questao']);
        $pdf = $service->render($prova);

        $audit->record(
            action: AuditAction::ANSWER_KEY_EXPORTED,
            entityType: 'prova',
            entityId: $prova->id,
            actorUserId: $this->actor($request)->id,
            escolaId: $prova->escola_id,
        );

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="gabarito-'.$prova->codigo.'.pdf"',
        ]);
    }
}
