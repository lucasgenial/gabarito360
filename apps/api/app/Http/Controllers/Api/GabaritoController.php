<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GabaritoController extends Controller
{
    public function show(int $provaId): JsonResponse
    {
        $prova = DB::table('provas')->find($provaId);
        if (!$prova) {
            return ApiResponse::notFound('Prova não encontrada.');
        }

        $gabarito = DB::table('gabaritos')->where('prova_id', $provaId)->first();

        if (!$gabarito) {
            return ApiResponse::success([
                'gabarito'  => null,
                'questoes'  => [],
                'publicado' => false,
            ]);
        }

        $questoes = DB::table('gabarito_questoes')
            ->where('gabarito_id', $gabarito->id)
            ->orderBy('numero_questao')
            ->get()
            ->map(fn($q) => [
                'numero_questao' => (int) $q->numero_questao,
                'alternativa'    => $q->alternativa,
                'peso'           => (float) $q->peso,
                'anulada'        => (bool) $q->anulada,
            ])
            ->values()
            ->toArray();

        return ApiResponse::success([
            'gabarito' => [
                'id'            => $gabarito->id,
                'prova_id'      => $gabarito->prova_id,
                'publicado_em'  => $gabarito->publicado_em,
                'publicado_por' => $gabarito->publicado_por,
            ],
            'questoes'  => $questoes,
            'publicado' => !is_null($gabarito->publicado_em),
        ]);
    }

    public function salvar(Request $request, int $provaId): JsonResponse
    {
        $prova = DB::table('provas')->find($provaId);
        if (!$prova) {
            return ApiResponse::notFound('Prova não encontrada.');
        }

        $gabarito = DB::table('gabaritos')->where('prova_id', $provaId)->first();
        if ($gabarito && !is_null($gabarito->publicado_em)) {
            return ApiResponse::error('Gabarito já publicado. Não pode ser editado.', [], 422);
        }

        $request->validate([
            'questoes'                  => 'required|array|min:1',
            'questoes.*.numero_questao' => 'required|integer|min:1|max:100',
            'questoes.*.alternativa'    => 'required|string|in:A,B,C,D,E',
            'questoes.*.peso'           => 'nullable|numeric|min:0',
            'questoes.*.anulada'        => 'nullable|boolean',
        ]);

        DB::beginTransaction();
        try {
            if (!$gabarito) {
                $gabaritoId = DB::table('gabaritos')->insertGetId([
                    'prova_id'   => $provaId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $gabaritoId = $gabarito->id;
                DB::table('gabaritos')->where('id', $gabaritoId)->update(['updated_at' => now()]);
            }

            DB::table('gabarito_questoes')->where('gabarito_id', $gabaritoId)->delete();

            $rows = [];
            foreach ($request->input('questoes') as $q) {
                $rows[] = [
                    'gabarito_id'    => $gabaritoId,
                    'numero_questao' => (int) $q['numero_questao'],
                    'alternativa'    => strtoupper($q['alternativa']),
                    'peso'           => $q['peso'] ?? 1.0,
                    'anulada'        => $q['anulada'] ?? false,
                ];
            }
            DB::table('gabarito_questoes')->insert($rows);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return ApiResponse::error('Erro ao salvar gabarito.', [], 500);
        }

        return ApiResponse::success(['salvo' => true]);
    }

    public function publicar(Request $request, int $provaId): JsonResponse
    {
        $usuario = $request->user();

        $prova = DB::table('provas')->find($provaId);
        if (!$prova) {
            return ApiResponse::notFound('Prova não encontrada.');
        }

        $gabarito = DB::table('gabaritos')->where('prova_id', $provaId)->first();
        if (!$gabarito) {
            return ApiResponse::error('Gabarito não preenchido. Preencha o gabarito antes de publicar.', [], 422);
        }

        if (!is_null($gabarito->publicado_em)) {
            return ApiResponse::error('Gabarito já foi publicado.', [], 422);
        }

        $totalPreenchidas = DB::table('gabarito_questoes')
            ->where('gabarito_id', $gabarito->id)
            ->count();

        if ($totalPreenchidas < $prova->num_questoes) {
            return ApiResponse::error(
                "Gabarito incompleto. Preencha todas as {$prova->num_questoes} questões antes de publicar.",
                [],
                422
            );
        }

        DB::table('gabaritos')->where('id', $gabarito->id)->update([
            'publicado_por' => $usuario->id,
            'publicado_em'  => now(),
            'updated_at'    => now(),
        ]);

        DB::table('provas')->where('id', $provaId)->update([
            'status'     => 'publicada',
            'updated_at' => now(),
        ]);

        return ApiResponse::success(['publicado' => true]);
    }
}
