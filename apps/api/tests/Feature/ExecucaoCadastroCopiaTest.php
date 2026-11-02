<?php
namespace Tests\Feature;

use App\Jobs\{ProcessarCopiaCadastro,ProcessarCopiaMaterial};
use App\Models\Usuario;
use App\Services\{LotesCadastroCopia,OutboxCopia,FilaExecucaoCopia};
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\{DB,Schema};
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ExecucaoCadastroCopiaTest extends TestCase
{
    private LotesCadastroCopia $s;
    protected function setUp(): void
    {
        parent::setUp(); $db=DB::connection();
        if (!(($db->getDriverName()==='sqlite' && $db->getDatabaseName()===':memory:')
            || (in_array($db->getDriverName(),['mysql','mariadb'],true) && str_ends_with($db->getDatabaseName(),'_test')))) throw new \RuntimeException('Banco descartável obrigatório.');
        RefreshDatabaseState::$migrated=false; $this->artisan('migrate:fresh',['--force'=>true])->assertSuccessful();
        Schema::table('alunos',function($t) { $t->unique(['espaco_id','matricula'],'t2_alunos_matricula'); $t->dropUnique('alunos_matricula_unique'); });
        config(['copias.ativo'=>true,'copias.auditoria_entrega'=>true,'copias.execucao_ativa'=>true,'copias.cadastro_execucao_ativa'=>true,
            'espacos.ativo'=>true,'espacos.pessoal'=>true,'vinculos.obrigatorio'=>true,'auditoria.connection'=>'redis']);
        foreach ([1,2] as $id) {
            Usuario::forceCreate(['id'=>$id,'perfil'=>'professor','nome'=>'Fictício','email'=>"c3-$id@example.test",
                'cpf'=>sprintf('%011d',$id),'password'=>'ficticio','ativo'=>true]);
            DB::table('clientes')->insert(['id'=>$id,'tipo'=>'professor','nome'=>'Fictício','modalidade'=>'interno','situacao'=>'ativo']);
            DB::table('espacos')->insert(['id'=>$id,'cliente_id'=>$id,'tipo'=>'pessoal','nome'=>'Pessoal','situacao'=>'ativo','usuario_proprietario_id'=>$id]);
            DB::table('usuario_vinculos')->insert(['id'=>$id,'usuario_id'=>$id,'espaco_id'=>$id,'perfil'=>'professor_autonomo',
                'situacao'=>'ativo','inicio_em'=>now()->subDay(),'versao_autorizacao'=>1]);
        }
        $this->turma(1,1); $this->aluno(1,1,1); $this->s=new LotesCadastroCopia;
    }
    private function turma(int $id,int $espaco): void
    {
        $e=DB::table('espacos')->find($espaco);
        DB::table('turmas')->insert(['id'=>$id,'espaco_id'=>$espaco,'espaco_tipo'=>$e->tipo,'escola_id'=>$e->escola_id,
            'nome'=>'Turma fictícia','serie'=>'3','turno'=>'manha','ano_letivo'=>2026]);
    }
    private function aluno(int $id,int $espaco,int $turma,?string $matricula=null): void
    {
        $e=DB::table('espacos')->find($espaco);
        DB::table('alunos')->insert(['id'=>$id,'espaco_id'=>$espaco,'espaco_tipo'=>$e->tipo,'turma_id'=>$turma,'nome'=>'NOME-FICTICIO-PRIVADO',
            'matricula'=>$matricula??"MAT-$id",'avatar_id'=>7,'data_nascimento'=>$e->tipo==='escola'?'2010-01-02':null,
            'genero'=>$e->tipo==='escola'?'O':null,'nome_responsavel'=>$e->tipo==='escola'?'RESPONSAVEL-FICTICIO':null,
            'foto_path'=>$e->tipo==='escola'?'privado/ficticio.jpg':null]);
    }
    private function escola(int $ator,int $numero): int
    {
        DB::table('clientes')->insert(['id'=>$numero,'tipo'=>'rede','nome'=>'Rede','modalidade'=>'interno','situacao'=>'ativo']);
        DB::table('redes')->insert(['id'=>$numero,'nome'=>'Rede','tipo'=>'estadual','uf'=>'BA']);
        DB::table('nucleos')->insert(['id'=>$numero,'rede_id'=>$numero,'nome'=>'Núcleo']);
        DB::table('escolas')->insert(['id'=>$numero,'nucleo_id'=>$numero,'nome'=>'Escola','inep'=>sprintf('%08d',$numero),'tipo_rede'=>'estadual']);
        foreach (['rede'=>1,'nucleo'=>2,'escola'=>3] as $tipo=>$offset) DB::table('espacos')->insert(['id'=>$numero*10+$offset,
            'cliente_id'=>$numero,'tipo'=>$tipo,'nome'=>$tipo,'situacao'=>'ativo',$tipo.'_id'=>$numero]);
        $e=$numero*10+3; DB::table('usuario_vinculos')->where('id',$ator)->update(['espaco_id'=>$e,'perfil'=>'dir_escolar']); return $e;
    }
    private function ator(int $id=1): Usuario { return Usuario::find($id)->noVinculo($id); }
    private function auditar(): void { foreach (DB::table('copias_outbox')->whereNull('entregue_em')->pluck('id') as $id) (new OutboxCopia)->entregar($id); }
    private function preparar(array $turmas=[1],array $alunos=[1],int $origem=1,int $destino=2): string
    {
        $id=$this->s->criar($this->ator(),$origem,$destino,(string)Str::uuid(),$turmas,$alunos);
        $p=$this->s->previaOrigem($this->ator(),$id);
        $this->s->revisarEvidencia($this->ator(),$id,$p['conteudo_digest'],'DOCUMENTO FICTICIO RESTRITO',true,CarbonImmutable::now()->addDay());
        $this->s->aprovar($this->ator(),$id,'origem',CarbonImmutable::now()->addHour());
        $r=$this->s->conciliar($this->ator(2),$id);
        $this->s->aprovar($this->ator(2),$id,'destino',CarbonImmutable::now()->addHour(),$r['digest']);
        $this->auditar(); $this->assertSame($id,$this->s->solicitarExecucao($this->ator(2),$id)); $this->auditar(); return $id;
    }
    private function terminar(string $id): void
    {
        for ($n=0;$n<5;$n++) { (new ProcessarCopiaCadastro($id))->handle(); $this->auditar(); if (DB::table('copias_lotes')->where('id',$id)->value('estado')==='concluido') return; }
        $this->fail('Não concluiu.');
    }
    public function test_staging_privado_e_publicacao_atomica_minimizada_com_novos_ids(): void
    {
        $origem=$this->escola(1,10); $this->turma(10,$origem); $this->aluno(10,$origem,10);
        $antes=(array)DB::table('alunos')->find(10); $id=$this->preparar([10],[10],$origem);
        $this->assertSame('preparado',$this->s->executarPasso($id));
        $this->assertSame(0,DB::table('alunos')->where('espaco_id',2)->count());
        $this->assertSame(0,DB::table('turmas')->where('espaco_id',2)->count());
        $this->assertStringNotContainsString('NOME-FICTICIO',DB::table('copias_staging')->value('conteudo_cifrado'));
        $this->assertSame('publicado',$this->s->executarPasso($id));
        $a=DB::table('alunos')->where('espaco_id',2)->first(); $t=DB::table('turmas')->where('espaco_id',2)->first();
        $this->assertNotEquals(10,$a->id); $this->assertNotEquals(10,$t->id); $this->assertEquals($t->id,$a->turma_id);
        $this->assertEquals(2,$a->criado_por); $this->assertSame('pessoal',$a->espaco_tipo); $this->assertNull($t->escola_id);
        foreach (['cpf','cpf_hash','data_nascimento','genero','nome_responsavel','foto_path'] as $campo) $this->assertNull($a->$campo);
        $this->assertEquals(7,$a->avatar_id); $this->assertSame($antes,(array)DB::table('alunos')->find(10));
        $this->assertNull(DB::table('copias_staging')->value('conteudo_cifrado'));
        $this->assertSame('aguardando_recibo',$this->s->executarPasso($id)); $this->terminar($id);
        $this->assertDatabaseCount('registros_origem',2);
    }
    public function test_duas_unidades_nao_invalidam_a_conciliacao_por_seus_proprios_mapas(): void
    {
        $this->turma(3,1); $this->aluno(3,1,3); $id=$this->preparar([1,3],[1,3]);
        $this->terminar($id); $this->assertDatabaseCount('registros_origem',4);
        $this->assertSame(2,DB::table('alunos')->where('espaco_id',2)->count());
    }
    public function test_nova_selecao_reutiliza_mapas_sem_sobrescrever_edicoes(): void
    {
        $id=$this->preparar(); $this->terminar($id);
        $a=DB::table('alunos')->where('espaco_id',2)->first(); DB::table('alunos')->where('id',$a->id)->update(['nome'=>'EDITADO','matricula'=>'EDITADA']);
        $novo=$this->preparar(); $this->terminar($novo); $this->assertDatabaseCount('alunos',2); $this->assertDatabaseCount('registros_origem',2);
        $this->assertDatabaseHas('alunos',['id'=>$a->id,'nome'=>'EDITADO','matricula'=>'EDITADA']);
        $acoes=DB::table('copias_outbox')->where('lote_id',$novo)->get()->map(fn($r)=>json_decode(json_decode($r->evento,true)['referencias'],true)['acao']);
        $this->assertSame(2,$acoes->filter(fn($a)=>$a==='item_reutilizado')->count());
        DB::table('alunos')->where('id',1)->update(['nome'=>'FONTE-ALTERADA']); (new ProcessarCopiaCadastro($id))->handle();
        $this->assertTrue($this->s->revalidar($this->ator(2),$id));
        try { $this->s->revogar($this->ator(),$id,'origem'); $this->fail('Concluído não reabre.'); } catch(HttpException $e) { $this->assertSame(409,$e->getStatusCode()); }
    }
    public function test_revogacao_apos_primeira_unidade_preserva_publicado_e_bloqueia_restante(): void
    {
        $this->turma(3,1); $this->aluno(3,1,3); $id=$this->preparar([1,3],[1,3]);
        (new ProcessarCopiaCadastro($id))->handle(); $this->auditar();
        $this->assertSame('preparado',$this->s->executarPasso($id));
        DB::table('usuario_vinculos')->where('id',2)->update(['situacao'=>'encerrado']);
        $this->assertSame('bloqueado',$this->s->executarPasso($id));
        $this->assertSame(1,DB::table('alunos')->where('espaco_id',2)->count());
        $this->assertSame(1,DB::table('copias_staging')->whereNotNull('conteudo_cifrado')->count());
    }
    public function test_matricula_conflitante_apos_preparacao_impede_toda_unidade(): void
    {
        $id=$this->preparar(); $this->s->executarPasso($id); $this->turma(20,2); $this->aluno(20,2,20,'MAT-1');
        $this->assertSame('bloqueado',$this->s->executarPasso($id));
        $this->assertDatabaseCount('registros_origem',0); $this->assertSame(1,DB::table('turmas')->where('espaco_id',2)->count());
    }
    public function test_evidencia_e_staging_corrompidos_impedem_publicacao(): void
    {
        $id=$this->preparar(); $this->s->executarPasso($id);
        DB::table('copias_staging')->update(['conteudo_cifrado'=>'corrompido']);
        $this->assertSame('bloqueado',$this->s->executarPasso($id));
        $outro=$this->preparar(); $this->s->executarPasso($outro);
        DB::table('copias_evidencias')->where('lote_id',$outro)->update(['documento_cifrado'=>'corrompido']);
        $this->assertSame('bloqueado',$this->s->executarPasso($outro)); $this->assertDatabaseCount('alunos',1);
    }
    public function test_falha_apos_inserir_aluno_reverte_turma_e_mapas_e_retry_nao_duplica(): void
    {
        $id=$this->preparar(); $this->s->executarPasso($id); $falhou=false;
        DB::listen(function($q) use(&$falhou) { if (!$falhou && str_starts_with($q->sql,'insert into') && str_contains($q->sql,'alunos')) {
            $falhou=true; throw new \RuntimeException('SQL NOME-FICTICIO-PRIVADO');
        }});
        try { (new ProcessarCopiaCadastro($id))->handle(); $this->fail('Deveria falhar.'); }
        catch (\RuntimeException $e) { $this->assertNull($e->getPrevious()); $this->assertStringNotContainsString('NOME-FICTICIO',$e->getMessage()); }
        $this->assertDatabaseCount('turmas',1); $this->assertDatabaseCount('alunos',1); $this->assertDatabaseCount('registros_origem',0);
        $this->auditar(); $this->terminar($id); (new ProcessarCopiaCadastro($id))->handle(); $this->assertDatabaseCount('registros_origem',2);
    }
    public function test_savepoint_reverte_unidade_antes_de_registrar_bloqueio_de_dominio(): void
    {
        $id=$this->preparar(); $this->s->executarPasso($id); $falhou=false;
        DB::listen(function($q) use(&$falhou) { if (!$falhou && str_starts_with($q->sql,'insert into') && str_contains($q->sql,'alunos')) {
            $falhou=true; throw new \DomainException('ficticio');
        }});
        $this->assertSame('bloqueado',$this->s->executarPasso($id)); $this->assertDatabaseCount('turmas',1); $this->assertDatabaseCount('registros_origem',0);
    }
    public function test_destino_escolar_recebe_nascimento_autorizado_e_referencias_proprias(): void
    {
        $o=$this->escola(1,10); $d=$this->escola(2,20); $this->turma(10,$o); $this->aluno(10,$o,10);
        $id=$this->preparar([10],[10],$o,$d); $this->terminar($id);
        $this->assertDatabaseHas('turmas',['espaco_id'=>$d,'escola_id'=>20,'espaco_tipo'=>'escola']);
        $a=DB::table('alunos')->where('espaco_id',$d)->first(); $this->assertSame('2010-01-02',$a->data_nascimento);
        foreach (['cpf','cpf_hash','genero','nome_responsavel','foto_path'] as $campo) $this->assertNull($a->$campo);
    }
    public function test_flags_fila_e_politica_nao_enfileiram_cadastro_como_material(): void
    {
        $id=$this->preparar(); config(['copias.cadastro_execucao_ativa'=>false]);
        $this->assertSame('pausado',$this->s->executarPasso($id)); $this->assertSame(['enviados'=>0,'falhas'=>0],(new FilaExecucaoCopia)->publicar());
        config(['copias.cadastro_execucao_ativa'=>true]);
        $antes=DB::table('copias_outbox')->count();
        try { (new ProcessarCopiaMaterial($id))->handle(); $this->fail('Política errada.'); } catch(\RuntimeException) {}
        $this->assertSame($antes,DB::table('copias_outbox')->count());
        $mock=\Mockery::mock(\Illuminate\Contracts\Bus\Dispatcher::class);
        $mock->shouldReceive('dispatch')->once()->with(\Mockery::on(function($j) use($id) {
            $this->assertInstanceOf(ProcessarCopiaCadastro::class,$j); $this->assertSame($id,$j->loteId);
            $this->assertStringNotContainsString('NOME-FICTICIO',serialize($j)); return true;
        }))->andThrow(new \RuntimeException('fila indisponível'));
        $this->app->instance(\Illuminate\Contracts\Bus\Dispatcher::class,$mock);
        $this->assertSame(['enviados'=>0,'falhas'=>1],(new FilaExecucaoCopia)->publicar());
        $this->assertDatabaseHas('copias_execucoes',['lote_id'=>$id,'estado'=>'pendente','erro_tecnico'=>'fila_indisponivel']);
    }
    public function test_mariadb_worker_concorrente_nao_publica_a_mesma_unidade(): void
    {
        if (!in_array(DB::connection()->getDriverName(),['mysql','mariadb'],true)) $this->markTestSkipped('Exige MariaDB.');
        $id=$this->preparar(); $this->s->executarPasso($id);
        config(['database.connections.c3_disputa'=>config('database.connections.'.config('database.default'))]);
        $outro=DB::connection('c3_disputa'); $outro->statement('SET SESSION innodb_lock_wait_timeout=1'); $tentou=false; $codigo=null;
        DB::listen(function($q) use($outro,$id,&$tentou,&$codigo) {
            if ($tentou || !str_starts_with($q->sql,'insert into `alunos`')) return; $tentou=true;
            try { $outro->transaction(fn()=>$outro->table('copias_lotes')->where('id',$id)->lockForUpdate()->first()); $codigo=0; }
            catch (QueryException $e) { $codigo=$e->errorInfo[1]; }
        });
        try { (new ProcessarCopiaCadastro($id))->handle(); $this->assertTrue($tentou); $this->assertSame(1205,$codigo);
            (new ProcessarCopiaCadastro($id))->handle(); $this->assertDatabaseCount('registros_origem',2);
        } finally { DB::purge('c3_disputa'); }
    }
}
