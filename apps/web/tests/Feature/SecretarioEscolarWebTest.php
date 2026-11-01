<?php
namespace Tests\Feature;

use App\Support\{Acesso, ContextoWeb};
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SecretarioEscolarWebTest extends TestCase
{
    public function test_painel_e_turma_administrativos_nao_consultam_dados_pedagogicos(): void
    {
        config(['services.api.base_url'=>'http://api.test/api']);
        Http::preventStrayRequests();
        $this->withSession(['auth_token'=>'identidade','auth_vinculos'=>true,'auth_nome'=>'Secretaria fictícia']);
        $this->withCookie(config('session.cookie'),session()->getId())->withCredentials();
        $dados=['token'=>'token-escolar','perfil'=>'secretario_escolar','espaco'=>['id'=>1,'tipo'=>'escola','nome'=>'Escola fictícia'],
            'permissoes'=>['escola.ver','turma.ver','turma.gerir','turma.desativar','aluno.ver','aluno.gerir','aluno.importar','aluno.desativar']];
        $id=ContextoWeb::criar($dados);
        $turma=['id'=>1,'nome'=>'Turma fictícia','serie'=>'4','turno'=>'tarde','ano_letivo'=>2026,'ativo'=>true,'escola_nome'=>'Escola fictícia','total_alunos'=>1,
            'alunos'=>[['id'=>1,'nome'=>'Aluno fictício','matricula'=>'A1','ativo'=>true]]];
        Http::fake(function ($r) use ($dados,$turma) {
            $this->assertTrue($r->hasHeader('Authorization','Bearer token-escolar'));
            $path=parse_url($r->url(),PHP_URL_PATH);
            return match ($path) {
                '/api/v1/auth/me'=>Http::response(['data'=>$dados]),
                '/api/v1/turmas/1'=>Http::response(['data'=>$turma]),
                '/api/v1/turmas'=>Http::response(['data'=>['turmas'=>[$turma],'kpis'=>['total_alunos'=>1]]]),
                '/api/v1/escolas'=>Http::response(['data'=>['escolas'=>[['id'=>1,'nome'=>'Escola fictícia']]]]),
                default=>$this->fail('Consulta inesperada: '.$path),
            };
        });
        $this->get('/painel?_ctx='.$id)->assertOk()->assertSee('Secretaria escolar')->assertSee('Ver alunos')->assertDontSee('Relatórios');
        $this->assertSame(['Painel','Turmas','Alunos'],array_column(Acesso::menuCelular()['barra'],'rotulo'));
        $this->get('/turmas?_ctx='.$id)->assertOk()->assertSee('Turma fictícia')->assertDontSee('Desempenho médio');
        $this->get('/turmas/1?_ctx='.$id)->assertOk()->assertSee('Aluno fictício')->assertSee('Salvar alterações')->assertDontSee('Média')->assertDontSee('Vincular professor');
        $this->put('/turmas/1',['_ctx'=>$id,'nome'=>'Turma editada','serie'=>'4','turno'=>'tarde','ano_letivo'=>2026])->assertRedirect()->assertSessionHas('sucesso');
        $this->get('/provas?_ctx='.$id)->assertForbidden();
        $this->get('/membros?_ctx='.$id)->assertForbidden();
        $this->get('/acessos-internos?_ctx='.$id)->assertForbidden();
        Http::assertNotSent(fn($r)=>str_contains($r->url(),'/dashboard/') || str_contains($r->url(),'/professores') || str_contains($r->url(),'/usuarios'));
    }
}
