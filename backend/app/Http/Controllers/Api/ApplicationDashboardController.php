<?php

namespace App\Http\Controllers\Api;

use App\Models\Aplicacao;
use App\Services\Applications\ApplicationMetrics;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ApplicationDashboardController extends BaseApiController
{
    public function __invoke(Request $request, Aplicacao $aplicacao, ApplicationMetrics $metrics): JsonResponse
    {
        Gate::authorize('view', $aplicacao);

        return $this->successResponse([
            'application_id' => $aplicacao->id,
            'channel' => 'private-applications.'.$aplicacao->id,
            'event' => 'application.progress.updated',
            'metrics' => $metrics->for($aplicacao),
        ]);
    }
}
