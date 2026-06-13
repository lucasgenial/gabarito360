<?php

namespace App\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;

final class HealthController extends BaseApiController
{
    public function __invoke(): JsonResponse
    {
        return $this->successResponse(
            data: [
                'app' => 'Gabarito360',
                'status' => 'online',
            ],
        );
    }
}
