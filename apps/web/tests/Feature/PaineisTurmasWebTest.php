<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\Support\{ContextoSelecionado,NavegacaoHttp};
use Tests\TestCase;

class PaineisTurmasWebTest extends TestCase
{
    use ContextoSelecionado,NavegacaoHttp;

    private function preparar(string $perfil, mixed $total=3, int $status=200, ?array $permissoes=null, array $extra=[]): void
    {
        config(['services.api.base_url'=>'http://api.test/api']);
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::preventStrayRequests();
        $this->withSession(['auth_token'=>'ficticio','auth_vinculos'=>true,'auth_nome'=>'Pessoa fictícia']);
        $me=['vinculo_id'=>7,'tipo_contexto'=>'vinculo','token'=>'contexto-ficticio','perfil'=>$perfil,
            'permissoes'=>$permissoes??['turma.ver','aluno.ver','relatorio.turma'],'espaco'=>['id'=>5,'tipo'=>'escola','nome'=>'Escola fictícia']];
        $this->selecionarContextoFixture($me);
        Http::fake(function($r)use($me,$total,$status,$extra){
            $path=parse_url($r->url(),PHP_URL_PATH);
            if($path==='/api/v1/auth/me')return Http::response(['data'=>$me]);
            if($path==='/api/v1/turmas/14')return Http::response(['data'=>['id'=>14,'nome'=>'Turma fictícia','serie'=>'3ª série','turno'=>'tarde','ano_letivo'=>2026,'ativo'=>true,'total_alunos'=>3,
                'alunos'=>[['id'=>8,'nome'=>'Nome nominal fictício','matricula'=>'FICTICIA','ativo'=>true]],'provas'=>[['id'=>22,'titulo'=>'Prova fictícia','disciplina'=>'Matemática','data_aplicacao'=>null,'status'=>'corrigida']]]]);
            if($path==='/api/v1/turmas/14/painel')return Http::response(['data'=>$extra['painel']??[]]);
            if($path==='/api/v1/turmas')return Http::response(['data'=>['turmas'=>[['id'=>14,'nome'=>'Turma <fictícia>','serie'=>'3ª série','turno'=>'tarde','ano_letivo'=>2026,'ativo'=>true,'total_alunos'=>$total,'avatar_id'=>1,'media_turma'=>987.6]],'kpis'=>['total_alunos'=>999]]+$extra],$status);
            return Http::response(['data'=>[]]);
        });
    }

    public function test_colecao_preserva_alternativa_operacional_e_nao_publica_media_legada(): void
    {
        $this->preparar('professor');
        $r=$this->consultarTela('/app/turmas')->assertOk()->assertSee('Turma &lt;fictícia&gt;',false)
            ->assertSee('Cadastros de alunos ativos')->assertSee('data-apresentacao="tabela"',false)
            ->assertSee('data-ordenar="texto"',false)->assertSee('data-lista-modo="cards"',false)
            ->assertSee('data-widget-id="comparacao-turmas"',false)->assertDontSee('987,6');
        $dom=new \DOMDocument();@$dom->loadHTML($r->getContent());
        $cadastros=(new \DOMXPath($dom))->query('//*[@data-widget-id="kpi-cadastros"]//strong')->item(0);
        $this->assertNotNull($cadastros);
        $this->assertSame('3',trim($cadastros->textContent),'O KPI deve usar a coleção autorizada, não o total legado999.');
        $this->assertStringNotContainsString('Turma <fictícia>',$r->getContent());
        Http::assertNotSent(fn($r)=>str_contains($r->url(),'agenda')||str_contains($r->url(),'habilidades'));
    }

    public function test_secretaria_e_auxiliar_nao_recebem_modelos_pedagogicos_mesmo_com_extras(): void
    {
        foreach(['secretario_escolar','auxiliar_secretaria'] as $perfil){
            $this->preparar($perfil);
            $this->consultarTela('/app/turmas')->assertOk()->assertSee('data-widget-id="agenda-administrativa"',false)
                ->assertDontSee('data-widget-id="comparacao-turmas"',false)->assertDontSee('painel-turma-registro',false)
                ->assertDontSee('Desempenho médio')->assertDontSee('data-widget-id="intervencoes"',false);
        }
    }

    public function test_contagem_ausente_falha_e_zero_sao_estados_distintos(): void
    {
        $this->preparar('professor',null);
        $this->consultarTela('/app/turmas')->assertOk()->assertSee('Há totais indisponíveis neste recorte.')->assertSee('aria-label="Indisponível"',false);
        $this->preparar('professor',0);
        $this->consultarTela('/app/turmas')->assertOk()->assertSee('Cadastros de alunos ativos');
        $this->preparar('professor',0,503);
        $this->consultarTela('/app/turmas')->assertOk()->assertSee('Não foi possível carregar os registros.')->assertSee('role="alert"',false)->assertDontSee('painel-turma-registro',false);
    }

