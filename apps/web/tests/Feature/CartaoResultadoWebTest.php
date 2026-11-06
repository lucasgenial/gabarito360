<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Tests\Support\NavegacaoHttp;
use Tests\Support\AcessoAtivoLegado;

class CartaoResultadoWebTest extends TestCase
{
    use NavegacaoHttp;
    use AcessoAtivoLegado;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.api.base_url' => 'http://api.test/api']);
        Http::preventStrayRequests();
        $this->withSession(['auth_token' => 'ficticio', 'auth_perfil' => 'coordenador', 'auth_nome' => 'Pessoa fictícia', 'auth_vinculos' => false, 'auth_permissoes' => ['correcao.acompanhar', 'correcao.alterar']]);
        $this->selecionarAcessoLegado('coordenador', ['correcao.acompanhar', 'correcao.alterar', 'resultado.ver']);
    }

    private function resultado(?string $url): array
    {
        return ['aluno' => ['id' => 1, 'nome' => 'Aluno fictício', 'matricula' => 'TESTE-1', 'turma_nome' => 'Turma fictícia', 'turma_id' => 1],
            'prova' => ['id' => 4, 'titulo' => 'Prova fictícia', 'disciplina' => 'Teste', 'nota_maxima' => 10, 'num_alternativas' => 5],
            'nota' => ['nota_final' => 5, 'acertos' => 1, 'total_questoes' => 2], 'folha' => [['numero_questao' => 1, 'situacao' => 'correta', 'marcada' => 'A', 'gabarito' => 'A', 'ambigua' => false], ['numero_questao' => 2, 'situacao' => 'branco', 'marcada' => null, 'gabarito' => 'B', 'ambigua' => false]],
            'lingua' => null, 'cartao_id' => 65, 'cartao_foto_url' => $url, 'correcao' => ['fechada' => false], 'media_turma' => null];
    }

    public function test_resultado_oferece_painel_sob_demanda_e_modal_sem_nova_aba(): void
    {
        $this->fingirResultado();
        $this->consultarTela('/app/resultados/1/4?from=correcao')->assertOk()->assertSee('Cartão resposta')->assertSee('doc-painel')->assertSee('doc-modal')->assertSee('painel-cartao.js')->assertSee('Histórico de leituras do cartão-resposta')
            ->assertSee('Carregando leituras…')->assertDontSee('Retirar correção')->assertDontSee('Revisar leitura')->assertSee('Relatório')->assertSee('Caminho da página')->assertSee('aria-current="page"', false);
        Http::assertNotSent(fn ($r) => str_contains($r->url(), '/tentativas'));
    }

    public function test_resultado_sem_autorizacao_de_foto_nao_oferece_leitor(): void
    {
        $this->apiLegada(['*' => Http::response(['data' => $this->resultado(null)])]);
        $this->consultarTela('/app/resultados/1/4')->assertOk()->assertDontSee('doc-painel')->assertDontSee('Visualizar cartão-resposta');
    }

    public function test_foto_antiga_inadequada_mostra_aviso_sem_alterar_o_resultado(): void
    {
        $d = $this->resultado('/foto-autorizada.jpg');
        $d['qualidade_foto'] = ['aprovada' => false, 'mensagem' => 'Marcador incompleto.'];
        $this->apiLegada(['*' => Http::response(['data' => $d])]);
        $this->consultarTela('/app/resultados/1/4')->assertOk()->assertSee('Marcador incompleto.')->assertSee('As respostas e a nota já registradas foram preservadas.')->assertSee('5,0');
    }

    public function test_retirada_encaminha_motivo_versao_e_contexto_para_api(): void
    {
        $this->apiLegada(['*' => Http::response(['data' => ['excluido' => true]])]);
        $this->alterarRecursoJson('DELETE', '/app/cartoes/65', ['motivo' => 'Retirada documentada de teste', 'versao' => str_repeat('a', 64)])->assertOk();
        Http::assertSent(fn ($r) => $r->method() === 'DELETE' && $r['motivo'] === 'Retirada documentada de teste' && $r['versao'] === str_repeat('a', 64) && $r->hasHeader('Authorization', 'Bearer ficticio'));
    }

    private function fingirResultado(bool $falhar = false, ?array $detalhe = null): void
    {
        $d = $this->resultado('/foto-autorizada.jpg');
        $d['historico_permitido'] = true;
        $d['total_leituras'] = 3;
        $this->apiLegada(function ($r) use ($d, $falhar, $detalhe) {
            if (str_contains($r->url(), '/v1/provas/4/tentativas')) {
                return Http::response(['data' => ['tentativas' => [], 'total' => 3, 'pagina' => 2, 'ultima_pagina' => 3]], $falhar ? 503 : 200);
            }
            if (str_contains($r->url(), '/v1/tentativas/')) {
                return Http::response(['data' => $detalhe]);
            }

            return Http::response(['data' => $d]);
        });
    }

    public function test_historico_paginado_permanece_no_resultado_e_filtra_o_aluno(): void
    {
        $this->fingirResultado();
        $r = $this->consultarJson('/app/resultados/1/4/historico?tentativas_pagina=2')->assertOk()->assertJsonPath('total', 3);
        $this->assertStringContainsString('tabela-padrao', $r->json('html'));
        \Illuminate\Testing\TestResponse::fromBaseResponse(response($r->json('html')))->assertDestinoRecurso('resultados.historico',['alunoId'=>1,'provaId'=>4],['tentativas_pagina'=>1]);
        \Illuminate\Testing\TestResponse::fromBaseResponse(response($r->json('html')))->assertDestinoRecurso('resultados.historico',['alunoId'=>1,'provaId'=>4],['tentativas_pagina'=>3]);
        $this->assertStringNotContainsString('/historico?', $r->json('html'));
        Http::assertSent(fn ($r) => str_contains($r->url(), '/v1/provas/4/tentativas') && (int) $r['aluno_id'] === 1 && (int) $r['page'] === 2);
    }

    public function test_falha_no_historico_nao_vira_zero_nem_oculta_nota(): void
    {
        $this->fingirResultado(true);
        $this->consultarTela('/app/resultados/1/4')->assertOk()->assertSee('Carregando leituras…')->assertSee('5,0')->assertDontSee('0 leituras registradas');
        $this->consultarJson('/app/resultados/1/4/historico')->assertStatus(503);
    }

    public function test_detalhe_de_outro_aluno_nao_e_atribuido_ao_resultado_atual(): void
    {
        $this->fingirResultado(false, ['aluno_id' => 2, 'prova' => ['id' => 4]]);
        $this->consultarJson('/app/resultados/1/4/historico?tentativa=9d166f17-9d1e-44b7-9719-28452a5c156b')->assertNotFound();
    }

    public function test_uma_leitura_nao_exibe_historico_e_docente_mantem_itens_coloridos_sem_tabela(): void
    {
        $d=$this->resultado(null);$d['pedagogico']=true;$d['historico_permitido']=true;$d['total_leituras']=1;
        $d['folha'][0]['turma']=['acertos'=>2,'total'=>3,'percentual'=>66.7];
        $d['folha'][0]['geral']=['acertos'=>6,'total'=>8,'percentual'=>75];
        $this->apiLegada(['*'=>Http::response(['data'=>$d])]);
        $this->consultarTela('/app/resultados/1/4')->assertOk()->assertSee('res-q correta',false)->assertSee('66,7%')->assertSee('75,0%')
            ->assertDontSee('historico-tentativas')->assertDontSee('<table',false)->assertDontSee('resultado-historico.js');
        Http::assertNotSent(fn($r)=>str_contains($r->url(),'/tentativas'));
    }

    public function test_detalhe_historico_renderiza_leitura_e_mudanca_coloridas_sem_percentuais(): void
    {
        $id='9d166f17-9d1e-44b7-9719-28452a5c156b';
        $this->fingirResultado(false,['id'=>$id,'aluno_id'=>1,'aluno_nome'=>'Aluno fictício','prova'=>['id'=>4],
            'numero'=>1,'autor_nome'=>'Pessoa fictícia','ocorrido_em'=>'2026-11-05 12:00:00','estado'=>'substituida',
            'tem_imagem'=>false,'imagem_sha256'=>null,'leituras'=>[],'nota'=>null,'pode_aplicar'=>false,'pode_descartar'=>false,'eventos'=>[],
            'linhas'=>[['numero_questao'=>1,'leitura'=>'A','resposta'=>'B','resposta_registrada'=>true,
                'observacao'=>'Revisão humana por Pessoa fictícia em 05/11/2026 09:00:00 -03:00',
                'referencia_preservada'=>true,'situacao'=>'correta','leitura_situacao'=>'incorreta','gabarito'=>'B','modificada'=>true,'intervencao_humana'=>true]]]);
        $r=$this->consultarJson('/app/resultados/1/4/historico?tentativa='.$id)->assertOk();
        $this->assertStringContainsString('res-q correta',$r->json('html'));
        $this->assertStringContainsString('Resposta modificada',$r->json('html'));
        $this->assertStringContainsString('Leitura automática',$r->json('html'));
        $this->assertStringContainsString('Revisão humana por Pessoa fictícia',$r->json('html'));
        $this->assertStringNotContainsString('resultado-percentuais',$r->json('html'));
    }

    public function test_aplicar_tentativa_integrada_volta_ao_aluno_canonico_sem_redirecionamento_arbitrario(): void
    {
        $id = '9d166f17-9d1e-44b7-9719-28452a5c156b';
        $this->apiLegada(['*' => Http::response(['data' => ['aluno_id' => 1, 'prova' => ['id' => 4], 'aplicada' => true]])]);
        $this->alterarRecurso('POST', '/app/tentativas/'.$id.'/aplicar', ['integrada' => 1, 'tentativas_pagina' => 2, 'motivo' => 'Nova leitura conferida no teste', 'confirmar' => 1, 'retorno' => 'https://externo.test'])->assertRetornoTela(route('resultados.show', [1, 4, 'tentativa' => $id, 'tentativas_pagina' => 2]).'#tentativa-detalhe');
    }

    public function test_flash_tem_fallback_seguro_e_solid_toast_sem_conteudo_html_executavel(): void
    {
        $this->fingirResultado();
        $this->withSession(['sucesso' => 'Registro salvo <script>alert(1)</script>'])->consultarTela('/app/resultados/1/4')->assertOk()->assertSee('notificacoes.js')->assertSee('g360-notificacoes-iniciais')->assertSee('data-g360-flash="sucesso"', false)->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_aplicar_com_ambiguidades_abre_revisao_sem_tentar_abrir_resultado_ainda_inexistente(): void
    {
        $id = '9d166f17-9d1e-44b7-9719-28452a5c156b';
        $this->apiLegada(['*' => Http::response(['data' => ['aluno_id' => 1, 'prova' => ['id' => 4], 'aplicada' => true, 'status' => 'ambiguo', 'prova_id' => 4, 'cartao_id' => 66]])]);
        $this->alterarRecurso('POST', '/app/tentativas/'.$id.'/aplicar', ['integrada' => 1, 'motivo' => 'Foto nova conferida para teste', 'confirmar' => 1])->assertRetornoTela(route('correcao.revisao', [4, 'cartao' => 66]))->assertSessionHas('info', 'Nova tentativa aplicada. Revise as questões pendentes.');
    }

    public function test_conflito_integrado_volta_ao_resultado_canonico_e_preserva_motivo(): void
    {
        $id = '9d166f17-9d1e-44b7-9719-28452a5c156b';
        $this->apiLegada(fn ($r) => $r->method() === 'GET' ? Http::response(['data' => ['aluno_id' => 1, 'prova' => ['id' => 4]]]) : Http::response(['message' => 'Correção alterada.'], 409));
        $this->from('/app/resultados/1/4/historico')->alterarRecurso('POST', '/app/tentativas/'.$id.'/aplicar', ['integrada' => 1, 'motivo' => 'Motivo documentado de teste', 'confirmar' => 1])->assertRetornoTela(route('resultados.show', [1, 4, 'tentativa' => $id, 'tentativas_pagina' => 1]).'#tentativa-detalhe')->assertSessionHasErrors('tentativa')->assertSessionHasInput('motivo', 'Motivo documentado de teste');
    }
}
