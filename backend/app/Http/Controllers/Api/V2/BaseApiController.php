<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

abstract class BaseApiController extends Controller
{
    /**
     * @param  array<string, mixed>  $extraMeta
     */
    protected function successResponse(
        mixed $data = null,
        int $status = 200,
        array $extraMeta = [],
    ): JsonResponse {
        return ApiResponse::success($data, $status, $extraMeta);
    }

    /**
     * Resposta de lista paginada no envelope do contrato
     * (`data` + `meta` com page/per_page/total).
     *
     * @param  LengthAwarePaginator<int, Model>  $paginator
     * @param  class-string<JsonResource>  $resource
     */
    protected function paginatedResponse(
        LengthAwarePaginator $paginator,
        string $resource,
        int $status = 200,
    ): JsonResponse {
        return ApiResponse::success(
            $resource::collection($paginator->getCollection()),
            $status,
            [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        );
    }

    protected function actor(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
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
