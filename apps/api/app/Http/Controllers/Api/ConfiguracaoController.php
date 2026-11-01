<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConfiguracaoController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        if (\App\Support\Espacos::ativo() && $request->boolean('listar_redes')) {
            $ids = \App\Support\Instituicoes::redesIds($request->user()) ?? [];
            return ApiResponse::success(['redes' => DB::table('redes')->whereIn('id', $ids)->orderBy('nome')->get(['id', 'nome'])]);
        }
        $rede = \App\Support\Instituicoes::redeSelecionada($request);

        if (!$rede) {
            return ApiResponse::notFound('Rede não configurada.');
        }

        return ApiResponse::success([
            'rede_id'          => $rede->id,
            'nome'             => $rede->nome,
            'meta_media'       => (float) $rede->meta_media,
            'meta_minima'      => (float) $rede->meta_minima,
            'limiar_seges_min' => (int) $rede->limiar_seges_min,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $rede = \App\Support\Instituicoes::redeSelecionada($request);

        if (!$rede) {
            return ApiResponse::notFound('Rede não configurada.');
        }

        $data = $request->validate([
            'meta_media'       => 'required|numeric|min:0|max:10',
            'meta_minima'      => 'required|numeric|min:0|max:10',
            'limiar_seges_min' => 'required|integer|min:1|max:1440',
        ]);

        DB::table('redes')->where('id', $rede->id)->update(array_merge($data, ['updated_at' => now()]));

        return ApiResponse::success(['atualizado' => true]);
    }
}
