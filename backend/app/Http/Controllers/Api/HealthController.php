<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;

final class HealthController extends BaseApiController
{
    public function __invoke(): JsonResponse
    {
        return $this->successResponse(
            message: 'API Gabarito360 online',
            data: [
                'app' => 'Gabarito360',
                'status' => 'online',
            ],
        );
    }
}
