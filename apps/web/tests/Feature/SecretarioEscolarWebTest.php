<?php
namespace Tests\Feature;

use App\Support\{Acesso, ContextoWeb};
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Tests\Support\NavegacaoHttp;

class SecretarioEscolarWebTest extends TestCase
{
    use NavegacaoHttp;
    use \Tests\Support\ContextoSelecionado;
    public function test_painel_e_turma_administrativos_nao_consultam_dados_pedagogicos(): void
    {
        config(['services.api.base_url'=>'http://api.test/api']);
        Http::preventStrayRequests();
        $this->withSession(['auth_token'=>'identidade','auth_vinculos'=>true,'auth_nome'=>'Secretaria fictícia']);
        $this->withCookie(config('session.cookie'),session()->getId())->withCredentials();
        $dados=['vinculo_id'=>1,'tipo_contexto'=>'vinculo','token'=>'token-escolar','perfil'=>'secretario_escolar','espaco'=>['id'=>1,'tipo'=>'escola','nome'=>'Escola fictícia'],
            'permissoes'=>['escola.ver','turma.ver','turma.gerir','turma.desativar','aluno.ver','aluno.gerir','aluno.importar','aluno.desativar']];
        $this->selecionarContextoFixture($dados);$id=ContextoWeb::referencia($dados);
        $turma=['id'=>1,'nome'=>'Turma fictícia','serie'=>'4','turno'=>'tarde','ano_letivo'=>2026,'ativo'=>true,'escola_nome'=>'Escola fictícia','total_alunos'=>1,
            'alunos'=>[['id'=>1,'nome'=>'Aluno fictício','matricula'=>'A1','ativo'=>true]]];
        Http::fake(function ($r) use ($dados,$turma) {
            // O seletor compartilhado consulta opções pela identidade, não pelo token contextual.
            if ($r->method()==='GET' && preg_match('~/auth/(vinculos|acessos-internos)$~',$r->url())) {
                $this->assertTrue($r->hasHeader('Authorization','Bearer '.session('auth_token')));
                return Http::response(['data'=>[]]);
            }

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
        $this->consultarTela('/app/painel')->assertOk()->assertSee('Secretaria escolar')->assertSee('Ver alunos')->assertDontSee('Relatórios');
        $this->assertSame(['Painel','Turmas','Alunos'],array_column(Acesso::menuCelular()['barra'],'rotulo'));
        $this->consultarTela('/app/turmas')->assertOk()->assertSee('Turma fictícia')->assertDontSee('Desempenho médio');
        $this->consultarTela('/app/turmas/1')->assertOk()->assertSee('Aluno fictício')->assertSee('Editar turma')->assertSee('Transferir')->assertDontSee('Média')->assertDontSee('Vincular professor');
        $this->consultarTela('/app/turmas/1/editar')->assertOk()->assertSee('Salvar alterações')->assertDontSee('modal-editar');
        $this->alterarRecurso('PUT', '/app/turmas/1',['nome'=>'Turma editada','serie'=>'4','turno'=>'tarde','ano_letivo'=>2026])->assertRetornoTela()->assertSessionHas('sucesso');
        $this->consultarTela('/app/provas')->assertForbidden();
        $this->consultarTela('/app/membros')->assertForbidden();
        $this->consultarTela('/app/acessos-internos')->assertForbidden();
        Http::assertNotSent(fn($r)=>str_contains($r->url(),'/dashboard/') || str_contains($r->url(),'/professores') || str_contains($r->url(),'/usuarios'));
    }
}
