<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

final class ApiResponse
{
    public static function success(
        mixed $data = null,
        int $status = 200,
    ): JsonResponse {
        $requestId = self::requestId();

        return response()->json([
            'data' => $data,
            'meta' => [
                'request_id' => $requestId,
            ],
        ], $status)->header('X-Request-ID', $requestId);
    }

    /**
     * @param  array<string, mixed>  $details
     */
    public static function error(
        string $code,
        string $message,
        array $details = [],
        int $status = 400,
    ): JsonResponse {
        $requestId = self::requestId();

        return response()->json([
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details === [] ? (object) [] : $details,
            ],
            'meta' => [
                'request_id' => $requestId,
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
