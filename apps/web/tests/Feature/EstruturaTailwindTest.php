<?php
namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\Support\{ContextoSelecionado,NavegacaoHttp};
use Tests\TestCase;

class EstruturaTailwindTest extends TestCase
{
    use ContextoSelecionado,NavegacaoHttp;

    private function preparar(string $perfil='coordenador'): void
    {
        config(['services.api.base_url'=>'http://api.test/api']);
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::preventStrayRequests();
        $this->withSession(['auth_token'=>'ficticio','auth_vinculos'=>true,'auth_nome'=>'Pessoa fictícia','auth_email'=>'pessoa@example.test']);
        $me=['vinculo_id'=>7,'tipo_contexto'=>'vinculo','token'=>'contexto-ficticio','perfil'=>$perfil,
            'permissoes'=>['turma.ver','turma.gerir','turma.desativar','aluno.ver','aluno.gerir','prova.ver','gabarito.ver','correcao.acompanhar','usuario.ver','relatorio.turma','relatorio.escola'],
            'espaco'=>['id'=>5,'tipo'=>$perfil==='professor_autonomo'?'pessoal':'escola','nome'=>'Colégio Estadual de Educação Básica Professora Maria das Dores de Almeida e Silva — Unidade Experimental de Ciências e Linguagens']];
        $this->selecionarContextoFixture($me);
        $turma=['id'=>14,'nome'=>'3º A','serie'=>'3ª série','turno'=>'tarde','ano_letivo'=>2026,'ativo'=>true,'total_alunos'=>3,'avatar_id'=>1,'escola_id'=>5,'escola_nome'=>$me['espaco']['nome'],
            'alunos'=>[['id'=>8,'nome'=>'Estudante fictício','matricula'=>'FICTICIA','ativo'=>true]],'provas'=>[]];
        Http::fake(function($r)use($me,$turma){
            $path=parse_url($r->url(),PHP_URL_PATH);
            if($path==='/api/v1/auth/me')return Http::response(['data'=>$me]);
            if($path==='/api/v1/auth/vinculos')return Http::response(['data'=>[
                ['id'=>7,'perfil'=>$me['perfil'],'espaco'=>$me['espaco']],
                ['id'=>8,'perfil'=>'professor','espaco'=>$me['espaco']],
            ]]);
            if($path==='/api/v1/turmas/14')return Http::response(['data'=>$turma]);
            if($path==='/api/v1/turmas')return Http::response(['data'=>['turmas'=>[$turma],'kpis'=>[]]]);
            if($path==='/api/v1/escolas')return Http::response(['data'=>[['id'=>5,'nome'=>$me['espaco']['nome']]]]);
            return Http::response(['data'=>[]]);
        });
    }

    public function test_estrutura_e_percurso_turmas_preservam_navegacao_e_protecoes(): void
    {
        $this->preparar();
        foreach(['/app/turmas'=>'turmas','/app/turmas/14'=>'turma','/app/turmas/14/editar'=>'editar'] as $url=>$arquivo){
            $r=$this->consultarTela($url)->assertOk()->assertSee('css/estrutura-app.css',false)
                ->assertSee('css/app-conteudo-legado.css',false)->assertDontSee('css/app.css?')
                ->assertSee('pessoa@example.test')->assertSee('Meu espaço')->assertSee('Menu principal (celular)');
            $dom=new \DOMDocument;@$dom->loadHTML('<?xml encoding="utf-8" ?>'.$r->getContent());$xp=new \DOMXPath($dom);
            $this->assertSame(1,$xp->query('//*[@id="user-menu-trigger"]')->length);
            $this->assertSame(1,$xp->query('//a[contains(@class,"marca-estrutura")]')->length);
            $this->assertSame(0,$xp->query('//aside//summary')->length);
            $this->assertSame(1,$xp->query('//dialog[@id="nav-folha"]')->length);
            $this->assertSame(1,$xp->query('//*[@id="user-menu"]//form[@method="POST"]//input[@name="_token"]')->length);
            $this->assertSame(1,$xp->query('//*[@id="user-menu"]//*[@id="contexto-trocar"]')->length);
            if($arquivo==='turmas'){
                $this->assertSame(1,$xp->query('//dialog[@id="filtros-turmas"]//form[@method="POST" and @data-navegacao-consulta]//input[@name="_token"]')->length);
                $this->assertSame(3,$xp->query('//dialog[@id="filtros-turmas"]//*[@name="busca" or @name="escola_id" or @name="ano_letivo"]')->length);
                $this->assertSame(0,$xp->query('//*[@name="busca" or @name="ano_letivo"][not(ancestor::dialog) and not(ancestor::*[@id="modal-criar"])]')->length);
            }
            if($arquivo==='editar')$this->assertSame(1,$xp->query('//form[@data-formulario-validado]//input[@name="_method" and @value="PUT"]')->length);
            if(getenv('G360_TW2_EXPORT')){
                $dir=sys_get_temp_dir().'/g360-tw2-render';if(!is_dir($dir))mkdir($dir,0700,true);
                file_put_contents($dir.'/'.$arquivo.'.html',$r->getContent());
            }
        }
        Http::assertNotSent(fn($r)=>$r->method()!=='GET');
    }

    public function test_espaco_pessoal_mantem_nomenclatura_sem_inventar_escola(): void
    {
        $this->preparar('professor_autonomo');
        $this->consultarTela('/app/turmas')->assertOk()->assertSee('Tipo de espaço')->assertSee('Pessoal')->assertDontSee('Minha escola');
    }
}
