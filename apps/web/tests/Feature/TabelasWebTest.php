<?php
namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TabelasWebTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['services.api.base_url'=>'http://api.test/api']);
        Http::preventStrayRequests();
        $this->withSession(['auth_token'=>'ficticio','auth_perfil'=>'admin_rede','auth_nome'=>'Teste']);
    }

    public function test_falha_na_api_nao_e_apresentada_como_lista_vazia(): void
    {
        Http::fake(['*'=>Http::response(['message'=>'Indisponível'],503)]);
        foreach (['/alunos','/turmas','/provas','/membros'] as $url) {
            $this->get($url)->assertOk()->assertSee('Não foi possível carregar')
                ->assertDontSee('Nenhum aluno encontrado')->assertDontSee('Nenhuma turma encontrada')
                ->assertDontSee('Nenhuma prova encontrada')->assertDontSee('Nenhum membro encontrado')
                ->assertDontSee('data-colecao-completa="true"',false);
        }
    }

    public function test_todos_alunos_e_acoes_estao_no_html_sem_javascript_e_escapados(): void
    {
        $alunos=[];
        for ($i=1;$i<=25;$i++) $alunos[]=['id'=>$i,'nome'=>'Aluno <teste> '.$i,'matricula'=>'F'.$i,'ativo'=>true,'turma_nome'=>'Turma A','turma_serie'=>'1º'];
        Http::fake(['*/v1/alunos*'=>Http::response(['data'=>['alunos'=>$alunos,'kpis'=>['total_alunos'=>25]]]),
            '*/v1/turmas*'=>Http::response(['data'=>['turmas'=>[]]]),
            '*/v1/auth/me'=>Http::response(['data'=>['perfil'=>'admin_rede','titular'=>false]])]);
        $html=$this->get('/alunos?busca=Aluno')->assertOk()->assertSee('Aluno &lt;teste&gt; 25',false)->getContent();
        $doc=new \DOMDocument(); @$doc->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        $xp=new \DOMXPath($doc);
        $this->assertSame(25,$xp->query('//table[contains(@class,"tabela-padrao")]/tbody/tr')->length);
        $this->assertSame(25,$xp->query('//td[@data-rotulo="Ações"]//a')->length);
        $this->assertSame(0,$xp->query('//table/tbody/tr[@hidden]')->length);
        $this->assertSame(25,$xp->query('//td[@data-rotulo="Aluno" and contains(@class,"tabela-principal")]')->length);
        $this->assertSame(1,$xp->query('//a[contains(@href,"/alunos/25")]')->length);
        Http::assertSent(fn($r)=>str_contains($r->url(),'/v1/alunos') && $r['busca']==='Aluno');
    }

    public function test_acoes_post_de_equipe_mantem_alvo_csrf_e_estados(): void
    {
        $membros=[];
        foreach (['ativo','suspenso','pendente'] as $i=>$situacao) $membros[]=['id'=>$i+1,'vinculo_id'=>$i+10,'nome'=>'Pessoa '.$i,'email'=>'teste'.$i.'@example.test','perfil'=>'professor','ativo'=>true,'situacao'=>$situacao];
        Http::fake(['*'=>Http::response(['data'=>['usuarios'=>$membros,'totais'=>['professor'=>3]]])]);
        $html=$this->get('/membros')->assertOk()->assertSee('Suspender vínculo')->assertSee('Reativar vínculo')->getContent();
        $doc=new \DOMDocument(); @$doc->loadHTML('<?xml encoding="utf-8" ?>'.$html);$xp=new \DOMXPath($doc);
        $this->assertSame(3,$xp->query('//td[@data-rotulo="Ações"]//a[contains(@href,"/editar")]')->length);
        $this->assertSame(3,$xp->query('//td[@data-rotulo="Ações"]//a[contains(@href,"/permissoes")]')->length);
        $forms=$xp->query('//td[@data-rotulo="Ações"]//form[@method="POST"]');
        $this->assertSame(2,$forms->length);
        foreach ($forms as $form) {
            $this->assertSame(1,$xp->query('.//input[@name="_token"]',$form)->length);
            $this->assertStringContainsString('vinculo_id=',$form->getAttribute('action'));
        }
    }
}
