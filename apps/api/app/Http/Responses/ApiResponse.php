<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function success(mixed $data, ?array $meta = null, int $code = 200): JsonResponse
    {
        $response = ['data' => $data];

        if ($meta !== null) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $code);
    }

    public static function error(string $message, array $errors = [], int $code = 422): JsonResponse
    {
        $response = ['message' => $message];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    public static function unauthorized(string $message = 'Não autenticado.'): JsonResponse
    {
        return response()->json(['message' => $message], 401);
    }

    public static function forbidden(string $message = 'Acesso negado.'): JsonResponse
    {
        return response()->json(['message' => $message], 403);
    }

    public static function notFound(string $message = 'Recurso não encontrado.'): JsonResponse
    {
        return response()->json(['message' => $message], 404);
    }
}
