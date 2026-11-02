<?php
namespace Tests\Feature;

use App\Jobs\GravarAuditoria;
use App\Models\{AuditoriaEvento, Usuario};
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Log, Queue};
use Tests\TestCase;

class AuditoriaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['auditoria.ativa'=>true, 'auditoria.connection'=>'database', 'acessos_internos.ativo'=>true,
            'vinculos.obrigatorio'=>true, 'espacos.ativo'=>true, 'espacos.pessoal'=>true]);
        Usuario::forceCreate(['id'=>1,'perfil'=>'professor','nome'=>'Fictício','email'=>'auditoria@example.test',
            'cpf'=>'00000000001','password'=>'senha-ficticia','ativo'=>true]);
        DB::table('usuario_acessos_internos')->insert(['id'=>1,'usuario_id'=>1,'perfil'=>'desenvolvedor',
            'situacao'=>'ativo','inicio_em'=>now()->subDay(),'versao_autorizacao'=>1]);
    }

    private function chamar(string $token, string $metodo, string $url, array $dados = [])
    {
        app('auth')->forgetGuards();
        return $this->json($metodo,'/api/v1/'.$url,$dados,['Authorization'=>'Bearer '.$token,'X-Usuario-Real-Id'=>'999','X-Vinculo-Id'=>'999']);
    }

    private function token(): string
    {
        $identidade=Usuario::find(1)->createToken('api-token')->plainTextToken;
        return $this->chamar($identidade,'POST','auth/acessos-internos/selecionar',['acesso_interno_id'=>1])
            ->assertCreated()->json('data.token');
    }

    public function test_captura_alteracao_leitura_e_recusa_sem_payload_ou_credenciais(): void
    {
        Queue::fake([GravarAuditoria::class]);
        $token=$this->token();
        $id=$this->chamar($token,'POST','clientes',['nome'=>'NOME-SECRETO','tipo'=>'escola','modalidade'=>'teste',
            'documento'=>'DOCUMENTO-SECRETO','contato_email'=>'segredo@example.test'])->assertCreated()->json('data.id');
        $this->chamar($token,'GET',"clientes/$id?busca=SEGREDO-QUERY")->assertOk();
        $this->chamar($token,'GET','alunos?cpf=SEGREDO-CPF')->assertForbidden();
        Queue::assertPushed(GravarAuditoria::class,4);
        $eventos=Queue::pushed(GravarAuditoria::class)->map(fn($j)=>$j->evento);
        $this->assertSame([201,201,200,403],$eventos->pluck('status_http')->all());
        $leitura=$eventos[2];
        $this->assertSame('api/v1/clientes/{id}',$leitura['rota']);
        $this->assertSame(['id'=>(string)$id],json_decode($leitura['referencias'],true));
        $this->assertEquals(1,$leitura['usuario_real_id']);
        $this->assertEquals(1,$leitura['usuario_visto_id']);
        $this->assertEquals(1,$leitura['acesso_interno_id']);
        $this->assertSame('desenvolvedor',$leitura['perfil']);
        $this->assertNull($leitura['vinculo_id']);
        $this->assertSame('127.0.0.0/24',$leitura['ip_rede']);
        foreach (['SECRETO','SEGREDO','segredo@example.test',$token] as $segredo) {
            $this->assertStringNotContainsString($segredo,$eventos->toJson());
        }
        $this->assertSame('recusado',$eventos[3]['resultado']);
        Queue::assertPushedOn('baixa',GravarAuditoria::class);
        $this->assertDatabaseCount('auditoria_eventos',0);
    }

    public function test_reentrega_idempotente_e_modelo_sem_edicao_ou_exclusao(): void
    {
        Queue::fake([GravarAuditoria::class]);
        $this->token();
        $job=Queue::pushed(GravarAuditoria::class)->first();
        $job->handle(); $job->handle();
        $this->assertDatabaseCount('auditoria_eventos',1);
        $registro=AuditoriaEvento::firstOrFail();
        foreach (['save','delete'] as $metodo) {
            try { $registro->$metodo(); $this->fail('Alteração deveria falhar.'); }
            catch (\LogicException $e) { $this->assertStringContainsString('somente para leitura',$e->getMessage()); }
        }
        $this->assertDatabaseCount('auditoria_eventos',1);
    }

    public function test_fila_real_cifra_payload_e_worker_grava(): void
    {
        config(['queue.connections.database.table'=>'au1_test_jobs']);
        \Illuminate\Support\Facades\Schema::create('au1_test_jobs', function ($t) {
            $t->temporary();
            $t->bigIncrements('id'); $t->string('queue'); $t->longText('payload');
            $t->unsignedTinyInteger('attempts'); $t->unsignedInteger('reserved_at')->nullable();
            $t->unsignedInteger('available_at'); $t->unsignedInteger('created_at');
        });
        $this->token();
        $linha=DB::table('au1_test_jobs')->where('queue','baixa')->first();
        $this->assertNotNull($linha);
        $this->assertStringNotContainsString('usuario_real_id',$linha->payload);
        $this->assertStringNotContainsString('referencias',$linha->payload);
        $this->assertDatabaseCount('auditoria_eventos',0);
        $job=Queue::connection('database')->pop('baixa');
        $job->fire();
        $this->assertDatabaseCount('auditoria_eventos',1);
        $this->assertDatabaseCount('au1_test_jobs',0);
        \Illuminate\Support\Facades\Schema::drop('au1_test_jobs');
    }

    public function test_contexto_pessoal_nao_usa_identidade_interna_ou_payload(): void
    {
        Queue::fake([GravarAuditoria::class]);
        DB::table('clientes')->insert(['id'=>1,'tipo'=>'professor','nome'=>'Fictício','modalidade'=>'interno','situacao'=>'ativo']);
        DB::table('espacos')->insert(['id'=>1,'cliente_id'=>1,'tipo'=>'pessoal','nome'=>'Pessoal','situacao'=>'ativo','usuario_proprietario_id'=>1]);
        DB::table('usuario_vinculos')->insert(['id'=>1,'usuario_id'=>1,'espaco_id'=>1,'perfil'=>'professor_autonomo','situacao'=>'ativo','inicio_em'=>now()->subDay()]);
        $identidade=Usuario::find(1)->createToken('api-token')->plainTextToken;
        $token=$this->chamar($identidade,'POST','auth/vinculos/selecionar',['vinculo_id'=>1])->assertCreated()->json('data.token');
        $this->chamar($token,'GET','auth/me?usuario_real_id=999')->assertOk();
        $evento=Queue::pushed(GravarAuditoria::class)->last()->evento;
        $this->assertEquals(1,$evento['vinculo_id']);
        $this->assertEquals(1,$evento['espaco_id']);
        $this->assertEquals(1,$evento['cliente_id']);
        $this->assertSame('professor_autonomo',$evento['perfil']);
        $this->assertNull($evento['acesso_interno_id']);
        DB::table('usuario_vinculos')->where('id',1)->update(['situacao'=>'suspenso']);
        $this->chamar($token,'GET','auth/me')->assertForbidden();
        $evento=Queue::pushed(GravarAuditoria::class)->last()->evento;
        $this->assertNull($evento['vinculo_id']);
        $this->assertNull($evento['perfil']);
    }

    public function test_falha_da_fila_nao_altera_resposta_nem_vaza_excecao(): void
    {
        $token=Usuario::find(1)->createToken('api-token')->plainTextToken;
        $this->mock(Dispatcher::class)->shouldReceive('dispatch')->andThrow(new \RuntimeException('SEGREDO-EXCECAO'));
        Log::spy();
        $this->chamar($token,'POST','auth/acessos-internos/selecionar',['acesso_interno_id'=>1])->assertCreated();
        Log::shouldHaveReceived('warning')->once()->with('auditoria_enfileiramento_indisponivel');
    }

    public function test_flag_desligada_e_health_nao_enfileiram(): void
    {
        Queue::fake([GravarAuditoria::class]);
        $this->getJson('/api/v1/health')->assertOk();
        $this->getJson('/api/v1/inexistente?token=segredo')->assertNotFound();
        Queue::assertNothingPushed();
        config(['auditoria.ativa'=>false]);
        $this->token();
        Queue::assertNothingPushed();
    }

    public function test_requisicao_sem_autenticacao_e_driver_sincrono(): void
    {
        Queue::fake([GravarAuditoria::class]);
        $this->getJson('/api/v1/alunos?token=segredo')->assertUnauthorized();
        $evento=Queue::pushed(GravarAuditoria::class)->first()->evento;
        $this->assertNull($evento['usuario_real_id']);
        $this->assertSame(401,$evento['status_http']);
        config(['auditoria.connection'=>'sync']);
        Log::spy();
        $this->getJson('/api/v1/alunos')->assertUnauthorized();
        Queue::assertPushed(GravarAuditoria::class,1);
        Log::shouldHaveReceived('warning')->once()->with('auditoria_enfileiramento_indisponivel');
    }

    public function test_erro_http_ipv6_e_falha_de_persistencia_nao_sao_ocultados(): void
    {
        Queue::fake([GravarAuditoria::class]);
        \Illuminate\Support\Facades\Route::post('api/v1/ensaio-auditoria/{id}', fn()=>abort(500,'SEGREDO-ERRO'));
        $this->withServerVariables(['REMOTE_ADDR'=>'2001:db8:abcd:1234::5678'])
            ->postJson('/api/v1/ensaio-auditoria/1',['password'=>'SEGREDO-SENHA'])->assertStatus(500);
        $job=Queue::pushed(GravarAuditoria::class)->first();
        $this->assertSame('erro',$job->evento['resultado']);
        $this->assertSame('2001:db8:abcd::/48',$job->evento['ip_rede']);
        $this->assertStringNotContainsString('SEGREDO',json_encode($job->evento));
        $invalido=$job->evento;
        $invalido['rota']=null;
        $this->expectException(\Illuminate\Database\QueryException::class);
        (new GravarAuditoria($invalido))->handle();
    }

    public function test_logout_preserva_contexto_historico_apos_revogar_token(): void
    {
        Queue::fake([GravarAuditoria::class]);
        $token=$this->token();
        $this->chamar($token,'POST','auth/logout')->assertSuccessful();
        $evento=Queue::pushed(GravarAuditoria::class)->last()->evento;
        $this->assertSame('api/v1/auth/logout',$evento['rota']);
        $this->assertEquals(1,$evento['acesso_interno_id']);
        $this->assertSame('desenvolvedor',$evento['perfil']);
    }

    public function test_login_so_atribui_identidade_apos_validar_credenciais(): void
    {
        Queue::fake([GravarAuditoria::class]);
        $this->postJson('/api/v1/auth/login',['email'=>'auditoria@example.test','password'=>'incorreta','auditoria.usuario_id'=>999])->assertUnauthorized();
        $this->assertNull(Queue::pushed(GravarAuditoria::class)->last()->evento['usuario_real_id']);
        $this->postJson('/api/v1/auth/login',['email'=>'auditoria@example.test','password'=>'senha-ficticia','auditoria.usuario_id'=>999])->assertOk();
        $evento=Queue::pushed(GravarAuditoria::class)->last()->evento;
        $this->assertEquals(1,$evento['usuario_real_id']);
        $this->assertNull($evento['perfil']);
        $this->assertStringNotContainsString('senha-ficticia',json_encode($evento));
    }
}
