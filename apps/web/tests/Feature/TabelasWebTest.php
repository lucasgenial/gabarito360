<?php
namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Tests\Support\AcessoAtivoLegado;

class TabelasWebTest extends TestCase
{
    use AcessoAtivoLegado;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.api.base_url'=>'http://api.test/api']);
        Http::preventStrayRequests();
        $this->withSession(['auth_token'=>'ficticio','auth_perfil'=>'admin_rede','auth_nome'=>'Teste']);
        $this->selecionarAcessoLegado('admin_rede', ['aluno.ver', 'turma.ver', 'prova.ver', 'usuario.ver']);
    }

    public function test_falha_na_api_nao_e_apresentada_como_lista_vazia(): void
    {
        $this->apiLegada(['*'=>Http::response(['message'=>'Indisponível'],503)]);
        foreach (['/app/alunos','/app/turmas','/app/provas','/app/membros'] as $url) {
            $this->get($url)->assertOk()->assertSee('Não foi possível carregar')
                ->assertDontSee('Nenhum aluno encontrado')->assertDontSee('Nenhuma turma encontrada')
                ->assertDontSee('Nenhuma prova encontrada')->assertDontSee('Nenhum membro encontrado')
                ->assertDontSee('data-colecao-completa="true"',false);
        }
    }

    public function test_filtro_sem_resultado_nao_sugere_cadastrar_primeiro_registro(): void
    {
        $this->apiLegada(['*'=>Http::response(['data'=>['alunos'=>[],'turmas'=>[],'provas'=>[],
            'usuarios'=>[],'totais'=>[],'kpis'=>[]]])]);
        foreach (['/app/alunos?busca=termo-sem-resultado','/app/turmas?busca=termo-sem-resultado',
            '/app/provas?busca=termo-sem-resultado','/app/membros?busca=termo-sem-resultado',
            '/app/alunos?ativo=0','/app/turmas?ativo=0'] as $url) {
            $this->get($url)->assertOk()->assertSee('Limpar')
                ->assertSee('Ajuste ou limpe os filtros')
                ->assertDontSee('Cadastre o primeiro aluno')->assertDontSee('Crie a primeira prova')
                ->assertDontSee('Crie a primeira turma');
        }
    }

    public function test_todos_alunos_e_acoes_estao_no_html_sem_javascript_e_escapados(): void
    {
        $alunos=[];
        for ($i=1;$i<=25;$i++) $alunos[]=['id'=>$i,'nome'=>'Aluno <teste> '.$i,'matricula'=>'F'.$i,'ativo'=>true,'turma_nome'=>'Turma A','turma_serie'=>'1º'];
        $this->apiLegada(['*/v1/alunos*'=>Http::response(['data'=>['alunos'=>$alunos,'kpis'=>['total_alunos'=>25]]]),
            '*/v1/turmas*'=>Http::response(['data'=>['turmas'=>[]]]),
            '*/v1/auth/me'=>Http::response(['data'=>['perfil'=>'admin_rede','titular'=>false]])]);
        $html=$this->get('/app/alunos?busca=Aluno')->assertOk()->assertSee('Aluno &lt;teste&gt; 25',false)->getContent();
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
        $this->selecionarAcessoLegado('admin_rede', ['usuario.ver', 'usuario.gerir']);
        $membros=[];
        foreach (['ativo','suspenso','pendente'] as $i=>$situacao) $membros[]=['id'=>$i+1,'vinculo_id'=>$i+10,'nome'=>'Pessoa '.$i,'email'=>'teste'.$i.'@example.test','perfil'=>'professor','ativo'=>true,'situacao'=>$situacao];
        $this->apiLegada(['*'=>Http::response(['data'=>['usuarios'=>$membros,'totais'=>['professor'=>3]]])]);
        $html=$this->get('/app/membros')->assertOk()->assertSee('Suspender vínculo')->assertSee('Reativar vínculo')->getContent();
        $doc=new \DOMDocument(); @$doc->loadHTML('<?xml encoding="utf-8" ?>'.$html);$xp=new \DOMXPath($doc);
        $this->assertSame(3,$xp->query('//td[@data-rotulo="Ações"]//a[contains(@href,"/editar")]')->length);
        $this->assertSame(3,$xp->query('//td[@data-rotulo="Ações"]//a[contains(@href,"/permissoes")]')->length);
        $forms=$xp->query('//td[@data-rotulo="Ações"]//form[@method="POST"]');
        $this->assertSame(4,$forms->length);
        $this->assertSame(2,$xp->query('//td[@data-rotulo="Ações"]//form[contains(@action,"/encerrar-vinculo")]')->length);
        foreach ($forms as $form) {
            $this->assertSame(1,$xp->query('.//input[@name="_token"]',$form)->length);
            $this->assertStringContainsString('vinculo_id=',$form->getAttribute('action'));
        }
    }

    public function test_selecao_integral_preserva_inputs_sem_js_e_nao_ativa_controles_em_colecao_parcial(): void
    {
        $html=\Illuminate\Support\Facades\Blade::render('<x-tabela titulo="Disciplinas" modo="selecao" :completa="true"><table class="table tabela-padrao"><thead><tr><th data-ordenar="texto">Disciplina</th><th>Selecionar</th></tr></thead><tbody>@for($i=1;$i<=25;$i++)<tr><td>Disciplina {{ $i }}</td><td><input type="checkbox" name="disciplina_ids[]" value="{{ $i }}" checked></td></tr>@endfor</tbody></table></x-tabela>');
        $this->assertStringContainsString('data-modo="selecao"',$html);
        $this->assertStringContainsString('data-colecao-completa="true"',$html);
        $this->assertStringContainsString('Somente selecionadas',$html);
        $this->assertStringContainsString('Limpar filtros',$html);
        $doc=new \DOMDocument; @$doc->loadHTML($html); $xp=new \DOMXPath($doc);
        $this->assertSame(25,$xp->query('//input[@name="disciplina_ids[]"][@checked]')->length);
        $this->assertSame(0,$xp->query('//table/tbody/tr[@hidden]')->length);
        $parcial=\Illuminate\Support\Facades\Blade::render('<x-tabela titulo="Parcial" modo="selecao"><table><tbody><tr><td>Dados da página</td></tr></tbody></table></x-tabela>');
        $this->assertStringContainsString('data-colecao-completa="false"',$parcial);
        $this->assertStringNotContainsString('data-tabela-busca',$parcial);
        $matriz=\Illuminate\Support\Facades\Blade::render('<x-tabela titulo="Matriz" modo="matriz" :completa="true"><table><tbody><tr><td>Quadro</td></tr></tbody></table></x-tabela>');
        $this->assertStringContainsString('data-colecao-completa="false"',$matriz);
        $this->assertStringNotContainsString('data-tabela-busca',$matriz);
    }
}
