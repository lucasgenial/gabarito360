<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Turma;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VinculoProfessorController extends Controller
{
    public function index(int $turmaId): JsonResponse
    {
        $turma = Turma::find($turmaId);

        if (!$turma) {
            return ApiResponse::notFound('Turma não encontrada.');
        }

        $professores = DB::table('usuarios')
            ->join('usuario_escopos', function ($j) use ($turmaId) {
                $j->on('usuarios.id', '=', 'usuario_escopos.usuario_id')
                  ->where('usuario_escopos.escopo_tipo', 'turma')
                  ->where('usuario_escopos.escopo_id', $turmaId);
            })
            ->where('usuarios.perfil', 'professor')
            ->select('usuarios.id', 'usuarios.nome', 'usuarios.email', 'usuarios.ativo')
            ->orderBy('usuarios.nome')
            ->get();

        return ApiResponse::success(['professores' => $professores]);
    }

    public function store(Request $request, int $turmaId): JsonResponse
    {
        $turma = Turma::find($turmaId);

        if (!$turma) {
            return ApiResponse::notFound('Turma não encontrada.');
        }

        $data = $request->validate([
            'usuario_id' => 'required|integer|exists:usuarios,id',
        ]);

        $professor = DB::table('usuarios')
            ->where('id', $data['usuario_id'])
            ->where('perfil', 'professor')
            ->first();

        if (!$professor) {
            return ApiResponse::error('Usuário não é professor.', [], 422);
        }

        $existe = DB::table('usuario_escopos')
            ->where('usuario_id', $data['usuario_id'])
            ->where('escopo_tipo', 'turma')
            ->where('escopo_id', $turmaId)
            ->exists();

        if (!$existe) {
            DB::table('usuario_escopos')->insert([
                'usuario_id'  => $data['usuario_id'],
                'escopo_tipo' => 'turma',
                'escopo_id'   => $turmaId,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        return ApiResponse::success(['vinculado' => true], null, 201);
    }

    public function destroy(int $turmaId, int $usuarioId): JsonResponse
    {
        DB::table('usuario_escopos')
            ->where('usuario_id', $usuarioId)
            ->where('escopo_tipo', 'turma')
            ->where('escopo_id', $turmaId)
            ->delete();

        return ApiResponse::success(['removido' => true]);
    }

    public function turmasDoProfessor(int $usuarioId): JsonResponse
    {
        $professor = DB::table('usuarios')
            ->where('id', $usuarioId)
            ->where('perfil', 'professor')
            ->first();

        if (!$professor) {
            return ApiResponse::notFound('Professor não encontrado.');
        }

        $turmas = DB::table('turmas')
            ->join('usuario_escopos', function ($j) use ($usuarioId) {
                $j->on('turmas.id', '=', 'usuario_escopos.escopo_id')
                  ->where('usuario_escopos.usuario_id', $usuarioId)
                  ->where('usuario_escopos.escopo_tipo', 'turma');
            })
            ->join('escolas', 'turmas.escola_id', '=', 'escolas.id')
            ->select('turmas.id', 'turmas.nome', 'turmas.serie', 'turmas.turno', 'turmas.ano_letivo', 'turmas.ativo', 'escolas.nome as escola_nome')
            ->orderBy('turmas.nome')
            ->get();

        return ApiResponse::success(['turmas' => $turmas]);
    }
}
