<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Turma;
use App\Support\Escopo;
use App\Support\Espacos;
use App\Support\GestaoLegada;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VinculoProfessorController extends Controller
{
    public function index(Request $request, int $turmaId): JsonResponse
    {
        $turma = $this->turmaNoEscopo($request, $turmaId);

        if (!$turma) {
            return ApiResponse::notFound('Turma não encontrada.');
        }

        $professores = \App\Models\Usuario::query()
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
        $turma = $this->turmaNoEscopo($request, $turmaId);

        if (!$turma) {
            return ApiResponse::notFound('Turma não encontrada.');
        }

        $data = $request->validate([
            'usuario_id' => 'required|integer|exists:usuarios,id',
        ]);

        $professor = \App\Models\Usuario::query()
            ->where('id', $data['usuario_id'])
            ->where('perfil', 'professor')
            ->first();

        if (!$professor) {
            return ApiResponse::error('Usuário não é professor.', [], 422);
        }

        $daEscola = DB::table('usuario_escopos')->where('usuario_id', $professor->id)
            ->where('escopo_tipo', 'escola')->where('escopo_id', $turma->escola_id)->exists();
        if (!$daEscola) return ApiResponse::notFound('Professor não encontrado nesta escola.');

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
            ]);
        }

        return ApiResponse::success(['vinculado' => true], null, 201);
    }

    public function destroy(Request $request, int $turmaId, int $usuarioId): JsonResponse
    {
        if (!$this->turmaNoEscopo($request, $turmaId)) {
            return ApiResponse::notFound('Turma não encontrada.');
        }

        DB::table('usuario_escopos')
            ->where('usuario_id', $usuarioId)
            ->where('escopo_tipo', 'turma')
            ->where('escopo_id', $turmaId)
            ->delete();

        return ApiResponse::success(['removido' => true]);
    }

    private function turmaNoEscopo(Request $request, int $turmaId): ?Turma
    {
        return Escopo::turmas($request->user())->find($turmaId);
    }

    public function turmasDoProfessor(Request $request, int $usuarioId): JsonResponse
    {
        $professor = (Espacos::ativo() && $usuarioId !== (int) $request->user()->id ? GestaoLegada::usuarios($request->user()) : \App\Models\Usuario::query())
            ->where('usuarios.id', $usuarioId)
            ->where('perfil', 'professor')
            ->first();

        if (!$professor) {
            return ApiResponse::notFound('Professor não encontrado.');
        }

        $turmas = Escopo::turmas($request->user())
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
