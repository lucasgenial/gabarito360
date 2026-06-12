<?php

namespace App\Http\Controllers\Api;

use App\Actions\Relatorios\GenerateApplicationCsvReportAction;
use App\Http\Requests\Relatorios\StoreApplicationReportRequest;
use App\Http\Resources\RelatorioResource;
use App\Models\Aplicacao;
use App\Models\Relatorio;
use App\Models\User;
use App\Services\Authorization\PortalScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RelatorioController extends BaseApiController
{
    public function index(Request $request, PortalScope $scope): JsonResponse
    {
        $reports = $scope->applyReports(Relatorio::query(), $this->actor($request))
            ->orderByDesc('solicitado_at')
            ->get();

        return $this->successResponse(RelatorioResource::collection($reports)->resolve($request));
    }

    public function store(StoreApplicationReportRequest $request, Aplicacao $aplicacao, GenerateApplicationCsvReportAction $action): JsonResponse
    {
        $aplicacao->loadMissing('escola');
        $report = $action->execute($aplicacao, $this->actor($request));

        return $this->successResponse(RelatorioResource::make($report)->resolve($request), 201);
    }

    public function show(Request $request, Relatorio $relatorio): JsonResponse
    {
        Gate::authorize('view', $relatorio);

        return $this->successResponse(RelatorioResource::make($relatorio)->resolve($request));
    }

    public function download(Request $request, Relatorio $relatorio): StreamedResponse
    {
        Gate::authorize('view', $relatorio);
        abort_unless($relatorio->arquivo !== null && $relatorio->status === 'concluido', 404);

        return Storage::disk($relatorio->arquivo->disco)
            ->download($relatorio->arquivo->caminho, $relatorio->arquivo->nome_original);
    }

    private function actor(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return $user;
    }
}
