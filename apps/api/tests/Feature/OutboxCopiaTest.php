<?php

namespace Tests\Feature;

use App\Models\Usuario;
use App\Jobs\GravarAuditoria;
use App\Services\{LotesCopia,OutboxCopia,ConferenciaAuditoria,ArquivoMensalAuditoria};
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\{DB,File};
use Illuminate\Support\Str;
use Tests\TestCase;

class OutboxCopiaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $db=DB::connection();
        if (!(($db->getDriverName()==='sqlite' && $db->getDatabaseName()===':memory:')
            || (in_array($db->getDriverName(),['mysql','mariadb'],true) && str_ends_with($db->getDatabaseName(),'_test')))) {
            throw new \RuntimeException('Exige banco descartável.');
        }
        RefreshDatabaseState::$migrated=false;
        $this->artisan('migrate:fresh',['--force'=>true])->assertSuccessful();
        config(['espacos.ativo'=>true,'espacos.pessoal'=>true,'vinculos.obrigatorio'=>true,'copias.ativo'=>true,
            'copias.auditoria_entrega'=>true,'auditoria.connection'=>'redis']);
        foreach ([1,2] as $id) {
            Usuario::forceCreate(['id'=>$id,'perfil'=>'professor','nome'=>'Fictício','email'=>"outbox-$id@example.test",
                'cpf'=>sprintf('%011d',$id),'password'=>'ficticio','ativo'=>true]);
            DB::table('clientes')->insert(['id'=>$id,'tipo'=>'professor','nome'=>'Cliente','modalidade'=>'interno','situacao'=>'ativo']);
            DB::table('espacos')->insert(['id'=>$id,'cliente_id'=>$id,'tipo'=>'pessoal','nome'=>'Pessoal',
                'situacao'=>'ativo','usuario_proprietario_id'=>$id]);
            DB::table('usuario_vinculos')->insert(['id'=>$id,'usuario_id'=>$id,'espaco_id'=>$id,'perfil'=>'professor_autonomo',
                'situacao'=>'ativo','inicio_em'=>'2025-01-01','versao_autorizacao'=>1]);
        }
        DB::table('provas')->insert(['id'=>1,'espaco_id'=>1,'espaco_tipo'=>'pessoal','criado_por'=>1,
            'titulo'=>'CONTEUDO-NAO-AUDITAVEL','disciplina'=>'Matemática','serie'=>'3','num_questoes'=>1,'num_alternativas'=>4]);
        DB::table('gabaritos')->insert(['id'=>1,'prova_id'=>1,'espaco_id'=>1]);
        DB::table('gabarito_questoes')->insert(['id'=>1,'gabarito_id'=>1,'espaco_id'=>1,'numero_questao'=>1,'alternativa'=>'A']);
    }

    private function ator(int $id=1): Usuario { return Usuario::find($id)->noVinculo($id); }
    private function criar(?string $chave=null): string { return (new LotesCopia)->criar($this->ator(),1,2,$chave ?? (string)Str::uuid(),[1]); }
    private function evento(): array { return json_decode(DB::table('copias_outbox')->first()->evento,true,32,JSON_THROW_ON_ERROR); }
    private function falha(callable $acao): void
    {
        try { $acao(); $this->fail('Falha esperada.'); }
        catch (\RuntimeException $e) { $this->assertStringContainsString('pendente',$e->getMessage()); }
    }

    public function test_eventos_atomicos_e_operacoes_idempotentes_nao_repetem_recibo(): void
    {
        $chave=(string)Str::uuid(); $lote=$this->criar($chave); $this->assertSame($lote,$this->criar($chave));
        $validade=CarbonImmutable::now()->addHour()->startOfSecond(); $servico=new LotesCopia;
        $previa=$servico->previaMaterial($this->ator(),$lote);
        $servico->revisarMaterial($this->ator(),$lote,$previa['conteudo_digest'],true);
        foreach ([1=>'origem',2=>'destino'] as $id=>$lado) {
            $servico->aprovar($this->ator($id),$lote,$lado,$validade);
            $servico->aprovar($this->ator($id),$lote,$lado,$validade);
        }
        $this->assertDatabaseCount('copias_outbox',4);
        $this->assertFalse((new OutboxCopia)->confirmada($lote));
        foreach (DB::table('copias_outbox')->pluck('id') as $id) (new OutboxCopia)->entregar($id);
        $this->assertTrue((new OutboxCopia)->confirmada($lote));
        $this->assertDatabaseCount('auditoria_eventos',4);
        $this->assertDatabaseCount('provas',1);
        $servico->revogar($this->ator(),$lote,'origem'); $servico->revogar($this->ator(),$lote,'origem');
        $this->assertDatabaseCount('copias_outbox',6);
        $this->assertFalse((new OutboxCopia)->confirmada($lote));
        $this->assertStringNotContainsString('CONTEUDO-NAO-AUDITAVEL',json_encode(DB::table('copias_outbox')->get()));
    }

    public function test_falha_ao_gravar_recibo_reverte_lote_e_itens(): void
    {
        DB::listen(function ($q) {
            if (preg_match('/^insert into ["`]copias_outbox["`]/',$q->sql)) throw new \RuntimeException('falha-injetada');
        });
        try { $this->criar(); $this->fail('Deveria reverter.'); }
        catch (\RuntimeException $e) { $this->assertSame('falha-injetada',$e->getMessage()); }
        $this->assertDatabaseCount('copias_lotes',0); $this->assertDatabaseCount('copias_itens',0); $this->assertDatabaseCount('copias_outbox',0);
    }

    public function test_confirmacao_perdida_reentrega_mesmo_uuid_e_recusa_colisao_divergente(): void
    {
        $this->criar(); $e=$this->evento();
        (new GravarAuditoria($e))->handle(); // Commit remoto ocorreu; confirmação local foi perdida.
        (new OutboxCopia)->entregar($e['id']); (new OutboxCopia)->entregar($e['id']);
        $this->assertDatabaseCount('auditoria_eventos',1);
        $this->assertNotNull(DB::table('copias_outbox')->value('entregue_em'));
        $alterado=$e; $r=json_decode($e['referencias'],true); $r['versao_acesso']=str_repeat('b',64);
        $alterado['referencias']=json_encode($r);
        try { (new GravarAuditoria($alterado))->handle(); $this->fail('Colisão deveria falhar.'); }
        catch (\LogicException $erro) { $this->assertStringContainsString('divergente',$erro->getMessage()); }
        $this->assertSame(OutboxCopia::digest($e),OutboxCopia::digest((array)DB::table('auditoria_eventos')->first()));
    }

    public function test_auditoria_indisponivel_preserva_recibo_e_retomada(): void
    {
        $this->criar(); $e=$this->evento(); $conexao=config('auditoria.conexao_gravacao');
        config(['auditoria.conexao_gravacao'=>'ausente']);
        $this->falha(fn()=>(new OutboxCopia)->entregar($e['id']));
        $this->assertDatabaseHas('copias_outbox',['id'=>$e['id'],'entregue_em'=>null,'falhas'=>1,'erro_tecnico'=>'entrega_falhou']);
        $this->assertDatabaseCount('auditoria_eventos',0);
        config(['auditoria.conexao_gravacao'=>$conexao]); (new OutboxCopia)->entregar($e['id']);
        $this->assertDatabaseCount('auditoria_eventos',1);
    }

    public function test_publicacao_sem_redis_fica_pendente_e_transporta_apenas_uuid(): void
    {
        $this->criar(); $dispatcher=\Mockery::mock(Dispatcher::class);
        $dispatcher->shouldReceive('dispatch')->once()->with(\Mockery::on(function ($job) {
            $this->assertInstanceOf(\App\Jobs\EntregarAuditoriaCopia::class,$job);
            $this->assertTrue(Str::isUuid($job->reciboId));
            $this->assertStringNotContainsString('CONTEUDO-NAO-AUDITAVEL',serialize($job)); return true;
        }))->andThrow(new \RuntimeException('indisponivel'));
        $this->app->instance(Dispatcher::class,$dispatcher);
        $this->assertSame(['enviados'=>0,'falhas'=>1],(new OutboxCopia)->publicar());
        $this->assertDatabaseHas('copias_outbox',['entregue_em'=>null,'erro_tecnico'=>'fila_indisponivel']);
        $this->assertSame(['enviados'=>0,'falhas'=>0],(new OutboxCopia)->publicar());
    }

    public function test_flags_independentes_e_lote_antigo_nao_e_confirmado(): void
    {
        $lote=$this->criar(); $e=$this->evento(); config(['copias.auditoria_entrega'=>false]);
        (new OutboxCopia)->entregar($e['id']); $this->assertDatabaseCount('auditoria_eventos',0);
        config(['copias.ativo'=>false,'auditoria.ativa'=>false,'copias.auditoria_entrega'=>true]);
        (new OutboxCopia)->entregar($e['id']); $this->assertDatabaseCount('auditoria_eventos',1);
        DB::table('copias_lotes')->where('id',$lote)->update(['auditoria_versao'=>0]);
        $this->assertFalse((new OutboxCopia)->confirmada($lote));
        $this->artisan('g360:copias-auditoria --status')->assertSuccessful();
        $this->artisan('g360:copias-auditoria --limite=101')->assertFailed();
    }

    public function test_digest_corrompido_bloqueia_entrega(): void
    {
        $this->criar(); $e=$this->evento();
        DB::table('copias_outbox')->where('id',$e['id'])->update(['digest'=>str_repeat('a',64)]);
        $this->falha(fn()=>(new OutboxCopia)->entregar($e['id']));
        $this->assertDatabaseCount('auditoria_eventos',0);
    }

    public function test_exportacao_mista_preserva_http_e_inclui_dominio_sem_http_ficticio(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-01-15 12:00:00','UTC'));
        $this->criar(); $e=$this->evento(); (new OutboxCopia)->entregar($e['id']);
        $http=$e; $http['id']=(string)Str::uuid(); $http['metodo']='GET'; $http['rota']='api/v1/alunos';
        $http['status_http']=200; $http['resultado']='sucesso'; $http['referencias']='[]';
        (new GravarAuditoria($http))->handle();
        $antes=ConferenciaAuditoria::linhaCanonica((object)$http);
        $this->assertSame($antes,ConferenciaAuditoria::linhaCanonica(DB::table('auditoria_eventos')->where('id',$http['id'])->first()));
        $this->assertDatabaseHas('auditoria_eventos',['id'=>$e['id'],'metodo'=>null,'rota'=>null,'status_http'=>null]);
        $this->travelBack(); $pasta=sys_get_temp_dir().'/t5-arquivo-'.Str::uuid();
        try {
            $m=(new ArquivoMensalAuditoria)->criar('2026-01',$pasta);
            $this->assertSame(1,$m['versao']); $this->assertSame(2,$m['quantidade']);
            $this->assertSame($m,(new ArquivoMensalAuditoria)->verificar('2026-01',$pasta));
            $this->assertSame($m['sha256'],(new ConferenciaAuditoria)->executar()['meses']['2026-01']['sha256']);
        } finally { File::deleteDirectory($pasta); }
    }
}