    public function test_permissao_retirada_nao_e_apresentada_como_zero_ou_lista_vazia(): void
    {
        $this->preparar('professor',3,200,['turma.ver']);
        $this->consultarTela('/app/turmas/14')->assertOk()->assertDontSee('Nome nominal fictício')
            ->assertDontSee('Nenhum aluno matriculado')->assertDontSee('Nenhuma prova aplicada')->assertDontSee('Média geral');
        $this->preparar('professor',3,200,['turma.ver','aluno.ver']);
        $this->consultarTela('/app/turmas/14')->assertOk()->assertSee('Nome nominal fictício')
            ->assertDontSee('Média geral')->assertDontSee('data-rotulo="Provas"',false);
    }
    public function test_relatorio_da_prova_tem_gate_distinto_da_media_consolidada(): void
    {
        $this->preparar('professor',3,200,['turma.ver','prova.ver','relatorio.turma']);
        $r=$this->consultarTela('/app/turmas/14')->assertOk()->assertSee('data-rotulo="Média"',false);
        $doc=new \DOMDocument();@$doc->loadHTML($r->getContent());
        $this->assertSame(0,(new \DOMXPath($doc))->query('//a[normalize-space(.)="Relatório"]')->length);
        $this->preparar('professor',3,200,['turma.ver','prova.ver','relatorio.prova']);
        $this->consultarTela('/app/turmas/14')->assertOk()->assertDontSee('data-rotulo="Média"',false)->assertDestinoRecurso('relatorios.turmaProva',['turmaId'=>14,'provaId'=>22]);
    }
    public function test_agregado_explicito_invalido_nao_cai_no_total_legado_nem_apaga_lista(): void
    {
        $agregado=['versao'=>1,'base'=>'colecao_completa_no_recorte','colecao_completa'=>true,
            'turmas'=>1,'em_uso'=>1,'arquivadas'=>0,'cadastros_ativos'=>3];
        $this->preparar('professor',extra:['resumo_cadastral'=>$agregado+['nome'=>'METADADO_PRIVADO_NAO_PROJETAR']]);
        $this->consultarTela('/app/turmas')->assertOk()->assertSee('Cadastros de alunos ativos')
            ->assertDontSee('Há totais indisponíveis')->assertDontSee('METADADO_PRIVADO_NAO_PROJETAR');
        foreach([null,array_replace($agregado,['cadastros_ativos'=>4]),array_replace($agregado,['colecao_completa'=>false])] as $valor){
            $this->preparar('professor',extra:['resumo_cadastral'=>$valor]);
            $r=$this->consultarTela('/app/turmas')->assertOk()->assertSee('Turma &lt;fictícia&gt;',false)
                ->assertSee('Há totais indisponíveis neste recorte.')->assertDontSee('Não foi possível carregar os registros.');
            $dom=new \DOMDocument();@$dom->loadHTML($r->getContent());
            $contador=(new \DOMXPath($dom))->query('//*[@data-widget-id="kpi-cadastros"]//strong')->item(0);
            $this->assertSame('—',trim($contador->textContent));
        }
    }

    public function test_resultados_agrupa_secoes_preserva_ids_e_controles_so_com_icones(): void
    {
        $this->preparar('coordenador',permissoes:['turma.ver','aluno.ver','resultado.ver','relatorio.turma','correcao.acompanhar'],
            extra:['painel'=>['kpis'=>['avaliacoes'=>0,'resultados'=>0,'alunos_com_resultado'=>0]]]);
        $r=$this->consultarTela('/app/turmas/14')->assertOk()->assertSee('Resultados da turma')
            ->assertSee('Acompanhamento da turma')->assertDontSee('Acompanhar correção');
        $dom=new \DOMDocument();@$dom->loadHTML($r->getContent());$xp=new \DOMXPath($dom);
        foreach(['estudantes','analises'] as $secao){
            $this->assertSame(1,$xp->query('//*[@aria-labelledby="titulo-resultados-turma"]//*[@data-painel-secao="'.$secao.'"]')->length);
            $this->assertSame(1,$xp->query('//*[@data-painel-secao="'.$secao.'"]/h3')->length);
        }
        $this->assertSame(1,$xp->query('//*[@data-widget-id="distribuicao-acertos"]//h4[@data-widget-titulo]')->length);
        $this->assertSame(0,$xp->query('//*[@aria-labelledby="titulo-resultados-turma"]//*[@data-painel-secao="resumo"]')->length);
        $this->assertSame(0,$xp->query('//button[@data-widget-altura-livre]')->length);
        $botoes=$xp->query('//button[@data-grafico-orientacao]');$this->assertSame(5,$botoes->length);
        foreach($botoes as $b){$this->assertSame('',trim($b->textContent));$this->assertNotSame('',$b->getAttribute('aria-label'));$this->assertNotSame('',$b->getAttribute('title'));}
    }

}
