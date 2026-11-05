<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;

class ValidacaoDocumentoController extends Controller
{
    public function index(Request $request)
    {
        $d = $request->validate(['codigo' => ['nullable', 'string', 'max:40']]);
        return response()->view('documentos.validar', ['codigo' => $d['codigo'] ?? ''])
            ->header('Cache-Control', 'no-store')->header('Referrer-Policy', 'no-referrer');
    }

    public function conferir(Request $request)
    {
        abort_if($request->allFiles(), 422, 'O arquivo deve permanecer no seu dispositivo.');
        $d = $request->validate(['codigo' => ['required', 'string', 'regex:/^G360-[a-fA-F0-9]{32}$/i'],
            'sha256' => ['nullable', 'string', 'regex:/^[a-fA-F0-9]{64}$/']]);
        try {
            // Cliente público sem token, sessão ou contexto de usuário da aplicação.
            $r = Http::baseUrl(rtrim(config('services.api.base_url'), '/'))->acceptJson()->timeout(15)->post('/v1/documentos/validar', $d);
            return response()->json($r->json() ?? ['message' => 'Validação temporariamente indisponível.'], $r->status())
                ->header('Cache-Control', 'no-store');
        } catch (ConnectionException) {
            return response()->json(['message' => 'Validação temporariamente indisponível. Tente novamente.'], 503)->header('Cache-Control', 'no-store');
        }
    }
}
