<?php
namespace Tests\Feature;
use App\Models\Usuario;
use App\Services\{SelecaoCadastroCopia,LotesCadastroCopia,RecebimentosCopia};
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\{Crypt,Queue};
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\{DB,Schema};
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class DecisaoCadastroCopiaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp(); $db=DB::connection();
        if (!(($db->getDriverName()==='sqlite' && $db->getDatabaseName()===':memory:')
            || (in_array($db->getDriverName(),['mysql','mariadb'],true) && str_ends_with($db->getDatabaseName(),'_test')))) throw new \RuntimeException('Banco descartável obrigatório.');
        RefreshDatabaseState::$migrated=false; $this->artisan('migrate:fresh',['--force'=>true])->assertSuccessful();
        Schema::table('alunos',function($t) { $t->unique(['espaco_id','matricula'],'t2_alunos_matricula'); $t->dropUnique('alunos_matricula_unique'); });
        config(['espacos.ativo'=>true,'espacos.pessoal'=>true,'vinculos.obrigatorio'=>true,'copias.ativo'=>true,
            'copias.execucao_ativa'=>true,'copias.cadastro_execucao_ativa'=>true,'copias.auditoria_entrega'=>true]);
        foreach ([1,2,3] as $id) Usuario::forceCreate(['id'=>$id,'perfil'=>'professor','nome'=>'Fictício','email'=>"d1-$id@example.test",
            'cpf'=>sprintf('%011d',$id),'password'=>'ficticio','ativo'=>true]);
        foreach ([1,2] as $id) {
            DB::table('clientes')->insert(['id'=>$id,'tipo'=>'rede','nome'=>'Rede','modalidade'=>'interno','situacao'=>'ativo']);
            DB::table('redes')->insert(['id'=>$id,'nome'=>'Rede','tipo'=>'estadual','uf'=>'BA']);
            DB::table('nucleos')->insert(['id'=>$id,'rede_id'=>$id,'nome'=>'Núcleo']);
            DB::table('escolas')->insert(['id'=>$id,'nucleo_id'=>$id,'nome'=>'Escola','inep'=>sprintf('%08d',$id),'tipo_rede'=>'estadual']);
            foreach (['rede'=>0,'nucleo'=>2,'escola'=>4] as $tipo=>$offset) DB::table('espacos')->insert([
                'id'=>$id+$offset,'cliente_id'=>$id,'tipo'=>$tipo,'nome'=>$tipo,'situacao'=>'ativo',$tipo.'_id'=>$id]);
        }
        DB::table('clientes')->insert(['id'=>3,'tipo'=>'professor','nome'=>'Pessoal','modalidade'=>'teste','situacao'=>'ativo']);
        DB::table('espacos')->insert(['id'=>7,'cliente_id'=>3,'tipo'=>'pessoal','nome'=>'Pessoal','situacao'=>'ativo','usuario_proprietario_id'=>1]);
        foreach ([[1,1,7,'professor_autonomo'],[2,2,5,'dir_escolar'],[3,1,6,'professor'],[4,2,6,'dir_escolar'],[5,3,1,'admin_rede']] as [$id,$u,$e,$perfil]) {
            DB::table('usuario_vinculos')->insert(['id'=>$id,'usuario_id'=>$u,'espaco_id'=>$e,'perfil'=>$perfil,'situacao'=>'ativo','inicio_em'=>now()->subDay(),'versao_autorizacao'=>1]);
        }
        foreach ([[1,7,null,'pessoal'],[2,5,1,'escola'],[3,6,2,'escola']] as [$id,$e,$escola,$tipo]) {
            DB::table('provas')->insert(['id'=>$id,'espaco_id'=>$e,'espaco_tipo'=>$tipo,'escola_id'=>$escola,'criado_por'=>1,
                'titulo'=>'Material fictício','disciplina'=>'Matemática','serie'=>'3','num_questoes'=>1,'num_alternativas'=>4]);
            DB::table('gabaritos')->insert(['id'=>$id,'prova_id'=>$id,'espaco_id'=>$e]);
            DB::table('gabarito_questoes')->insert(['gabarito_id'=>$id,'espaco_id'=>$e,'numero_questao'=>1,'alternativa'=>'A']);
        }
        foreach ([[1,5,1,'escola'],[2,6,2,'escola'],[3,7,null,'pessoal']] as [$id,$e,$escola,$tipo]) {
            DB::table('turmas')->insert(['id'=>$id,'espaco_id'=>$e,'espaco_tipo'=>$tipo,'escola_id'=>$escola,'nome'=>'Turma','serie'=>'3','turno'=>'manha','ano_letivo'=>2026]);
            DB::table('alunos')->insert(['id'=>$id,'espaco_id'=>$e,'espaco_tipo'=>$tipo,'turma_id'=>$id,'nome'=>'NOME-ESCOLAR-FICTICIO',
                'matricula'=>"MAT-$id",'data_nascimento'=>$tipo==='escola'?'2010-01-01':null]);
        }
    }
    private function ator(int $v=2): Usuario { return Usuario::find($v===1?1:2)->noVinculo($v); }
    private function recusa(int $status,callable $f): void
    { try { $f(); $this->fail('Deveria recusar.'); } catch(HttpException $e) { $this->assertSame($status,$e->getStatusCode()); } }
    private function pedido(int $v=1,int $destino=7): array
    {
        config(['copias.interface_ativa'=>true]); $r=new RecebimentosCopia('cadastro_minimo');
        $codigo=$r->emitir($this->ator($v),$destino)['codigo']; $d=$r->consultar($this->ator(),5,$codigo);
        $p=(new SelecaoCadastroCopia)->previa($this->ator(),5,$d['destino']['tipo'],[1],[1]);
        return [$this->ator(),5,$codigo,$d['destino_digest'],$p['conteudo_digest'],(string)Str::uuid(),[1],[1]];
    }
    private function evidencia(LotesCadastroCopia $s,string $id): string
    {
        $dig=$s->previaInterface($this->ator(),$id,'origem')['conteudo_digest'];
        $s->evidenciaInterface($this->ator(),$id,$dig,'DOCUMENTO-RESTRITO-FICTICIO',true,CarbonImmutable::now()->addHours(2)); return $dig;
    }
    private function saida(LotesCadastroCopia $s,string $id): string
    {
        $dig=$this->evidencia($s,$id); $s->aprovarInterface($this->ator(),$id,'origem',$dig,CarbonImmutable::now()->addHour(),null); return $dig;
    }
    public function test_finalidade_consumo_digest_retry_sem_nova_fonte(): void
    {
        $a=$this->pedido(); $s=new LotesCadastroCopia;
        $this->recusa(404,fn()=>(new RecebimentosCopia)->consultar($this->ator(),5,$a[2]));
        $c=(new RecebimentosCopia)->emitir($this->ator(1),7)['codigo'];
        $this->recusa(404,fn()=>(new RecebimentosCopia('cadastro_minimo'))->consultar($this->ator(),5,$c));
        $bad=$a; $bad[3]=str_repeat('0',64); $this->recusa(409,fn()=>$s->criarViaRecebimento(...$bad));
        $id=$s->criarViaRecebimento(...$a); $this->assertSame($id,$s->criarViaRecebimento(...$a));
        DB::table('copias_recebimentos')->update(['valida_ate'=>now()->subHour()]);
        DB::table('alunos')->where('id',1)->update(['nome'=>'Alterado']);
        $this->assertSame($id,$s->criarViaRecebimento(...$a));
        $bad=$a; $bad[4]=str_repeat('0',64); $this->recusa(409,fn()=>$s->criarViaRecebimento(...$bad));
        $bad=$a; $bad[5]=(string)Str::uuid(); $this->recusa(409,fn()=>$s->criarViaRecebimento(...$bad));
        $this->assertDatabaseCount('copias_lotes',1); $this->assertDatabaseCount('copias_outbox',1);
        $this->assertSame(hash('sha256',$a[2]),DB::table('copias_recebimentos')->where('lote_id',$id)->value('codigo_hash'));
        $this->recusa(409,fn()=>$s->previaInterface($this->ator(),$id,'origem'));
    }
    public function test_evidencia_cifrada_saida_e_entrada_separadas_sem_jobs(): void
    {
        Queue::fake(); $s=new LotesCadastroCopia; $id=$s->criarViaRecebimento(...$this->pedido());
        $this->recusa(404,fn()=>$s->previaInterface($this->ator(1),$id,'destino'));
        $p=$s->previaInterface($this->ator(),$id,'origem');
        $this->recusa(409,fn()=>$s->aprovarInterface($this->ator(),$id,'origem',$p['conteudo_digest'],CarbonImmutable::now()->addHour(),null));
        $dig=$this->evidencia($s,$id);
        $this->assertSame('DOCUMENTO-RESTRITO-FICTICIO',Crypt::decryptString(DB::table('copias_evidencias')->value('documento_cifrado')));
        $this->assertStringNotContainsString('DOCUMENTO-RESTRITO',DB::table('copias_evidencias')->value('documento_cifrado'));
        $this->recusa(404,fn()=>$s->previaInterface($this->ator(1),$id,'destino')); $this->assertDatabaseCount('copias_autorizacoes',0);
        $s->aprovarInterface($this->ator(),$id,'origem',$dig,CarbonImmutable::now()->addHour(),null);
        $p=$s->previaInterface($this->ator(1),$id,'destino'); $this->assertTrue($p['conciliacao']['sem_bloqueios']);
        foreach(['documento','DOCUMENTO-RESTRITO','ator_id','vinculo_id','origem_id','turma_origem_id','destino_id','contexto_origem'] as $k) $this->assertStringNotContainsString($k,json_encode($p));
        $ate=CarbonImmutable::now()->addMinutes(30)->startOfSecond();
        $s->aprovarInterface($this->ator(1),$id,'destino',$dig,$ate,$p['conciliacao']['digest']);
        $s->aprovarInterface($this->ator(1),$id,'destino',$dig,$ate,$p['conciliacao']['digest']);
        $this->assertDatabaseCount('copias_autorizacoes',2); $this->assertDatabaseCount('copias_outbox',4);
        $this->assertSame('aguardando_auditoria',DB::table('copias_lotes')->value('estado')); $this->assertDatabaseCount('copias_execucoes',0); Queue::assertNothingPushed();
    }
    public function test_conciliacao_muda_e_conflito_nao_expoe_registro_existente(): void
    {
        $s=new LotesCadastroCopia; $id=$s->criarViaRecebimento(...$this->pedido()); $dig=$this->saida($s,$id);
        $p=$s->previaInterface($this->ator(1),$id,'destino');
        DB::table('alunos')->where('id',3)->update(['matricula'=>'MAT-1','nome'=>'SEGREDO-DESTINO']);
        $p2=$s->previaInterface($this->ator(1),$id,'destino'); $this->assertFalse($p2['conciliacao']['sem_bloqueios']);
        $this->assertSame('conflito_matricula',$p2['conciliacao']['itens'][1]['codigo']);
        $this->assertNotSame($p['conciliacao']['digest'],$p2['conciliacao']['digest']);
        $this->assertStringNotContainsString('SEGREDO-DESTINO',json_encode($p2));
        $this->assertNull($s->previaInterface($this->ator(),$id,'origem')['conciliacao']);
        foreach([$p['conciliacao']['digest'],$p2['conciliacao']['digest']] as $d) $this->recusa(409,fn()=>$s->aprovarInterface($this->ator(1),$id,'destino',$dig,CarbonImmutable::now()->addMinutes(30),$d));
        $this->assertDatabaseCount('copias_autorizacoes',1);
    }
    public function test_prazos_confirmacoes_evidencia_alteracao_e_revogacao(): void
    {
        $s=new LotesCadastroCopia; $id=$s->criarViaRecebimento(...$this->pedido()); $dig=$s->previaInterface($this->ator(),$id,'origem')['conteudo_digest'];
        foreach(['https://example.test/doc','',str_repeat('x',65537)] as $doc) $this->recusa(422,fn()=>$s->evidenciaInterface($this->ator(),$id,$dig,$doc,true,CarbonImmutable::now()->addHour()));
        $this->recusa(422,fn()=>$s->evidenciaInterface($this->ator(),$id,$dig,'Texto',false,CarbonImmutable::now()->addHour()));
        $this->recusa(422,fn()=>$s->evidenciaInterface($this->ator(),$id,$dig,'Texto',true,CarbonImmutable::now()->addDays(2)));
        $this->recusa(409,fn()=>$s->evidenciaInterface($this->ator(),$id,str_repeat('0',64),'Texto',true,CarbonImmutable::now()->addHour()));
        $this->saida($s,$id); $p=$s->previaInterface($this->ator(1),$id,'destino');
        $this->recusa(422,fn()=>$s->aprovarInterface($this->ator(),$id,'origem',$dig,CarbonImmutable::now()->addHours(3),null));
        $this->recusa(422,fn()=>$s->aprovarInterface($this->ator(1),$id,'destino',$dig,CarbonImmutable::now()->addMinutes(90),$p['conciliacao']['digest']));
        $this->recusa(409,fn()=>$s->aprovarInterface($this->ator(1),$id,'destino',str_repeat('0',64),CarbonImmutable::now()->addMinutes(30),$p['conciliacao']['digest']));
        DB::table('usuario_vinculos')->where('id',2)->increment('versao_autorizacao');
        $this->recusa(404,fn()=>$s->previaInterface($this->ator(1),$id,'destino'));
        DB::table('copias_lotes')->update(['estado'=>'em_processamento']);
        $this->recusa(409,fn()=>$s->previaInterface($this->ator(),$id,'origem'));
    }
    public function test_pendencia_escolar_nao_autoriza_e_fonte_obsoleta_nao_consume(): void
    {
        $s=new LotesCadastroCopia; $a=$this->pedido(4,6);
        DB::table('alunos')->where('id',1)->update(['data_nascimento'=>null]);
        $this->recusa(409,fn()=>$s->criarViaRecebimento(...$a)); $this->assertDatabaseCount('copias_lotes',0);
        $this->assertNull(DB::table('copias_recebimentos')->value('lote_id'));
        $a[4]=(new SelecaoCadastroCopia)->previa($this->ator(),5,'escola',[1],[1])['conteudo_digest'];
        $id=$s->criarViaRecebimento(...$a); $this->assertTrue($s->previaInterface($this->ator(),$id,'origem')['tem_pendencias']);
        $dig=$this->evidencia($s,$id); $this->recusa(409,fn()=>$s->aprovarInterface($this->ator(),$id,'origem',$dig,CarbonImmutable::now()->addHour(),null));
        $this->recusa(404,fn()=>$s->previaInterface($this->ator(4),$id,'destino'));
    }
    public function test_rollback_lote_e_codigo_quando_recibo_falha(): void
    {
        $a=$this->pedido(); $falha=true;
        DB::listen(function($q) use(&$falha) { if($falha && str_contains(strtolower($q->sql),'insert into') && str_contains($q->sql,'copias_outbox')) throw new \RuntimeException('Falha simulada'); });
        try { (new LotesCadastroCopia)->criarViaRecebimento(...$a); $this->fail('Esperada falha'); }
        catch(\RuntimeException $e) { $this->assertSame('Falha simulada',$e->getMessage()); } finally { $falha=false; }
        $this->assertDatabaseCount('copias_lotes',0); $this->assertDatabaseCount('copias_outbox',0); $this->assertNull(DB::table('copias_recebimentos')->value('lote_id'));
        (new LotesCadastroCopia)->criarViaRecebimento(...$a); $this->assertDatabaseCount('copias_lotes',1);
    }
    public function test_http_tipo_real_injecao_e_confirmacoes(): void
    {
        $a=$this->pedido(); $u=Usuario::find(2); $pai=$u->createToken('api-token')->accessToken;
        $h=['Authorization'=>'Bearer '.$u->createToken('vinculo-contexto',['vinculo:2','identidade:'.$pai->id])->plainTextToken];
        $d=['espaco_id'=>5,'codigo'=>$a[2],'turmas'=>[1],'alunos'=>[1]];
        app('auth')->forgetGuards(); $this->postJson('/api/v1/copias/cadastros/preparar',$d,$h)->assertOk()->assertJsonPath('data.previa.tipo_destino','pessoal')->assertJsonMissingPath('data.previa.alunos.0.dados.data_nascimento');
        app('auth')->forgetGuards(); $this->postJson('/api/v1/copias/cadastros/preparar',$d+['tipo_destino'=>'escola'],$h)->assertUnprocessable();
        $d+=['destino_digest'=>$a[3],'conteudo_digest'=>$a[4],'chave'=>$a[5]];
        app('auth')->forgetGuards(); $this->postJson('/api/v1/copias/cadastros/lotes',$d,$h)->assertUnprocessable();
        app('auth')->forgetGuards(); $this->postJson('/api/v1/copias/cadastros/lotes',$d+['confirmado'=>true,'ator_id'=>1],$h)->assertUnprocessable();
        app('auth')->forgetGuards(); $res=$this->postJson('/api/v1/copias/cadastros/lotes',$d+['confirmado'=>true],$h)->assertCreated(); $id=$res->json('data.lote_id');
        $dig=(new LotesCadastroCopia)->previaInterface($this->ator(),$id,'origem')['conteudo_digest'];
        $e=['conteudo_digest'=>$dig,'documento'=>'Documento restrito','valida_ate'=>now()->utc()->addHour()->format('Y-m-d\TH:i:s\Z')];
        app('auth')->forgetGuards(); $this->postJson('/api/v1/copias/cadastros/lotes/'.$id.'/evidencia',$e,$h)->assertUnprocessable();
        app('auth')->forgetGuards(); $this->postJson('/api/v1/copias/cadastros/lotes/'.$id.'/evidencia',$e+['confirmado'=>true],$h)->assertOk()->assertDontSee('Documento restrito');
        $this->assertDatabaseCount('copias_autorizacoes',0);
    }
    public function test_mesma_identidade_em_dois_vinculos_e_http_entrada_explicita(): void
    {
        $s=new LotesCadastroCopia; $id=$s->criarViaRecebimento(...$this->pedido(4,6)); $dig=$this->saida($s,$id);
        $this->recusa(403,fn()=>$s->previaInterface($this->ator(),$id,'destino'));
        $p=$s->previaInterface($this->ator(4),$id,'destino'); $u=Usuario::find(2); $pai=$u->createToken('api-token')->accessToken;
        $h=['Authorization'=>'Bearer '.$u->createToken('vinculo-contexto',['vinculo:4','identidade:'.$pai->id])->plainTextToken];
        $d=['lado'=>'destino','conteudo_digest'=>$dig,'conciliacao_digest'=>$p['conciliacao']['digest'],'valida_ate'=>now()->utc()->addMinutes(30)->format('Y-m-d\TH:i:s\Z')];
        app('auth')->forgetGuards(); $this->postJson('/api/v1/copias/cadastros/lotes/'.$id.'/aprovar',$d,$h)->assertUnprocessable();
        app('auth')->forgetGuards(); $this->postJson('/api/v1/copias/cadastros/lotes/'.$id.'/aprovar',$d+['confirmado'=>true],$h)->assertOk()->assertJsonPath('data.autoriza_execucao',false);
        $this->assertDatabaseCount('copias_autorizacoes',2); $this->assertDatabaseCount('copias_execucoes',0);
        DB::table('usuario_vinculos')->where('id',4)->update(['situacao'=>'encerrado']);
        $this->recusa(403,fn()=>$s->previaInterface($this->ator(4),$id,'destino'));
    }
    public function test_evidencia_vencida_ou_corrompida_fecha_leitura_e_flags(): void
    {
        $s=new LotesCadastroCopia; $id=$s->criarViaRecebimento(...$this->pedido()); $this->saida($s,$id);
        $original=DB::table('copias_evidencias')->value('documento_cifrado');
        DB::table('copias_evidencias')->update(['documento_cifrado'=>'corrompido']);
        $this->recusa(404,fn()=>$s->previaInterface($this->ator(1),$id,'destino'));
        DB::table('copias_evidencias')->update(['documento_cifrado'=>$original,'valida_ate'=>now()->subMinute()]);
        $this->recusa(404,fn()=>$s->previaInterface($this->ator(1),$id,'destino'));
        config(['copias.interface_ativa'=>false]); $this->recusa(404,fn()=>$s->previaInterface($this->ator(),$id,'origem'));
    }
}
