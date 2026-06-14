<?php

namespace App\Http\Controllers\Api\V2\Auth;

use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\V2\Auth\ForgotPasswordRequest;
use Illuminate\Support\Facades\Password;
use Symfony\Component\HttpFoundation\Response;

class ForgotPasswordController extends BaseApiController
{
    public function __invoke(ForgotPasswordRequest $request): Response
    {
        // Resposta neutra (202) independentemente da existência do e-mail,
        // para não permitir enumeração de contas.
        Password::sendResetLink(['email' => $request->validated()['email']]);

        return response()->noContent(Response::HTTP_ACCEPTED);
    }
}
