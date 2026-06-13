<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

abstract class BaseApiController extends Controller
{
    protected function successResponse(
        mixed $data = null,
        int $status = 200,
    ): JsonResponse {
        return ApiResponse::success($data, $status);
    }

    /**
     * @param  array<string, mixed>  $errors
     */
    protected function errorResponse(
        string $code,
        string $message,
        array $errors = [],
        int $status = 400,
    ): JsonResponse {
        return ApiResponse::error($code, $message, $errors, $status);
    }
}
