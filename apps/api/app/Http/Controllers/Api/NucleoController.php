<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Nucleo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NucleoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $usuario = $request->user();

        $query = Nucleo::query()->with('escolas:id,nucleo_id');

        if ($usuario->perfil === 'dir_nucleo') {
            $nucleoId = DB::table('usuario_escopos')
                ->where('usuario_id', $usuario->id)
                ->where('escopo_tipo', 'nucleo')
                ->value('escopo_id');
            $query->where('id', $nucleoId);
        }

        $nucleos = $query->orderBy('nome')->get()->map(function ($n) {
            return [
                'id'           => $n->id,
                'rede_id'      => $n->rede_id,
                'nome'         => $n->nome,
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
        ]);

        $nucleo = Nucleo::create($data);

        return ApiResponse::success($nucleo, 'Núcleo criado com sucesso.', 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $nucleo = Nucleo::with('escolas')->find($id);

        if (!$nucleo) {
            return ApiResponse::notFound('Núcleo não encontrado.');
        }

        $this->verificarAcesso($request, $nucleo->id);

        return ApiResponse::success($nucleo);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $nucleo = Nucleo::find($id);

        if (!$nucleo) {
            return ApiResponse::notFound('Núcleo não encontrado.');
        }

        $data = $request->validate([
            'nome'    => 'sometimes|string|max:200',
            'rede_id' => 'sometimes|integer|exists:redes,id',
        ]);

        $nucleo->update($data);

        return ApiResponse::success($nucleo, 'Núcleo atualizado com sucesso.');
    }

    public function destroy(int $id): JsonResponse
    {
        $nucleo = Nucleo::find($id);

        if (!$nucleo) {
            return ApiResponse::notFound('Núcleo não encontrado.');
        }

        if ($nucleo->escolas()->exists()) {
            return ApiResponse::error('Núcleo possui escolas vinculadas. Reatribua-as antes de excluir.', 422);
        }

        $nucleo->delete();

        return ApiResponse::success(null, 'Núcleo excluído com sucesso.');
    }

    private function verificarAcesso(Request $request, int $nucleoId): void
    {
        $usuario = $request->user();

        if ($usuario->perfil === 'admin_rede') {
            return;
        }

        if ($usuario->perfil === 'dir_nucleo') {
            $meuNucleoId = DB::table('usuario_escopos')
                ->where('usuario_id', $usuario->id)
                ->where('escopo_tipo', 'nucleo')
                ->value('escopo_id');

            if ($meuNucleoId !== $nucleoId) {
                abort(403, 'Acesso restrito ao seu núcleo.');
            }
            return;
        }

        abort(403, 'Sem permissão para acessar núcleos.');
    }
}
