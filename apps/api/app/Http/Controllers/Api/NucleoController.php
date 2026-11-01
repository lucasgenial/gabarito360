<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Nucleo;
use App\Support\Instituicoes;
use App\Support\Espacos;
use App\Support\Escopo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NucleoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $escolas = Escopo::escolaIds($request->user());
        $query = Instituicoes::nucleos($request->user())->with(['escolas' => fn ($q) => $q
            ->when($escolas !== null, fn ($q) => $q->whereIn('id', $escolas))->select('id', 'nucleo_id')]);

        $nucleos = $query->orderBy('nome')->get()->map(function ($n) {
            return [
                'id'           => $n->id,
                'rede_id'      => $n->rede_id,
                'nome'         => $n->nome,
                'orgao_regional' => $n->orgao_regional,
                'total_escolas'=> $n->escolas->count(),
                'created_at'   => $n->created_at,
            ];
        });

        return ApiResponse::success(['nucleos' => $nucleos]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nome'    => 'required|string|max:200',
            'rede_id' => 'required|integer|exists:redes,id',
            'orgao_regional' => 'nullable|string|max:20',
        ]);

        $redes = Instituicoes::redesIds($request->user());
        abort_unless($request->user()->perfil === 'admin_rede' && ($redes === null || in_array((int) $data['rede_id'], $redes, true)), 404);
        $nucleo = DB::transaction(function () use ($data, $request) {
            $cliente = Espacos::ativo() ? Instituicoes::redesAtivas()->where('r.id', $data['rede_id'])->lockForUpdate()->value('er.cliente_id') : null;
            if (Espacos::ativo()) abort_unless($cliente, 404);
            $n = Nucleo::create($data);
            if (Espacos::ativo()) Instituicoes::criarEspaco('nucleo', $n->id, $n->nome, $cliente, $request->user());
            return $n;
        });

        return ApiResponse::success($nucleo, null, 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $nucleo = Instituicoes::nucleos($request->user())->with(['escolas' => fn ($q) => $q->when(Escopo::escolaIds($request->user()) !== null, fn ($q) => $q->whereIn('id', Escopo::escolaIds($request->user())))])->find($id);

        if (!$nucleo) {
            return ApiResponse::notFound('Núcleo não encontrado.');
        }

        return ApiResponse::success($nucleo);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $nucleo = Instituicoes::nucleos($request->user())->find($id);

        if (!$nucleo) {
            return ApiResponse::notFound('Núcleo não encontrado.');
        }

        $data = $request->validate([
            'nome'    => 'sometimes|string|max:200',
            'rede_id' => 'sometimes|integer|exists:redes,id',
            'orgao_regional' => 'sometimes|nullable|string|max:20',
        ]);

        if (Espacos::ativo() && isset($data['rede_id'])) abort_unless((int) $data['rede_id'] === (int) $nucleo->rede_id, 422, 'Mudança de rede exige procedimento próprio.');
        $nucleo->update($data);

        return ApiResponse::success($nucleo);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $nucleo = Instituicoes::nucleos($request->user())->find($id);

        if (!$nucleo) {
            return ApiResponse::notFound('Núcleo não encontrado.');
        }

        if ($nucleo->escolas()->exists()) {
            return ApiResponse::error('Núcleo possui escolas vinculadas. Reatribua-as antes de excluir.', [], 422);
        }

        if (DB::table('espacos')->where('nucleo_id', $id)->exists()) return ApiResponse::error('Núcleo com espaço registrado; encerramento exige procedimento próprio.', [], 422);
        $nucleo->delete();

        return ApiResponse::success(null);
    }

}
