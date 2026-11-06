<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Prova;
use App\Support\Cabecalho;
use App\Support\Correcao;
use App\Support\Escopo;
use App\Support\Espacos;
use App\Support\ContextoCorrecao;
use App\Support\ContextoVinculo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;

class ResultadoController extends Controller
{
    private function modelo(Request $request): string
    {
        $d = $request->validate(['modelo' => 'nullable|in:aluno,professor']);
        $modelo = $d['modelo'] ?? 'aluno';
        if ($modelo === 'professor') abort_unless($this->pedagogico($request), 403);
        return $modelo;
    }

    private function pedagogico(Request $request): bool
    {
        return !in_array($request->user()->perfil, ['aluno', 'aplicador']) && \App\Support\Permissao::pode($request->user(), 'relatorio.prova');
    }
    public function show(Request $request, int $alunoId, int $provaId): JsonResponse
    {
        $dados = $this->montar($request, $alunoId, $provaId);

        if($dados instanceof JsonResponse)return $dados;
        $c=\App\Models\Cartao::find($dados['cartao_id']);$dados['qualidade_foto']=$c?->qualidade;
        $dados['historico_permitido']=$c && \App\Support\Permissao::pode($request->user(),'correcao.acompanhar')
            && ContextoCorrecao::cartoes($request->user())->whereKey($c->id)->exists();
        $dados['total_leituras'] = $dados['historico_permitido'] ? DB::table('cartao_tentativas')
            ->where('prova_id', $provaId)->where('aluno_id', $alunoId)->where('espaco_id', $c->espaco_id)->count() : null;
        $dados['historico_permitido'] = $dados['historico_permitido'] && $dados['total_leituras'] > 1;
        $dados['tentativa_numero']=$c?->tentativa_id?DB::table('cartao_tentativas')->where('id',$c->tentativa_id)->value('numero'):null;
        $dados['cartao_foto_url']=$c?->imagem_url&&\App\Support\Permissao::pode($request->user(),'correcao.acompanhar')
            &&ContextoCorrecao::cartoes($request->user())->whereKey($c->id)->exists()?app(\App\Services\Omr\ImagensCartao::class)->url($c->id,'original',$request->user()):null;
        if(!empty($dados['cartao_foto_url'])&&empty($dados['qualidade_foto'])&&$c?->imagem_url){
            try{$dados['qualidade_foto']=app(\App\Services\Omr\QualidadeCartao::class)->avaliar(\Illuminate\Support\Facades\Storage::disk('public')->path($c->imagem_url));}
            catch(\Throwable){$dados['qualidade_foto']=['aprovada'=>null,'codigo'=>'indisponivel','mensagem'=>'Não foi possível verificar a qualidade desta foto agora.'];}
        }
        return ApiResponse::success($dados);
    }

