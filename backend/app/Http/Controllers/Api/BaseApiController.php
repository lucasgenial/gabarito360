<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

abstract class BaseApiController extends Controller
{
    protected function successResponse(
        string $message,
        mixed $data = null,
        int $status = 200,
    ): JsonResponse {
        return ApiResponse::success($message, $data, $status);
    }

    /**
     * @param  array<string, mixed>  $errors
     */
    protected function errorResponse(
        string $message,
        array $errors = [],
        int $status = 400,
        mixed $data = null,
    ): JsonResponse {
        return ApiResponse::error($message, $errors, $status, $data);
    }
}
