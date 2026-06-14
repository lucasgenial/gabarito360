<?php

namespace App\Http\Controllers\Api\V2\Me;

use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Resources\Api\V2\UsuarioResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends BaseApiController
{
    public function __invoke(Request $request): JsonResponse
    {
        return $this->successResponse(UsuarioResource::make($request->user()));
    }
}
