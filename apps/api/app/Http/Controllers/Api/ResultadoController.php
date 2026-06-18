<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Aluno;
use App\Models\Prova;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ResultadoController extends Controller
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

    public function show(Request $request, int $alunoId, int $provaId): JsonResponse
    {
        $escolaIds = $this->escopoEscolaIds($request);
        $provaQuery = Prova::query();
        if ($escolaIds !== null) {
            $provaQuery->whereIn('escola_id', $escolaIds);
        }
        $prova = $provaQuery->find($provaId);
        if (!$prova) {
            return ApiResponse::notFound('Prova não encontrada.');
        }

        $aluno = Aluno::with('turma:id,nome,serie')->find($alunoId);
        if (!$aluno) {
            return ApiResponse::notFound('Aluno não encontrado.');
        }

        $nota = DB::table('notas')->where('aluno_id', $alunoId)->where('prova_id', $provaId)->first();
        if (!$nota) {
            return ApiResponse::error('Resultado ainda não disponível para este aluno nesta prova.', [], 404);
        }

        $cartao = DB::table('cartoes')->where('id', $nota->cartao_id)->first();

        $gabarito = DB::table('gabaritos')->where('prova_id', $provaId)->first();
        $questoesGabarito = $gabarito
            ? DB::table('gabarito_questoes')->where('gabarito_id', $gabarito->id)->orderBy('numero_questao')->get()->keyBy('numero_questao')
            : collect();

        $respostas = DB::table('cartao_respostas')
            ->where('cartao_id', $nota->cartao_id)
            ->get()
            ->keyBy('numero_questao');

        $folha = [];
        foreach ($questoesGabarito as $numero => $questao) {
            $resposta = $respostas->get($numero);
            $marcada  = $resposta?->alternativa;

            if ($questao->anulada) {
                $situacao = 'anulada';
            } elseif ($marcada === null) {
                $situacao = 'branco';
            } elseif ($marcada === $questao->alternativa) {
                $situacao = 'correta';
            } else {
                $situacao = 'incorreta';
            }

            $folha[] = [
                'numero_questao' => $numero,
                'gabarito'       => $questao->alternativa,
                'marcada'        => $marcada,
                'situacao'       => $situacao,
            ];
        }

        $mediaTurma = DB::table('notas')
            ->where('prova_id', $provaId)
            ->where('turma_id', $nota->turma_id)
            ->avg('nota_final');

        return ApiResponse::success([
            'aluno' => [
                'id'         => $aluno->id,
                'nome'       => $aluno->nome,
                'matricula'  => $aluno->matricula,
                'turma_id'   => $aluno->turma?->id,
                'turma_nome' => $aluno->turma?->nome,
            ],
            'prova' => [
                'id'               => $prova->id,
                'titulo'           => $prova->titulo,
                'disciplina'       => $prova->disciplina,
                'data_aplicacao'   => $prova->data_aplicacao,
                'num_questoes'     => $prova->num_questoes,
                'num_alternativas' => $prova->num_alternativas,
                'nota_maxima'      => $prova->nota_maxima,
            ],
            'nota' => [
                'acertos'          => $nota->acertos,
                'total_questoes'   => $nota->total_questoes,
                'nota_final'       => (float) $nota->nota_final,
                'status_aprovacao' => $nota->status_aprovacao,
            ],
            'cartao_id'       => $nota->cartao_id,
            'media_turma'     => $mediaTurma !== null ? round((float) $mediaTurma, 1) : null,
            'confianca_geral' => $cartao ? (float) $cartao->confianca_geral : null,
            'folha'           => $folha,
        ]);
    }
}
