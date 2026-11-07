<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\Support\{ContextoSelecionado,NavegacaoHttp};
use Tests\TestCase;

class PaineisTurmasWebTest extends TestCase
{
    use ContextoSelecionado,NavegacaoHttp;

    private function preparar(string $perfil, mixed $total=3, int $status=200, ?array $permissoes=null): void
    {
        config(['services.api.base_url'=>'http://api.test/api']);
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::preventStrayRequests();
        $this->withSession(['auth_token'=>'ficticio','auth_vinculos'=>true,'auth_nome'=>'Pessoa fictícia']);
        $me=['vinculo_id'=>7,'tipo_contexto'=>'vinculo','token'=>'contexto-ficticio','perfil'=>$perfil,
            'permissoes'=>$permissoes??['turma.ver','aluno.ver','relatorio.turma'],'espaco'=>['id'=>5,'tipo'=>'escola','nome'=>'Escola fictícia']];
        $this->selecionarContextoFixture($me);
        Http::fake(function($r)use($me,$total,$status){
            $path=parse_url($r->url(),PHP_URL_PATH);
            if($path==='/api/v1/auth/me')return Http::response(['data'=>$me]);
            if($path==='/api/v1/turmas/14')return Http::response(['data'=>['id'=>14,'nome'=>'Turma fictícia','serie'=>'3ª série','turno'=>'tarde','ano_letivo'=>2026,'ativo'=>true,'total_alunos'=>3,
                'alunos'=>[['id'=>8,'nome'=>'Nome nominal fictício','matricula'=>'FICTICIA','ativo'=>true]],'provas'=>[['id'=>22,'titulo'=>'Prova fictícia','disciplina'=>'Matemática','data_aplicacao'=>null,'status'=>'corrigida']]]]);
            if($path==='/api/v1/turmas')return Http::response(['data'=>['turmas'=>[['id'=>14,'nome'=>'Turma <fictícia>','serie'=>'3ª série','turno'=>'tarde','ano_letivo'=>2026,'ativo'=>true,'total_alunos'=>$total,'avatar_id'=>1,'media_turma'=>987.6]],'kpis'=>['total_alunos'=>999]]],$status);
            return Http::response(['data'=>[]]);
        });
    }

    public function test_colecao_preserva_alternativa_operacional_e_nao_publica_media_legada(): void
    {
        $this->preparar('professor');
        $r=$this->consultarTela('/app/turmas')->assertOk()->assertSee('Turma &lt;fictícia&gt;',false)
            ->assertSee('cadastros ativos nas turmas deste recorte')->assertSee('Tabela com ordenação e paginação')
            ->assertSee('data-ordenar="texto"',false)->assertSee('painel-turma-registro',false)
            ->assertSee('data-painel-modelo="comparacao-turmas"',false)->assertDontSee('987,6')->assertDontSee('999');
        $this->assertStringNotContainsString('Turma <fictícia>',$r->getContent());
        Http::assertNotSent(fn($r)=>str_contains($r->url(),'agenda')||str_contains($r->url(),'habilidades'));
    }

    public function test_secretaria_e_auxiliar_nao_recebem_modelos_pedagogicos_mesmo_com_extras(): void
    {
        foreach(['secretario_escolar','auxiliar_secretaria'] as $perfil){
            $this->preparar($perfil);
            $this->consultarTela('/app/turmas')->assertOk()->assertSee('data-painel-modelo="agenda-administrativa"',false)
                ->assertDontSee('data-painel-modelo="comparacao-turmas"',false)->assertDontSee('painel-turma-registro',false)
                ->assertDontSee('Desempenho médio')->assertDontSee('data-painel-modelo="intervencoes"',false);
        }
    }

    public function test_contagem_ausente_falha_e_zero_sao_estados_distintos(): void
    {
        $this->preparar('professor',null);
        $this->consultarTela('/app/turmas')->assertOk()->assertSee('Contagem de cadastros indisponível')->assertSee('Não informado');
        $this->preparar('professor',0);
        $this->consultarTela('/app/turmas')->assertOk()->assertDontSee('Contagem de cadastros indisponível')->assertSee('cadastros ativos nas turmas deste recorte');
        $this->preparar('professor',0,503);
        $this->consultarTela('/app/turmas')->assertOk()->assertSee('Listagem temporariamente indisponível')->assertDontSee('painel-turma-registro',false);
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
}
