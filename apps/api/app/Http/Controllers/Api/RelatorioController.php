<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Prova;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RelatorioController extends Controller
{
    private function escopoEscolaIds(Request $request): ?array
    {
        $usuario = $request->user();

        return match ($usuario->perfil) {
            'admin_rede' => null,
            'dir_nucleo' => DB::table('escolas')
                ->join('nucleos', 'escolas.nucleo_id', '=', 'nucleos.id')
                ->join('usuario_escopos', function ($j) use ($usuario) {
                    $j->on('nucleos.id', '=', 'usuario_escopos.escopo_id')
                      ->where('usuario_escopos.usuario_id', $usuario->id)
                      ->where('usuario_escopos.escopo_tipo', 'nucleo');
                })
                ->pluck('escolas.id')->all(),
            'dir_escolar', 'coordenador', 'professor' => DB::table('usuario_escopos')
                ->where('usuario_id', $usuario->id)
                ->where('escopo_tipo', 'escola')
                ->pluck('escopo_id')->all(),
            default => [],
        };
    }

    private function encontrarProva(Request $request, int $provaId): ?Prova
    {
        $escolaIds = $this->escopoEscolaIds($request);
        $query     = Prova::query();
        if ($escolaIds !== null) {
            $query->whereIn('escola_id', $escolaIds);
        }

        return $query->find($provaId);
    }

    private function metaRede(int $escolaId): float
    {
        $meta = DB::table('escolas')
            ->join('nucleos', 'escolas.nucleo_id', '=', 'nucleos.id')
            ->join('redes', 'nucleos.rede_id', '=', 'redes.id')
            ->where('escolas.id', $escolaId)
            ->value('redes.meta_media');

        return $meta !== null ? (float) $meta : 7.0;
    }

    private function tabelaAlunos(int $provaId, ?int $turmaId = null): array
    {
        $query = DB::table('notas')
            ->join('alunos', 'alunos.id', '=', 'notas.aluno_id')
            ->join('turmas', 'turmas.id', '=', 'notas.turma_id')
            ->where('notas.prova_id', $provaId);

        if ($turmaId !== null) {
            $query->where('notas.turma_id', $turmaId);
        }

        return $query
            ->select(
                'alunos.id as aluno_id',
                'alunos.nome as aluno_nome',
                'turmas.id as turma_id',
                'turmas.nome as turma_nome',
                'notas.nota_final',
                'notas.status_aprovacao'
            )
            ->orderBy('alunos.nome')
            ->get()
            ->toArray();
    }

    private function kpis(Prova $prova, ?int $turmaId = null): array
    {
        $cartoesQuery = DB::table('cartoes')->where('prova_id', $prova->id);
        $notasQuery   = DB::table('notas')->where('prova_id', $prova->id);

        if ($turmaId !== null) {
            $cartoesQuery->whereIn('aluno_id', DB::table('alunos')->where('turma_id', $turmaId)->pluck('id'));
            $notasQuery->where('turma_id', $turmaId);
        }

        $totalCartoes = (clone $cartoesQuery)->count();
        $lidos        = (clone $cartoesQuery)->where('status', 'lido')->count();
        $ambiguos     = (clone $cartoesQuery)->where('status', 'ambiguo')->count();

        $totalNotas    = (clone $notasQuery)->count();
        $aprovados     = (clone $notasQuery)->where('status_aprovacao', 'aprovado')->count();
        $media         = (clone $notasQuery)->avg('nota_final');
        $maiorNota     = (clone $notasQuery)->max('nota_final');

        return [
            'media'             => $media !== null ? round((float) $media, 1) : 0.0,
            'meta_rede'         => $this->metaRede($prova->escola_id),
            'aprovacao_pct'     => $totalNotas > 0 ? round(($aprovados / $totalNotas) * 100) : 0,
            'aprovados'         => $aprovados,
            'total_alunos'      => $totalNotas,
            'cartoes_lidos'     => $lidos,
            'cartoes_total'     => $totalCartoes,
            'pendencias'        => $ambiguos,
            'maior_nota'        => $maiorNota !== null ? round((float) $maiorNota, 1) : null,
        ];
    }

    public function prova(Request $request, int $id): JsonResponse
    {
        $prova = $this->encontrarProva($request, $id);
        if (!$prova) {
            return ApiResponse::notFound('Prova não encontrada.');
        }

        $turmas = DB::table('prova_turmas')
            ->join('turmas', 'prova_turmas.turma_id', '=', 'turmas.id')
            ->where('prova_turmas.prova_id', $id)
            ->pluck('turmas.nome');

        return ApiResponse::success([
            'prova'  => array_merge($prova->toArray(), ['turmas' => $turmas]),
            'kpis'   => $this->kpis($prova),
            'alunos' => $this->tabelaAlunos($id),
        ]);
    }

    public function turmaProva(Request $request, int $turmaId, int $provaId): JsonResponse
    {
        $prova = $this->encontrarProva($request, $provaId);
        if (!$prova) {
            return ApiResponse::notFound('Prova não encontrada.');
        }

        $turma = DB::table('turmas')->where('id', $turmaId)->first();
        if (!$turma) {
            return ApiResponse::notFound('Turma não encontrada.');
        }

        return ApiResponse::success([
            'prova'  => $prova,
            'turma'  => $turma,
            'kpis'   => $this->kpis($prova, $turmaId),
            'alunos' => $this->tabelaAlunos($provaId, $turmaId),
        ]);
    }
}
