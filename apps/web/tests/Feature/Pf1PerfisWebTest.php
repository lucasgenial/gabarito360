<?php
namespace Tests\Feature;
use App\Support\{Acesso,ContextoWeb};
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class Pf1PerfisWebTest extends TestCase
{
    private function contexto(string $perfil,array $permissoes): string
    {
        config(['services.api.base_url'=>'http://api.test/api']);Http::preventStrayRequests();
        $this->withSession(['auth_token'=>'identidade','auth_vinculos'=>true,'auth_nome'=>'Pessoa fictícia']);
        $this->withCookie(config('session.cookie'),session()->getId())->withCredentials();
        $dados=['vinculo_id'=>1,'tipo_contexto'=>'vinculo','token'=>'token-pf1','perfil'=>$perfil,'espaco'=>['id'=>1,'tipo'=>'escola','nome'=>'Escola fictícia'],'permissoes'=>$permissoes];
        ContextoWeb::criar($dados);$id=ContextoWeb::referencia($dados);
        $turma=['id'=>1,'nome'=>'Turma fictícia','serie'=>'4','turno'=>'tarde','ano_letivo'=>2026,'ativo'=>true,'escola_nome'=>'Escola fictícia','total_alunos'=>0,'alunos'=>[]];
        Http::fake(function($r)use($dados,$turma){
            $p=parse_url($r->url(),PHP_URL_PATH);
            if(preg_match('~/auth/(vinculos|acessos-internos)$~',$p))return Http::response(['data'=>[]]);
            $this->assertTrue($r->hasHeader('Authorization','Bearer token-pf1'));
            return match($p){
                '/api/v1/auth/me'=>Http::response(['data'=>$dados]),
                '/api/v1/escolas'=>Http::response(['data'=>['escolas'=>[['id'=>1,'nome'=>'Escola fictícia']]]]),
                '/api/v1/turmas/1'=>Http::response(['data'=>$turma]),
                '/api/v1/turmas'=>Http::response(['data'=>['turmas'=>[$turma],'kpis'=>['total_alunos'=>0]]]),
                '/api/v1/alunos'=>Http::response(['data'=>['alunos'=>[],'kpis'=>[]]]),
                '/api/v1/usuarios'=>Http::response(['data'=>['usuarios'=>[['id'=>2,'nome'=>'Auxiliar fictício','email'=>'aux@example.test','perfil'=>'auxiliar_secretaria','ativo'=>true,'situacao'=>'ativo','vinculo_id'=>2]],'totais'=>[]]]),
                default=>$this->fail('Consulta inesperada: '.$p),
            };
        });return $id;
    }
    public function test_auxiliar_tem_interface_administrativa_sem_acoes_proibidas():void
    {
        $id=$this->contexto('auxiliar_secretaria',['escola.ver','turma.ver','aluno.ver','aluno.gerir']);
        $this->get('/acessos/'.$id.'/painel')->assertOk()->assertSee('Secretaria escolar')->assertSee('Ver alunos')->assertSee('Auxiliar de secretaria');
        $this->get('/acessos/'.$id.'/turmas')->assertOk()->assertDontSee('Desempenho médio')->assertDontSee('Nova turma');
        $this->get('/acessos/'.$id.'/turmas/1')->assertOk()->assertDontSee('Salvar alterações')->assertDontSee('Desativar turma')->assertDontSee('Vincular professor');
        $this->get('/acessos/'.$id.'/alunos')->assertOk()->assertSee('Cadastrar aluno')->assertDontSee('Importar');
        foreach(['/provas','/membros','/relatorios/escola'] as $r)$this->get('/acessos/'.$id.$r)->assertForbidden();
    }
    public function test_vice_sem_concessao_nao_mostra_gestao_da_equipe():void
    {
        $id=$this->contexto('vice_dir_escolar',['usuario.ver']);
        $this->get('/acessos/'.$id.'/membros')->assertOk()->assertSee('Vice-diretor escolar')->assertSee('Auxiliar de secretaria')->assertSee('Auxiliar fictício')->assertDontSee('Suspender vínculo')->assertDontSee('Novo Membro')->assertDontSee('>Editar<',false);
        $this->assertSame([],Acesso::perfisGeridos());
    }
    public function test_vice_com_concessao_so_mostra_acoes_de_subordinados():void
    {
        $id=$this->contexto('vice_dir_escolar',['usuario.ver','usuario.gerir']);
        $this->get('/acessos/'.$id.'/membros')->assertOk()->assertSee('Suspender vínculo')->assertSee('Novo Membro')->assertSee('>Editar<',false);
        $this->assertNotContains('vice_dir_escolar',Acesso::perfisGeridos());
        $this->assertContains('auxiliar_secretaria',Acesso::perfisGeridos());
    }
}
