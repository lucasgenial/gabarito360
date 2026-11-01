<?php

namespace Tests\Feature;

use App\Support\{Acesso, ContextoWeb};
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IntegracaoPessoalWebTest extends TestCase
{
    public function test_selecao_pessoal_abre_painel_e_menu_sem_poderes_institucionais(): void
    {
        config(['services.api.base_url'=>'http://api.test/api']);
        Http::preventStrayRequests();
        $this->withSession(['auth_token'=>'identidade','auth_vinculos'=>true,'auth_nome'=>'Pessoa fictícia']);
        $this->withCookie(config('session.cookie'),session()->getId())->withCredentials();
        $permissoes=['turma.ver','turma.gerir','aluno.ver','aluno.gerir','prova.ver','prova.criar','gabarito.ver','correcao.acompanhar','cartao.imprimir'];
        $pessoal=['perfil'=>'professor_autonomo','espaco'=>['id'=>1,'tipo'=>'pessoal','nome'=>'Meu espaço pessoal'],'permissoes'=>$permissoes];
        Http::fake(function ($r) use ($pessoal) {
            $path=parse_url($r->url(),PHP_URL_PATH);
            if ($path==='/api/v1/auth/vinculos') return Http::response(['data'=>[
                ['id'=>1]+$pessoal,
                ['id'=>2,'perfil'=>'professor','espaco'=>['id'=>2,'tipo'=>'escola','nome'=>'Escola fictícia']],
            ]]);
            if ($path==='/api/v1/auth/vinculos/selecionar') return Http::response(['data'=>['token'=>'pessoal-token']+$pessoal],201);
            $this->assertTrue($r->hasHeader('Authorization','Bearer pessoal-token'));
            if ($path==='/api/v1/auth/me') return Http::response(['data'=>$pessoal]);
            $this->assertSame('/api/v1/dashboard/professor',$path);
            return Http::response(['data'=>['professor'=>['nome'=>'Pessoa fictícia','espaco_nome'=>'Meu espaço pessoal','total_turmas'=>0],
                'kpis'=>['minhas_provas'=>0,'cartoes_corrigir'=>0,'minhas_turmas'=>0,'media_turmas'=>null], 'provas'=>[]]]);
        });
        $this->get('/vinculos')->assertOk()->assertSee('Professor autônomo')->assertSee('Escola fictícia');
        $destino=$this->post('/vinculos',['vinculo_id'=>1])->assertRedirect()->headers->get('Location');
        $this->get($destino)->assertOk()->assertSee('Meu espaço pessoal')->assertSee('Minhas provas')
            ->assertDontSee('Administrador da Rede')->assertDontSee('href="'.route('escolas.index').'"',false)
            ->assertDontSee('href="'.route('membros.index').'"',false);
        $this->assertSame(['Painel','Corrigir','Provas','Turmas'],array_column(Acesso::menuCelular()['barra'],'rotulo'));
        $this->assertContains('Alunos',array_column(Acesso::menu(),'rotulo'));
        $this->assertSame('identidade',session('auth_token'));
        Http::assertNotSent(fn($r)=>str_contains($r->url(),'/dashboard/admin') || str_contains($r->url(),'/escolas'));
    }
}
