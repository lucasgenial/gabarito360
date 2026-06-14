<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

final class ApiResponse
{
    /**
     * @param  array<string, mixed>  $extraMeta
     */
    public static function success(
        mixed $data = null,
        int $status = 200,
        array $extraMeta = [],
    ): JsonResponse {
        $requestId = self::requestId();

        return response()->json([
            'data' => $data,
            'meta' => [
                'request_id' => $requestId,
                ...$extraMeta,
            ],
        ], $status)->header('X-Request-ID', $requestId);
    }

    /**
     * @param  array<string, mixed>  $errors
     */
    public static function error(
        string $code,
        string $message,
        array $errors = [],
        int $status = 400,
    ): JsonResponse {
        $requestId = self::requestId();

        return response()->json([
            'message' => $message,
            'errors' => $errors === [] ? (object) [] : $errors,
            'meta' => [
                'request_id' => $requestId,
                'code' => $code,
            ],
        ], $status)->header('X-Request-ID', $requestId);
    }

    private static function requestId(): string
    {
        $requestId = request()->attributes->get('request_id');

        if (is_string($requestId) && Str::isUuid($requestId)) {
            return $requestId;
        }

        $requestId = (string) Str::uuid();
        request()->attributes->set('request_id', $requestId);

        return $requestId;
    }
}
