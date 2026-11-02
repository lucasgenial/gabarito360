<?php
namespace Tests\Feature;
use App\Models\Usuario;
use App\Services\{SelecaoMaterialCopia,LotesCopia,RecebimentosMaterial};
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\{DB,Schema,Queue};
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class DecisaoMaterialCopiaTest extends TestCase
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
    private function pedido(): array
    {
        config(['copias.interface_ativa'=>true]); $s=new RecebimentosMaterial;
        $r=$s->emitir($this->ator(1),7); $d=$s->consultar($this->ator(),5,$r['codigo']);
        $p=(new SelecaoMaterialCopia)->previa($this->ator(),5,[2]);
        return [$this->ator(),5,$r['codigo'],$d['destino_digest'],$p['conteudo_digest'],(string)Str::uuid(),[2]];
    }
    private function saida(LotesCopia $s,string $id): string
    {
        $p=$s->previaInterface($this->ator(),$id,'origem'); $dig=$p['conteudo_digest'];
        $s->revisarInterface($this->ator(),$id,$dig,true);
        $s->aprovarInterface($this->ator(),$id,'origem',$dig,CarbonImmutable::now('UTC')->addHour()->startOfSecond()); return $dig;
    }
    public function test_codigo_indica_sem_conceder_e_criacao_tem_retry_sem_novos_efeitos(): void
    {
        $args=$this->pedido(); $s=new LotesCopia;
        $r=DB::table('copias_recebimentos')->first(); $this->assertSame(hash('sha256',$args[2]),$r->codigo_hash);
        $this->assertStringNotContainsString($args[2],json_encode($r));
        $this->assertDatabaseCount('copias_lotes',0); $id=$s->criarViaRecebimento(...$args);
        $this->assertSame($id,$s->criarViaRecebimento(...$args));
        DB::table('copias_recebimentos')->update(['valida_ate'=>now()->subMinute()]);
        $this->assertSame($id,$s->criarViaRecebimento(...$args));
        $this->assertDatabaseCount('copias_lotes',1); $this->assertDatabaseCount('copias_outbox',1);
        $this->assertDatabaseCount('copias_autorizacoes',0); $this->assertDatabaseCount('copias_execucoes',0);
        $alterado=$args; $alterado[4]=str_repeat('0',64); $this->recusa(409,fn()=>$s->criarViaRecebimento(...$alterado));
        $args[5]=(string)Str::uuid(); $this->recusa(409,fn()=>$s->criarViaRecebimento(...$args));
    }
    public function test_confirmacao_obsoleta_nao_consume_codigo(): void
    {
        $args=$this->pedido(); $s=new LotesCopia; $err=$args; $err[3]=str_repeat('0',64);
        $this->recusa(409,fn()=>$s->criarViaRecebimento(...$err));
        DB::table('provas')->where('id',2)->update(['titulo'=>'Alterado']);
        $this->recusa(409,fn()=>$s->criarViaRecebimento(...$args));
        $this->assertDatabaseCount('copias_lotes',0); $this->assertDatabaseCount('copias_outbox',0);
        $this->assertNull(DB::table('copias_recebimentos')->value('lote_id'));
    }
    public function test_expiracao_versao_papel_limite_e_flags(): void
    {
        $a=$this->pedido(); $s=new RecebimentosMaterial;
        $this->recusa(404,fn()=>$s->consultar($this->ator(),5,str_repeat('f',64)));
        $this->recusa(404,fn()=>$s->consultar($this->ator(1),7,$a[2]));
        $this->recusa(403,fn()=>$s->emitir(Usuario::find(1)->noVinculo(3),6));
        for($i=0;$i<4;$i++) $s->emitir($this->ator(1),7);
        $this->recusa(429,fn()=>$s->emitir($this->ator(1),7));
        DB::table('usuario_vinculos')->where('id',1)->increment('versao_autorizacao');
        $this->recusa(404,fn()=>$s->consultar($this->ator(),5,$a[2]));
        $r=$s->emitir($this->ator(4),6);
        DB::table('copias_recebimentos')->update(['valida_ate'=>now()->subMinute()]);
        $this->recusa(404,fn()=>$s->consultar($this->ator(),5,$r['codigo']));
        config(['copias.interface_ativa'=>false]); $this->recusa(404,fn()=>$s->emitir($this->ator(1),7));
    }
    public function test_destino_so_le_apos_revisao_e_saida_e_decisoes_nao_executam(): void
    {
        Queue::fake();
        $s=new LotesCopia; $id=$s->criarViaRecebimento(...$this->pedido());
        $this->recusa(404,fn()=>$s->previaInterface($this->ator(1),$id,'destino'));
        $p=$s->previaInterface($this->ator(),$id,'origem'); $dig=$p['conteudo_digest'];
        $this->recusa(409,fn()=>$s->aprovarInterface($this->ator(),$id,'origem',$dig,CarbonImmutable::now()->addHour()));
        $this->recusa(422,fn()=>$s->revisarInterface($this->ator(),$id,$dig,false));
        $this->recusa(409,fn()=>$s->revisarInterface($this->ator(),$id,str_repeat('0',64),true));
        $this->saida($s,$id); $p=$s->previaInterface($this->ator(1),$id,'destino');
        $this->assertArrayNotHasKey('origem_id',$p['itens'][0]); $this->assertArrayNotHasKey('selecao_digest',$p);
        $this->recusa(422,fn()=>$s->aprovarInterface($this->ator(1),$id,'destino',$dig,CarbonImmutable::now()->addHours(2)));
        $this->recusa(422,fn()=>$s->aprovarInterface($this->ator(1),$id,'destino',$dig,CarbonImmutable::now()->addDays(2)));
        $this->recusa(409,fn()=>$s->aprovarInterface($this->ator(1),$id,'destino',str_repeat('0',64),CarbonImmutable::now()->addMinutes(30)));
        $ate=CarbonImmutable::now()->addMinutes(30)->startOfSecond();
        $s->aprovarInterface($this->ator(1),$id,'destino',$dig,$ate); $s->aprovarInterface($this->ator(1),$id,'destino',$dig,$ate);
        $this->assertDatabaseCount('copias_autorizacoes',2); $this->assertDatabaseCount('copias_outbox',4);
        $this->assertDatabaseCount('copias_execucoes',0); Queue::assertNothingPushed();
        $this->assertSame('aguardando_auditoria',DB::table('copias_lotes')->value('estado'));
        DB::table('usuario_vinculos')->where('id',2)->increment('versao_autorizacao');
        $this->recusa(404,fn()=>$s->previaInterface($this->ator(1),$id,'destino'));
    }
    public function test_fonte_prazo_e_estado_revalidados_antes_de_expor(): void
    {
        $s=new LotesCopia; $id=$s->criarViaRecebimento(...$this->pedido()); $this->saida($s,$id);
        $this->recusa(403,fn()=>$s->previaInterface($this->ator(4),$id,'destino'));
        DB::table('provas')->where('id',2)->update(['titulo'=>'Alterado']);
        $this->recusa(404,fn()=>$s->previaInterface($this->ator(1),$id,'destino'));
        DB::table('provas')->where('id',2)->update(['titulo'=>'Material fictício']);
        DB::table('copias_autorizacoes')->update(['valida_ate'=>now()->subMinute()]);
        $this->recusa(404,fn()=>$s->previaInterface($this->ator(1),$id,'destino'));
        DB::table('copias_lotes')->update(['estado'=>'em_processamento']);
        $this->recusa(409,fn()=>$s->previaInterface($this->ator(),$id,'origem'));
    }
    public function test_falha_no_recibo_desfaz_lote_e_consumo(): void
    {
        $args=$this->pedido(); $falhar=true;
        DB::listen(function($q) use(&$falhar) { if($falhar && str_contains(strtolower($q->sql),'insert into') && str_contains($q->sql,'copias_outbox')) throw new \RuntimeException('falha simulada'); });
        try { (new LotesCopia)->criarViaRecebimento(...$args); $this->fail('Esperada falha'); }
        catch(\RuntimeException $e) { $this->assertSame('falha simulada',$e->getMessage()); } finally { $falhar=false; }
        $this->assertDatabaseCount('copias_lotes',0); $this->assertDatabaseCount('copias_outbox',0);
        $this->assertNull(DB::table('copias_recebimentos')->value('lote_id'));
        (new LotesCopia)->criarViaRecebimento(...$args); $this->assertDatabaseCount('copias_lotes',1);
    }
    public function test_http_nao_aceita_ator_injetado_ou_confirmacao_implicita(): void
    {
        $a=$this->pedido(); $u=Usuario::find(2); $pai=$u->createToken('api-token')->accessToken;
        $token=$u->createToken('vinculo-contexto',['vinculo:2','identidade:'.$pai->id])->plainTextToken; $h=['Authorization'=>'Bearer '.$token];
        $d=['espaco_id'=>5,'codigo'=>$a[2],'destino_digest'=>$a[3],'conteudo_digest'=>$a[4],'chave'=>$a[5],'provas'=>[2]];
        app('auth')->forgetGuards(); $this->postJson('/api/v1/copias/materiais/lotes',$d,$h)->assertUnprocessable();
        foreach(['vinculo_id'=>1,'ator_id'=>1,'destino_espaco_id'=>7] as $k=>$v) {
            app('auth')->forgetGuards(); $this->postJson('/api/v1/copias/materiais/lotes',$d+['confirmado'=>true,$k=>$v],$h)->assertUnprocessable();
        }
        app('auth')->forgetGuards(); $res=$this->postJson('/api/v1/copias/materiais/lotes',$d+['confirmado'=>true],$h)->assertCreated()->assertJsonPath('data.autoriza_execucao',false);
        $id=$res->json('data.lote_id'); app('auth')->forgetGuards();
        $this->getJson('/api/v1/copias/materiais/lotes/'.$id.'?lado=origem',$h)->assertOk();
        app('auth')->forgetGuards(); $this->postJson('/api/v1/copias/materiais/lotes/'.$id.'/revisar',['conteudo_digest'=>$a[4]],$h)->assertUnprocessable();
        $this->assertDatabaseCount('copias_autorizacoes',0);
    }
    public function test_mesma_identidade_exige_contextos_separados_e_destino_vigente(): void
    {
        config(['copias.interface_ativa'=>true]); $r=new RecebimentosMaterial; $s=new LotesCopia;
        $codigo=$r->emitir($this->ator(4),6)['codigo']; $d=$r->consultar($this->ator(),5,$codigo);
        $p=(new SelecaoMaterialCopia)->previa($this->ator(),5,[2]);
        $id=$s->criarViaRecebimento($this->ator(),5,$codigo,$d['destino_digest'],$p['conteudo_digest'],(string)Str::uuid(),[2]);
        $dig=$this->saida($s,$id);
        $this->recusa(403,fn()=>$s->previaInterface($this->ator(),$id,'destino'));
        $this->assertSame('destino',$s->previaInterface($this->ator(4),$id,'destino')['lado']);
        DB::table('usuario_vinculos')->where('id',4)->update(['situacao'=>'encerrado']);
        $this->recusa(403,fn()=>$s->aprovarInterface($this->ator(4),$id,'destino',$dig,CarbonImmutable::now()->addMinutes(30)));
        $this->assertDatabaseCount('copias_autorizacoes',1);
    }
    public function test_http_revisao_e_aprovacao_exigem_confirmacao_e_prazo_utc(): void
    {
        $s=new LotesCopia; $id=$s->criarViaRecebimento(...$this->pedido()); $dig=$s->previaInterface($this->ator(),$id,'origem')['conteudo_digest'];
        $u=Usuario::find(2); $pai=$u->createToken('api-token')->accessToken;
        $h=['Authorization'=>'Bearer '.$u->createToken('vinculo-contexto',['vinculo:2','identidade:'.$pai->id])->plainTextToken];
        app('auth')->forgetGuards();
        $this->postJson('/api/v1/copias/materiais/lotes/'.$id.'/revisar',['conteudo_digest'=>$dig,'sem_dados_pessoais'=>true],$h)->assertOk();
        $d=['lado'=>'origem','conteudo_digest'=>$dig,'valida_ate'=>now()->utc()->addHour()->format('Y-m-d\TH:i:s\Z')];
        app('auth')->forgetGuards(); $this->postJson('/api/v1/copias/materiais/lotes/'.$id.'/aprovar',$d,$h)->assertUnprocessable();
        app('auth')->forgetGuards(); $this->postJson('/api/v1/copias/materiais/lotes/'.$id.'/aprovar',$d+['confirmado'=>true],$h)->assertOk()->assertJsonPath('data.autoriza_execucao',false);
        $this->assertDatabaseCount('copias_autorizacoes',1); $this->assertDatabaseCount('copias_execucoes',0);
    }
}