    /**
     * PDF do resultado (nota + questão por questão) para imprimir ou enviar ao aluno/responsáveis.
     * Exceção documentada à regra "API só JSON": é download de arquivo, não tela.
     */
    public function pdf(Request $request, int $alunoId, int $provaId)
    {
        $modelo = $this->modelo($request);
        $versao = ContextoCorrecao::versaoAcesso($request->user());
        $dados = $this->montar($request, $alunoId, $provaId);
        if ($dados instanceof JsonResponse) {
            return $dados;
        }

        $prova  = Prova::find($provaId);
        $emissao = app(\App\Services\EmissaoDocumento::class);
        $preparado = $emissao->preparar($request->user(), $modelo, $dados['cabecalho']['nome'] ?? '', $prova->espaco_id);
        $saida = tempnam(sys_get_temp_dir(), 'g360_e'.($prova->espaco_id ?? 0).'_res_');
        abort_unless($saida !== false, 503, 'Não foi possível preparar o PDF.');
        chmod($saida, 0600);
        $pdf = $this->dadosPdf($dados, $prova, $saida, (bool) \App\Support\EspacoPessoal::doAtor($request->user())) + ['modelo' => $modelo, 'emissao' => $preparado['pdf']];
        if ($modelo === 'aluno') { unset($pdf['media_turma']); foreach ($pdf['folha'] as &$q) { unset($q['turma'], $q['geral']); } unset($q); }
        try {
        $r = Process::path(config('omr.scripts'))
            ->timeout(max(60, config('omr.timeout')))
            ->input(json_encode($pdf, JSON_UNESCAPED_UNICODE))
            ->run([config('omr.python'), 'gerar_resultado.py']);

        $json = json_decode(trim($r->output()), true);
        if (!$r->successful() || !is_array($json) || empty($json['ok']) || !is_file($saida) || !filesize($saida)) {
            @unlink($saida);
            report(new \RuntimeException('Falha ao gerar PDF do resultado: ' . ($json['erro'] ?? trim($r->errorOutput()))));
            return ApiResponse::error('Não foi possível gerar o PDF do resultado.', [], 500);
        }
        // A geração pode demorar; não entregar nem registrar emissão após revogação.
        abort_unless(hash_equals($versao, ContextoCorrecao::versaoAcesso($request->user())) && \App\Support\Permissao::pode($request->user(), 'resultado.compartilhar'), 403);
        if ($modelo === 'professor') abort_unless($this->pedagogico($request), 403);
        $atual = $this->montar($request, $alunoId, $provaId);
        if ($atual instanceof JsonResponse) { @unlink($saida); return $atual; }
        $emissao->registrar($preparado, $saida);

        $nome = 'resultado-'.($modelo === 'professor' ? 'professor-' : '') . \Illuminate\Support\Str::slug($dados['aluno']['nome'] . ' ' . $dados['prova']['disciplina']) . '.pdf';

        return response()->download($saida, $nome, ['Content-Type' => 'application/pdf', 'Cache-Control' => 'private, no-store'])->deleteFileAfterSend();
        } catch (\Throwable $e) { @unlink($saida); throw $e; }
    }

    /** Um arquivo combinado: cada aluno pode ocupar mais de uma página. */
    public function lote(Request $request, int $provaId)
    {
        $modelo = $this->modelo($request);
        $request->validate(['turma_id' => 'nullable|integer|min:1']);
        abort_if(in_array($request->user()->perfil, ['aluno', 'aplicador']), 403);
        $versao = ContextoCorrecao::versaoAcesso($request->user());
        $prova = Escopo::provaComConteudo($request->user(), $provaId);
        abort_unless($prova, 404, 'Prova não encontrada.');
        $consulta = new \App\Support\ConsultasPedagogicas($request->user());
        $turma = $request->filled('turma_id') ? (int) $request->query('turma_id') : null;
        if ($turma) abort_unless(Escopo::turmas($request->user())->whereKey($turma)->exists(), 404, 'Turma não encontrada.');
        $ids = $consulta->tabela('notas')->where('prova_id', $provaId)
            ->whereIn('aluno_id', Escopo::alunos($request->user())->select('alunos.id'))
            ->when($turma, fn ($q) => $q->where('turma_id', $turma))
            ->orderBy('turma_id')->orderBy('aluno_id')->distinct()->limit(501)->pluck('aluno_id')->all();
        abort_unless(count($ids), 422, 'Nenhum resultado disponível para este lote.');
        abort_if(count($ids) > 500, 422, 'O limite é de 500 resultados por PDF. Selecione uma turma.');
        $emissao = app(\App\Services\EmissaoDocumento::class);
        $preparado = null;
        $resultados = [];
        foreach ($ids as $id) {
            $dados = $this->montar($request, (int) $id, $provaId);
            if ($dados instanceof JsonResponse) return $dados;
            $preparado ??= $emissao->preparar($request->user(), $modelo, $dados['cabecalho']['nome'] ?? '', $prova->espaco_id, count($ids));
            $pdf = $this->dadosPdf($dados, $prova, '', (bool) \App\Support\EspacoPessoal::doAtor($request->user())) + ['modelo' => $modelo, 'emissao' => $preparado['pdf']];
            if ($modelo === 'aluno') { unset($pdf['media_turma']); foreach ($pdf['folha'] as &$q) { unset($q['turma'], $q['geral']); } unset($q); }
            $resultados[] = $pdf;
        }
        usort($resultados, fn ($a, $b) => strnatcasecmp(($a['aluno']['turma'] ?? '').' '.$a['aluno']['nome'], ($b['aluno']['turma'] ?? '').' '.$b['aluno']['nome']));
        $saida = tempnam(sys_get_temp_dir(), 'g360_lote_');
        abort_unless($saida !== false, 500, 'Não foi possível preparar o PDF.');
        chmod($saida, 0600);
        try {
            $r = Process::path(config('omr.scripts'))->timeout(120)
                ->input(json_encode(['saida' => $saida, 'resultados' => $resultados], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR))
                ->run([config('omr.python'), 'gerar_resultado.py']);
            $json = json_decode(trim($r->output()), true);
            if (!$r->successful() || empty($json['ok']) || ($json['total'] ?? null) !== count($ids) || !filesize($saida)) {
                throw new \RuntimeException('Geração do lote de resultados não concluída.');
            }
            abort_unless(hash_equals($versao, ContextoCorrecao::versaoAcesso($request->user()))
                && \App\Support\Permissao::pode($request->user(), 'resultado.compartilhar')
                && \App\Support\Permissao::pode($request->user(), 'relatorio.prova'), 403);
            // Revalidar todos os alunos antes de liberar qualquer página do arquivo.
            foreach ($ids as $id) {
                $atual = $this->montar($request, (int) $id, $provaId);
                if ($atual instanceof JsonResponse) { @unlink($saida); return $atual; }
            }
            $emissao->registrar($preparado, $saida);
            return response()->download($saida, 'resultados-prova-'.$provaId.($turma ? '-turma-'.$turma : '').($modelo === 'professor' ? '-professor' : '').'.pdf', [
                'Content-Type' => 'application/pdf', 'Cache-Control' => 'private, no-store',
                'X-Content-Type-Options' => 'nosniff',
            ])->deleteFileAfterSend();
        } catch (\Throwable $e) { @unlink($saida); throw $e; }
    }

