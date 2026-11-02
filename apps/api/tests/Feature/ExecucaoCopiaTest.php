<?php
namespace Tests\Feature;

use App\Models\Usuario;
use App\Jobs\ProcessarCopiaMaterial;
use App\Services\{LotesCopia,OutboxCopia,FilaExecucaoCopia};
use App\Support\Escopo;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ExecucaoCopiaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp(); $db=DB::connection();
        if (!(($db->getDriverName()==='sqlite' && $db->getDatabaseName()===':memory:')
            || (in_array($db->getDriverName(),['mysql','mariadb'],true) && str_ends_with($db->getDatabaseName(),'_test')))) throw new \RuntimeException('Banco descartável obrigatório.');
        RefreshDatabaseState::$migrated=false;
        $this->artisan('migrate:fresh',['--force'=>true])->assertSuccessful();
        config(['copias.ativo'=>true,'copias.auditoria_entrega'=>true,'copias.execucao_ativa'=>true,
            'espacos.ativo'=>true,'espacos.pessoal'=>true,'vinculos.obrigatorio'=>true,'auditoria.connection'=>'redis']);
        foreach ([1,2] as $id) {
            Usuario::forceCreate(['id'=>$id,'perfil'=>'professor','nome'=>'Fictício','email'=>"execucao-$id@example.test",
                'cpf'=>sprintf('%011d',$id),'password'=>'ficticio','ativo'=>true]);
            DB::table('clientes')->insert(['id'=>$id,'tipo'=>'professor','nome'=>'Fictício','modalidade'=>'interno','situacao'=>'ativo']);
            DB::table('espacos')->insert(['id'=>$id,'cliente_id'=>$id,'tipo'=>'pessoal','nome'=>'Pessoal','situacao'=>'ativo','usuario_proprietario_id'=>$id]);
            DB::table('usuario_vinculos')->insert(['id'=>$id,'usuario_id'=>$id,'espaco_id'=>$id,'perfil'=>'professor_autonomo',
                'situacao'=>'ativo','inicio_em'=>now()->subDay(),'versao_autorizacao'=>1]);
        }
        $this->prova(1);
    }
    private function prova(int $id): void
    {
        DB::table('provas')->insert(['id'=>$id,'espaco_id'=>1,'espaco_tipo'=>'pessoal','criado_por'=>1,
            'titulo'=>'MATERIAL-FICTICIO-PRIVADO','disciplina'=>'Matemática','serie'=>'3','num_questoes'=>1,'num_alternativas'=>4,
            'status'=>'corrigida','data_aplicacao'=>'2026-01-01','correcao_fechada_em'=>now(),'correcao_fechada_por'=>1]);
        DB::table('gabaritos')->insert(['id'=>$id,'prova_id'=>$id,'espaco_id'=>1,'publicado_por'=>1,'publicado_em'=>now()]);
        DB::table('gabarito_questoes')->insert(['id'=>$id,'gabarito_id'=>$id,'espaco_id'=>1,'numero_questao'=>1,'alternativa'=>'A']);
    }
    private function ator(int $id): Usuario { return Usuario::find($id)->noVinculo($id); }
    private function auditar(): void
    {
        foreach (DB::table('copias_outbox')->whereNull('entregue_em')->pluck('id') as $id) (new OutboxCopia)->entregar($id);
    }
    private function preparar(array $provas=[1], int $destino=2): string
    {
        $s=new LotesCopia; $origem=$this->ator(1); $alvo=$this->ator(2);
        $id=$s->criar($origem,1,$destino,(string)Str::uuid(),$provas);
        $p=$s->previaMaterial($origem,$id); $s->revisarMaterial($origem,$id,$p['conteudo_digest'],true);
        $s->aprovar($origem,$id,'origem',CarbonImmutable::now()->addHour());
        $s->aprovar($alvo,$id,'destino',CarbonImmutable::now()->addHour());
        $this->auditar(); $this->assertSame($id,$s->solicitarExecucao($alvo,$id)); $this->auditar();
        return $id;
    }

    public function test_staging_privado_e_publicacao_atomica_com_ids_novos_e_rascunho(): void
    {
        $s=new LotesCopia; $id=$this->preparar(); $antes=(array)DB::table('provas')->find(1);
        $this->assertSame('preparado',$s->executarPasso($id));
        $this->assertStringNotContainsString('MATERIAL-FICTICIO-PRIVADO',DB::table('copias_staging')->value('conteudo_cifrado'));
        $this->assertSame(0,Escopo::provas($this->ator(2))->count());
        $this->assertDatabaseCount('provas',1); $this->assertDatabaseCount('registros_origem',0);
        $this->assertSame('publicado',$s->executarPasso($id));
        $mapa=DB::table('registros_origem')->first(); $this->assertNotEquals(1,$mapa->destino_id);
        $destino=DB::table('provas')->find($mapa->destino_id);
        $this->assertSame('rascunho',$destino->status); $this->assertEquals(2,$destino->espaco_id);
        $this->assertEquals(2,$destino->criado_por); $this->assertNull($destino->escola_id);
        $this->assertNull($destino->data_aplicacao); $this->assertNull($destino->correcao_fechada_em);
        $this->assertNull($destino->correcao_fechada_por); $this->assertFalse((bool)$destino->gerar_cartao_pdf);
        $g=DB::table('gabaritos')->where('prova_id',$destino->id)->first();
        $this->assertNotEquals(1,$g->id); $this->assertNull($g->publicado_em); $this->assertNull($g->publicado_por);
        $this->assertDatabaseHas('gabarito_questoes',['gabarito_id'=>$g->id,'espaco_id'=>2,'alternativa'=>'A']);
        $this->assertSame($antes,(array)DB::table('provas')->find(1));
        $this->assertNull(DB::table('copias_staging')->value('conteudo_cifrado'));
        $this->assertSame('aguardando_recibo',$s->executarPasso($id));
        $this->auditar(); $this->assertSame('concluido',$s->executarPasso($id));
        DB::table('provas')->where('id',$destino->id)->update(['titulo'=>'Edição legítima']);
        DB::table('provas')->where('id',1)->update(['titulo'=>'Fonte alterada depois']);
        (new ProcessarCopiaMaterial($id))->handle();
        $this->assertDatabaseHas('provas',['id'=>$destino->id,'titulo'=>'Edição legítima']);
        $this->assertDatabaseCount('provas',2); $this->assertDatabaseCount('registros_origem',1);
    }

    public function test_revogacao_do_executor_entre_etapas_bloqueia_com_contexto_historico(): void
    {
        $id=$this->preparar(); $s=new LotesCopia; $s->executarPasso($id);
        DB::table('usuario_vinculos')->where('id',2)->update(['situacao'=>'encerrado']);
        $this->assertSame('bloqueado',$s->executarPasso($id));
        $this->assertDatabaseCount('provas',1); $this->assertDatabaseCount('registros_origem',0);
        $evento=json_decode(DB::table('copias_outbox')->whereNull('entregue_em')->first()->evento,true);
        $refs=json_decode($evento['referencias'],true);
        $this->assertSame('worker_copia',$refs['executor']); $this->assertSame(2,$refs['versao']);
        $this->assertSame(2,$evento['usuario_real_id']); $this->auditar();
    }

    public function test_alteracao_da_fonte_entre_etapas_impede_gravacao_no_destino(): void
    {
        $id=$this->preparar(); $s=new LotesCopia; $s->executarPasso($id);
        DB::table('gabarito_questoes')->where('id',1)->update(['alternativa'=>'B']);
        $this->assertSame('bloqueado',$s->executarPasso($id));
        $this->assertDatabaseCount('provas',1); $this->assertDatabaseCount('registros_origem',0);
        $this->assertNotNull(DB::table('copias_staging')->value('conteudo_cifrado'));
    }

    public function test_cifra_corrompida_nao_publica_conteudo(): void
    {
        $id=$this->preparar(); $s=new LotesCopia; $s->executarPasso($id);
        DB::table('copias_staging')->update(['conteudo_cifrado'=>'corrompido']);
        $this->assertSame('bloqueado',$s->executarPasso($id));
        $this->assertDatabaseHas('copias_execucoes',['lote_id'=>$id,'erro_tecnico'=>'integridade_invalida']);
        $this->assertDatabaseCount('provas',1);
    }

    public function test_falha_apos_insercoes_reverte_unidade_e_retentativa_nao_duplica(): void
    {
        $id=$this->preparar(); (new LotesCopia)->executarPasso($id); $injetou=false;
        DB::listen(function ($q) use (&$injetou) {
            if (!$injetou && preg_match('/^insert into ["`]registros_origem["`]/',$q->sql)) {
                $injetou=true; throw new \RuntimeException('MATERIAL-FICTICIO-PRIVADO erro simulado');
            }
        });
        try { (new ProcessarCopiaMaterial($id))->handle(); $this->fail('Deveria falhar.'); }
        catch (\RuntimeException $e) { $this->assertStringNotContainsString('MATERIAL-FICTICIO-PRIVADO',$e->getMessage()); $this->assertNull($e->getPrevious()); }
        $this->assertDatabaseCount('provas',1); $this->assertDatabaseCount('gabaritos',1); $this->assertDatabaseCount('gabarito_questoes',1);
        $this->assertDatabaseCount('registros_origem',0); $this->assertDatabaseHas('copias_itens',['lote_id'=>$id,'estado'=>'preparado']);
        $this->auditar(); (new ProcessarCopiaMaterial($id))->handle(); (new ProcessarCopiaMaterial($id))->handle();
        $this->assertDatabaseCount('provas',2); $this->assertDatabaseCount('registros_origem',1);
        $this->auditar(); (new ProcessarCopiaMaterial($id))->handle();
        $this->assertDatabaseHas('copias_execucoes',['lote_id'=>$id,'estado'=>'concluido']);
    }

    public function test_revogacao_de_lote_parcial_nao_exclui_unidade_ja_publicada(): void
    {
        $this->prova(2); $id=$this->preparar([1,2]); (new ProcessarCopiaMaterial($id))->handle();
        $this->assertDatabaseCount('registros_origem',1); $this->auditar();
        DB::table('usuario_vinculos')->where('id',1)->increment('versao_autorizacao');
        $this->assertSame('bloqueado',(new LotesCopia)->executarPasso($id));
        $this->assertDatabaseCount('provas',3); $this->assertDatabaseCount('registros_origem',1);
    }

    public function test_flag_e_auditoria_pendente_nao_executam_e_intencao_repete_sem_duplicar(): void
    {
        $id=$this->preparar(); $s=new LotesCopia;
        $this->assertSame($id,$s->solicitarExecucao($this->ator(2),$id));
        $this->assertDatabaseCount('copias_execucoes',1);
        config(['copias.execucao_ativa'=>false]); $this->assertSame('pausado',$s->executarPasso($id));
        $this->assertDatabaseCount('copias_staging',0);
        config(['copias.execucao_ativa'=>true]); DB::table('copias_outbox')->update(['entregue_em'=>null]);
        $this->assertSame('aguardando_auditoria',$s->executarPasso($id));
        $this->assertDatabaseCount('copias_staging',0);
    }

    public function test_fila_indisponivel_preserva_intencao_e_payload_sem_material(): void
    {
        $id=$this->preparar(); $mock=\Mockery::mock(\Illuminate\Contracts\Bus\Dispatcher::class);
        $mock->shouldReceive('dispatch')->once()->with(\Mockery::on(function ($j) use ($id) {
            $this->assertSame($id,$j->loteId); $this->assertStringNotContainsString('MATERIAL-FICTICIO-PRIVADO',serialize($j)); return true;
        }))->andThrow(new \RuntimeException('Redis indisponível'));
        $this->app->instance(\Illuminate\Contracts\Bus\Dispatcher::class,$mock);
        $this->assertSame(['enviados'=>0,'falhas'=>1],(new FilaExecucaoCopia)->publicar());
        $this->assertDatabaseHas('copias_execucoes',['lote_id'=>$id,'estado'=>'pendente','erro_tecnico'=>'fila_indisponivel']);
        $this->assertDatabaseCount('provas',1);
    }

    public function test_destino_escolar_recebe_referencias_proprias_sem_herdar_escola_da_origem(): void
    {
        DB::table('clientes')->insert(['id'=>3,'tipo'=>'rede','nome'=>'Rede','modalidade'=>'interno','situacao'=>'ativo']);
        DB::table('redes')->insert(['id'=>1,'nome'=>'Rede','tipo'=>'estadual','uf'=>'BA']);
        DB::table('nucleos')->insert(['id'=>1,'rede_id'=>1,'nome'=>'Núcleo']);
        DB::table('escolas')->insert(['id'=>1,'nucleo_id'=>1,'nome'=>'Escola','inep'=>'00000001','tipo_rede'=>'estadual']);
        foreach (['rede'=>3,'nucleo'=>4,'escola'=>5] as $tipo=>$id) DB::table('espacos')->insert([
            'id'=>$id,'cliente_id'=>3,'tipo'=>$tipo,'nome'=>$tipo,'situacao'=>'ativo',$tipo.'_id'=>1]);
        DB::table('usuario_vinculos')->where('id',2)->update(['espaco_id'=>5,'perfil'=>'dir_escolar']);
        $id=$this->preparar([1],5); (new ProcessarCopiaMaterial($id))->handle();
        $this->assertDatabaseHas('provas',['espaco_id'=>5,'espaco_tipo'=>'escola','escola_id'=>1,'status'=>'rascunho']);
        $this->assertDatabaseHas('provas',['id'=>1,'espaco_id'=>1,'escola_id'=>null]);
    }

    public function test_mariadb_worker_concorrente_aguarda_lock_do_lote(): void
    {
        if (!in_array(DB::connection()->getDriverName(),['mysql','mariadb'],true)) $this->markTestSkipped('Exige MariaDB.');
        $id=$this->preparar(); (new LotesCopia)->executarPasso($id);
        config(['database.connections.t5_worker_disputa'=>config('database.connections.'.config('database.default'))]);
        $outro=DB::connection('t5_worker_disputa'); $outro->statement('SET SESSION innodb_lock_wait_timeout=1');
        $tentou=false; $codigo=null;
        DB::listen(function ($q) use ($outro,$id,&$tentou,&$codigo) {
            if ($tentou || !str_starts_with($q->sql,'insert into `registros_origem`')) return;
            $tentou=true;
            try { $outro->transaction(fn()=>$outro->table('copias_lotes')->where('id',$id)->lockForUpdate()->first()); $codigo=0; }
            catch (QueryException $e) { $codigo=$e->errorInfo[1]; }
        });
        try {
            (new ProcessarCopiaMaterial($id))->handle();
            $this->assertTrue($tentou); $this->assertSame(1205,$codigo);
            (new ProcessarCopiaMaterial($id))->handle(); $this->assertDatabaseCount('registros_origem',1);
        } finally { DB::purge('t5_worker_disputa'); }
    }
}
