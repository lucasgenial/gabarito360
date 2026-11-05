<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\EmissaoDocumento;
use Illuminate\Http\Request;

class ValidacaoDocumentoController extends Controller
{
    public function __invoke(Request $request, EmissaoDocumento $emissao)
    {
        $d = $request->validate(['codigo' => ['required', 'string', 'regex:/^G360-[a-fA-F0-9]{32}$/i'],
            'sha256' => ['nullable', 'string', 'regex:/^[a-fA-F0-9]{64}$/']]);
        return ApiResponse::success($emissao->verificar($d['codigo'], $d['sha256'] ?? null))
            ->header('Cache-Control', 'no-store')->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