    private function dadosPdf(array $dados, Prova $prova, string $saida, bool $autonomo): array
    {
        return [
                'saida'       => $saida,
                'titulo'      => $dados['prova']['titulo'],
                'disciplina'  => $dados['prova']['disciplina'],
                'data'        => $prova->data_aplicacao?->format('d/m/Y'),
                'nota_maxima' => $dados['prova']['nota_maxima'],
                'aluno'       => ['nome' => $dados['aluno']['nome'], 'matricula' => $dados['aluno']['matricula'], 'turma' => $dados['aluno']['turma_nome']],
                'nota'        => $dados['nota'],
                'lingua'      => $dados['lingua'],
                'media_turma' => $dados['media_turma'],
                'folha'       => $dados['folha'],
            ] + Cabecalho::paraPdf($dados['cabecalho'], $autonomo);
    }

    /** Monta o resultado do aluno na prova, ou a resposta de erro. */
    private function montar(Request $request, int $alunoId, int $provaId): array|JsonResponse
    {
        $individual = ContextoVinculo::presente($request->user()) && $request->user()->perfil === 'aluno';
        $prova = $individual
            ? Prova::query()->where('espaco_id', ContextoVinculo::resolver($request->user())->espaco_id)->find($provaId)
            : Escopo::provaComConteudo($request->user(), $provaId);
        if ($individual && !ContextoCorrecao::provaValida($prova)) $prova = null;
        if (!$prova) {
            return ApiResponse::notFound('Prova não encontrada.');
        }

        $historica = Espacos::ativo() ? (new \App\Support\ConsultasPedagogicas($request->user()))->tabela('notas')->where('aluno_id',$alunoId)->where('prova_id',$provaId)->first() : null;
        $aluno = Escopo::alunos($request->user())->with('turma:id,nome,serie')->find($alunoId);
        if(!$aluno && $historica) $aluno=\App\Models\Aluno::where('espaco_id',$prova->espaco_id)->with('turma:id,nome,serie')->find($alunoId);
        if (!$aluno) {
            return ApiResponse::notFound('Aluno não encontrado.');
        }

        $pessoal = \App\Support\EspacoPessoal::doAtor($request->user());
        if (Espacos::ativo() && !$pessoal && !$historica && !ContextoCorrecao::alunos($prova, $individual ? null : $request->user())->where('alunos.id', $alunoId)->exists()) return ApiResponse::notFound('Resultado não encontrado.');
        $notas = $pessoal ? (new \App\Support\ConsultasPedagogicas($request->user()))->tabela('notas')
            : ContextoCorrecao::filtrar(DB::table('notas'), $prova->espaco_id);
        $nota = $notas->where('aluno_id', $alunoId)->where('prova_id', $provaId)->first();
        if (!$nota) {
            return ApiResponse::error('Resultado ainda não disponível para este aluno nesta prova.', [], 404);
        }

        $cartao = ContextoCorrecao::filtrar(DB::table('cartoes'), $prova->espaco_id)->where('id', $nota->cartao_id)->first();

        if (Espacos::ativo() && (!$cartao || (int) $cartao->aluno_id !== $alunoId || (int) $cartao->prova_id !== $provaId)) return ApiResponse::notFound('Resultado não encontrado.');
        $gabarito = ContextoCorrecao::filtrar(DB::table('gabaritos'), $prova->espaco_id)->where('prova_id', $provaId)->first();
        $questoesGabarito = $gabarito
            ? ContextoCorrecao::filtrar(DB::table('gabarito_questoes'), $prova->espaco_id)->where('gabarito_id', $gabarito->id)->orderBy('numero_questao')->get()->keyBy('numero_questao')
            : collect();

        $respostas = ContextoCorrecao::filtrar(DB::table('cartao_respostas'), $prova->espaco_id)
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

        $mediaTurma = $individual ? null : ContextoCorrecao::filtrar(DB::table('notas'), $prova->espaco_id)
            ->where('prova_id', $provaId)
            ->where('turma_id', $nota->turma_id)
            ->avg('nota_final');

        $turmaResultado = Escopo::turmas($request->user())->find($nota->turma_id);
        $pedagogico = $this->pedagogico($request);
        if ($pedagogico) {
            $chave = 'resultado_estatisticas_'.$provaId;
            $stats = $request->attributes->get($chave);
            if ($stats === null) {
                $stats = app(\App\Services\Omr\EstatisticasQuestoes::class)->calcular($request->user(), $prova);
                $request->attributes->set($chave, $stats);
            }
            foreach ($folha as &$q) {
                $s = $stats[$q['numero_questao']] ?? [];
                $q['geral'] = $s['geral'] ?? ['acertos' => 0, 'total' => 0, 'percentual' => null];
                $q['turma'] = $s['turmas'][$nota->turma_id] ?? ['acertos' => 0, 'total' => 0, 'percentual' => null];
            }
            unset($q);
        }
        $cabecalho = Cabecalho::contexto($request->user(), $prova->escola_id);
        return [
            'pedagogico' => $pedagogico,
            'aluno' => [
                'id'         => $aluno->id,
                'nome'       => $aluno->nome,
                'matricula'  => $aluno->matricula,
                'turma_id'   => $turmaResultado?->id,
                'turma_nome' => $turmaResultado?->nome,
            ],
            'prova' => [
                'id'               => $prova->id,
                'titulo'           => $prova->titulo,
                'disciplina'       => $prova->disciplina,
                'data_aplicacao'   => $prova->data_aplicacao,
                'num_questoes'     => $prova->num_questoes,
                'num_alternativas' => $prova->num_alternativas,
                'nota_maxima'      => $prova->nota_maxima,
                'escola_nome'      => $cabecalho['nome'],
            ],
            'nota' => [
                'acertos'          => $nota->acertos,
                'total_questoes'   => $nota->total_questoes,
                'nota_final'       => (float) $nota->nota_final,
                'status_aprovacao' => $nota->status_aprovacao,
            ],
            'cabecalho'       => $cabecalho,
            'correcao'        => Correcao::situacao($prova),
            'cartao_id'       => $nota->cartao_id,
            'lingua'          => $cartao?->lingua,
            'media_turma'     => $mediaTurma !== null ? round((float) $mediaTurma, 1) : null,
            'confianca_geral' => $cartao ? (float) $cartao->confianca_geral : null,
            'folha'           => $folha,
        ];
    }
}
