<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Aluno;
use App\Models\Prova;
use App\Support\Cabecalho;
use App\Support\Escopo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;

class ResultadoController extends Controller
{
    public function show(Request $request, int $alunoId, int $provaId): JsonResponse
    {
        $dados = $this->montar($request, $alunoId, $provaId);

        return $dados instanceof JsonResponse ? $dados : ApiResponse::success($dados);
    }

    /**
     * PDF do resultado (nota + questão por questão) para imprimir ou enviar ao aluno/responsáveis.
     * Exceção documentada à regra "API só JSON": é download de arquivo, não tela.
     */
    public function pdf(Request $request, int $alunoId, int $provaId)
    {
        $dados = $this->montar($request, $alunoId, $provaId);
        if ($dados instanceof JsonResponse) {
            return $dados;
        }

        $prova  = Prova::find($provaId);
        $escola = DB::table('escolas')->where('id', $prova->escola_id)->first();
        $saida  = tempnam(sys_get_temp_dir(), 'g360_res_');
        @unlink($saida);
        $saida .= '.pdf';

        $r = Process::path(config('omr.scripts'))
            ->timeout(max(60, config('omr.timeout')))
            ->input(json_encode([
                'saida'       => $saida,
                'rede'        => $escola ? DB::table('nucleos')->join('redes', 'nucleos.rede_id', '=', 'redes.id')
                    ->where('nucleos.id', $escola->nucleo_id)->value('redes.nome') : null,
                'escola'      => $escola->nome ?? '',
                'titulo'      => $dados['prova']['titulo'],
                'disciplina'  => $dados['prova']['disciplina'],
                'data'        => $prova->data_aplicacao?->format('d/m/Y'),
                'nota_maxima' => $dados['prova']['nota_maxima'],
                'aluno'       => ['nome' => $dados['aluno']['nome'], 'matricula' => $dados['aluno']['matricula'], 'turma' => $dados['aluno']['turma_nome']],
                'nota'        => $dados['nota'],
                'lingua'      => $dados['lingua'],
                'media_turma' => $dados['media_turma'],
                'folha'       => $dados['folha'],
                'logo'        => Cabecalho::logoPath($escola->logo_path ?? null),
                'detalhe'     => $dados['cabecalho']['detalhe'] ?? null,
            ], JSON_UNESCAPED_UNICODE))
            ->run([config('omr.python'), 'gerar_resultado.py']);

        $json = json_decode(trim($r->output()), true);
        if (!is_array($json) || empty($json['ok']) || !is_file($saida)) {
            report(new \RuntimeException('Falha ao gerar PDF do resultado: ' . ($json['erro'] ?? trim($r->errorOutput()))));
            return ApiResponse::error('Não foi possível gerar o PDF do resultado.', [], 500);
        }

        $nome = 'resultado-' . \Illuminate\Support\Str::slug($dados['aluno']['nome'] . ' ' . $dados['prova']['disciplina']) . '.pdf';

        return response()->download($saida, $nome, ['Content-Type' => 'application/pdf'])->deleteFileAfterSend();
    }

    /** Monta o resultado do aluno na prova, ou a resposta de erro. */
    private function montar(Request $request, int $alunoId, int $provaId): array|JsonResponse
    {
        $prova = Escopo::provaComConteudo($request->user(), $provaId);
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
            // Mesmo critério da CalculadoraNota: aluno de Espanhol usa o gabarito alternativo
            $esperada = ($prova->opcao_lingua && $cartao?->lingua === 'ESP' && !empty($questao->alternativa_espanhol))
                ? $questao->alternativa_espanhol
                : $questao->alternativa;

            if ($questao->anulada) {
                $situacao = 'anulada';
            } elseif ($marcada === null) {
                $situacao = 'branco';
            } elseif ($marcada === $esperada) {
                $situacao = 'correta';
            } else {
                $situacao = 'incorreta';
            }

            $folha[] = [
                'numero_questao' => $numero,
                'gabarito'       => $esperada,
                'marcada'        => $marcada,
                'ambigua'        => (bool) ($resposta?->ambigua ?? false),
                'situacao'       => $situacao,
            ];
        }

        $mediaTurma = DB::table('notas')
            ->where('prova_id', $provaId)
            ->where('turma_id', $nota->turma_id)
            ->avg('nota_final');

        return [
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
                'escola_nome'      => DB::table('escolas')->where('id', $prova->escola_id)->value('nome'),
            ],
            'nota' => [
                'acertos'          => $nota->acertos,
                'total_questoes'   => $nota->total_questoes,
                'nota_final'       => (float) $nota->nota_final,
                'status_aprovacao' => $nota->status_aprovacao,
            ],
            'cabecalho'       => Cabecalho::escola((int) $prova->escola_id),
            'cartao_id'       => $nota->cartao_id,
            'lingua'          => $cartao?->lingua,
            'media_turma'     => $mediaTurma !== null ? round((float) $mediaTurma, 1) : null,
            'confianca_geral' => $cartao ? (float) $cartao->confianca_geral : null,
            'folha'           => $folha,
        ];
    }
}
