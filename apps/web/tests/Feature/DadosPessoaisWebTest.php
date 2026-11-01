<?php
namespace Tests\Feature;

use App\Support\ContextoWeb;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DadosPessoaisWebTest extends TestCase
{
    private string $ctx;
    protected function setUp(): void
    {
        parent::setUp();
        config(['services.api.base_url'=>'http://api.test/api']);
        Http::preventStrayRequests();
        $this->withSession(['auth_token'=>'identidade','auth_vinculos'=>true,'auth_nome'=>'Professor','auth_email'=>'teste@example.test']);
        $this->withCookie(config('session.cookie'),session()->getId())->withCredentials();
        $this->ctx=ContextoWeb::criar(['token'=>'pessoal','perfil'=>'professor_autonomo','espaco'=>['tipo'=>'pessoal','nome'=>'Meu espaço'],
            'permissoes'=>['turma.ver','turma.gerir','aluno.ver','aluno.gerir','prova.ver','prova.criar','gabarito.ver']]);
    }

    private function fake(): void
    {
        Http::fake(function ($r) {
            $path=parse_url($r->url(),PHP_URL_PATH);
            $this->assertFalse(str_contains($path,'/escolas') || str_contains($path,'/usuarios') || str_contains($path,'/professores'));
            $turma=['id'=>1,'nome'=>'3A','serie'=>'3','turno'=>'tarde','ano_letivo'=>2026,'escola_id'=>null,'ativo'=>true,'alunos'=>[],'provas'=>[], 'media_turma'=>null, 'total_alunos'=>0, 'total_provas'=>0];
            $dados=match($path) {
                '/api/v1/auth/me'=>['perfil'=>'professor_autonomo','permissoes'=>['turma.ver','turma.gerir','aluno.ver','aluno.gerir','prova.ver','prova.criar','gabarito.ver']],
                '/api/v1/turmas'=>['id'=>1,'turmas'=>[$turma],'kpis'=>[]],
                '/api/v1/turmas/1'=>$turma,
                '/api/v1/alunos/1'=>['id'=>1,'nome'=>'Aluno fictício','matricula'=>'A-1','turma_id'=>1,'ativo'=>true],
                default=>['id'=>1],
            };
            return Http::response(['data'=>$dados],$r->method()==='POST'?201:200);
        });
    }

    public function test_formularios_minimos_sem_campos_ou_consultas_institucionais(): void
    {
        $this->fake();
        foreach (['/alunos/novo','/alunos/1'] as $path) {
            $r=$this->get($path.'?_ctx='.$this->ctx)->assertOk();
            if ($dir=getenv('T3B_PREVIEW_DIR')) file_put_contents($dir.'/'.str_replace('/','-',trim($path,'/')).'.html',$r->getContent());
            foreach (['cpf','data_nascimento','genero','nome_responsavel'] as $campo) $r->assertDontSee('name="'.$campo.'"',false);
            $r->assertSee('name="turma_id"',false)->assertSee('name="avatar_id"',false);
        }
        foreach (['/turmas','/turmas/1','/provas/nova'] as $path) {
            $r=$this->get($path.'?_ctx='.$this->ctx)->assertOk()->assertDontSee('name="escola_id"',false);
            if ($path==='/turmas/1') $r->assertDontSee('Vincular professor')->assertDontSee('id="professor-selecionado"',false);
            if ($dir=getenv('T3B_PREVIEW_DIR')) file_put_contents($dir.'/'.str_replace('/','-',trim($path,'/')).'.html',$r->getContent());
        }
    }

    public function test_envio_minimo_e_rejeicao_antes_de_enviar_dados_excessivos(): void
    {
        $this->fake();
        $this->post('/alunos',['_ctx'=>$this->ctx,'nome'=>'Aluno','matricula'=>'A-1','turma_id'=>1,'avatar_id'=>2])->assertRedirect()->assertSessionHas('sucesso');
        Http::assertSent(fn($r)=>str_ends_with($r->url(),'/alunos') && $r->data()===['avatar_id'=>2,'turma_id'=>1,'nome'=>'Aluno','matricula'=>'A-1']);
        $this->post('/alunos',['_ctx'=>$this->ctx,'nome'=>'Aluno','matricula'=>'A-2','turma_id'=>1,'cpf'=>'12345678901'])->assertSessionHasErrors('cpf');
        Http::assertNotSent(fn($r)=>isset($r['cpf']));
        $this->post('/turmas',['_ctx'=>$this->ctx,'nome'=>'Turma','serie'=>'3','turno'=>'tarde','ano_letivo'=>2026])->assertRedirect()->assertSessionHas('sucesso');
        Http::assertSent(fn($r)=>str_ends_with($r->url(),'/turmas') && $r->method()==='POST' && !isset($r['escola_id']));
    }
}
