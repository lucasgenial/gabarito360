<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Turma;
use App\Support\Escopo;
use App\Support\Espacos;
use App\Support\GestaoLegada;
use App\Support\ContextoVinculo;
use App\Support\GestaoVinculos;
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

        if (ContextoVinculo::presente($request->user())) {
            return ApiResponse::success(['professores' => GestaoVinculos::pessoas($request->user(), (int) $turma->escola_id, $turmaId)->orderBy('usuarios.nome')->get()]);
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

        if (ContextoVinculo::presente($request->user())) return $this->alterarContextual($request, $turmaId, (int) $data['usuario_id'], true);

        abort_if(DB::table('usuario_vinculos')->where('usuario_id', $data['usuario_id'])->exists(), 422, 'Professor convertido exige gestão por vínculo.');
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
        if (ContextoVinculo::presente($request->user())) return $this->alterarContextual($request, $turmaId, $usuarioId, false);
        if (!$this->turmaNoEscopo($request, $turmaId)) {
            return ApiResponse::notFound('Turma não encontrada.');
        }

        abort_if(DB::table('usuario_vinculos')->where('usuario_id', $usuarioId)->exists(), 422, 'Professor convertido exige gestão por vínculo.');
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
        if (ContextoVinculo::presente($request->user())) {
            $v = $usuarioId === (int) $request->user()->id ? ContextoVinculo::resolver($request->user()) : GestaoVinculos::alvo($request, $usuarioId, false);
            abort_if($request->has('vinculo_id') && (int) $request->input('vinculo_id') !== (int) $v->id, 404);
            abort_unless($v->perfil === 'professor', 404, 'Professor não encontrado.');
            $turmas = Escopo::turmas($request->user())->whereIn('turmas.id', DB::table('vinculo_turmas')->where('vinculo_id', $v->id)
                ->where('espaco_id', $v->espaco_id)->select('turma_id'))->where('turmas.espaco_id', $v->espaco_id)
                ->join('escolas', 'turmas.escola_id', '=', 'escolas.id')
                ->get(['turmas.id', 'turmas.nome', 'turmas.serie', 'turmas.turno', 'turmas.ano_letivo', 'turmas.ativo', 'escolas.nome as escola_nome']);
            return ApiResponse::success(['turmas' => $turmas]);
        }

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
    private function alterarContextual(Request $request, int $turmaId, int $pessoa, bool $adicionar): JsonResponse
    {
        return DB::transaction(function () use ($request, $turmaId, $pessoa, $adicionar) {
            $turma = $this->turmaNoEscopo($request, $turmaId);
            abort_unless($turma, 404, 'Turma não encontrada.');
            $v = GestaoVinculos::alvo($request, $pessoa, lock: true);
            abort_unless($v->perfil === 'professor' && (int) $v->espaco_id === (int) $turma->espaco_id, 404, 'Professor não encontrado nesta escola.');
            abort_unless($v->situacao === 'ativo' && $v->inicio_em <= now() && (!$v->fim_em || $v->fim_em > now()), 422, 'Vínculo indisponível.');
            $chave = ['vinculo_id' => $v->id, 'espaco_id' => $v->espaco_id, 'turma_id' => $turmaId];
            $existe = DB::table('vinculo_turmas')->where($chave)->exists();
            if ($adicionar && !$existe) DB::table('vinculo_turmas')->insert($chave);
            if (!$adicionar && $existe) DB::table('vinculo_turmas')->where($chave)->delete();
            if ($existe !== $adicionar) GestaoVinculos::evento($v, $request->user(), $adicionar ? 'turma_adicionada' : 'turma_removida', 'turma:'.$turmaId);
            return ApiResponse::success([$adicionar ? 'vinculado' : 'removido' => true, 'vinculo_id' => $v->id], null, $adicionar ? 201 : 200);
        });
    }

}
