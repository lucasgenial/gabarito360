<?php

namespace App\Http\Controllers\Api;

use App\Actions\Leituras\ConfirmReadingAction;
use App\Actions\Leituras\CreatePreliminaryReadingAction;
use App\Actions\Leituras\ReviewReadingAction;
use App\Http\Requests\Leituras\ConfirmLeituraRequest;
use App\Http\Requests\Leituras\ReviewLeituraRequest;
use App\Http\Requests\Leituras\StoreLeituraRequest;
use App\Http\Resources\LeituraCartaoResource;
use App\Http\Resources\ResultadoResource;
use App\Jobs\ProcessOmrReadingJob;
use App\Models\Aplicacao;
use App\Models\LeituraCartao;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LeituraCartaoController extends BaseApiController
{
    public function store(StoreLeituraRequest $request, Aplicacao $aplicacao, CreatePreliminaryReadingAction $action): JsonResponse
    {
        $reading = $action->execute($aplicacao, $request->validated(), $this->actor($request));

        return $this->successResponse(LeituraCartaoResource::make($reading)->resolve($request), 201);
    }

    public function show(Request $request, LeituraCartao $leitura): JsonResponse
    {
        Gate::authorize('view', $leitura->aplicacao);

        return $this->successResponse(LeituraCartaoResource::make(
            $leitura->load('respostasDetectadas')
        )->resolve($request));
    }

    public function review(ReviewLeituraRequest $request, LeituraCartao $leitura, ReviewReadingAction $action): JsonResponse
    {
        $reading = $action->execute($leitura, $request->validated(), $this->actor($request));

        return $this->successResponse(LeituraCartaoResource::make($reading)->resolve($request));
    }

    public function confirm(ConfirmLeituraRequest $request, LeituraCartao $leitura, ConfirmReadingAction $action): JsonResponse
    {
        $confirmed = $action->execute($leitura, $request->idempotencyKey(), $this->actor($request));

        return $this->successResponse([
            'leitura' => LeituraCartaoResource::make($confirmed['reading']->load('respostasDetectadas'))->resolve($request),
            'resultado' => ResultadoResource::make($confirmed['result'])->resolve($request),
        ]);
    }

    public function processOmr(Request $request, LeituraCartao $leitura): JsonResponse
    {
        Gate::authorize('confirmReading', $leitura->aplicacao);
        abort_if($leitura->confirmada_at !== null || $leitura->cancelada_at !== null, 409);
        ProcessOmrReadingJob::dispatch($leitura->id);

        return $this->successResponse(['leitura_id' => $leitura->id, 'status' => 'processamento_solicitado'], 202);
    }

    private function actor(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return $user;
    }
}
