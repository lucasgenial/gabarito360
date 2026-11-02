<?php
namespace Tests\Feature;

use App\Models\Usuario;
use App\Services\{LotesResultadosCopia,RecebimentosCopia};
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\{Crypt,Queue};
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\{DB,Schema};
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class DecisaoResultadosCopiaTest extends TestCase
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
    private function resultado(): void
    {
        DB::table('prova_turmas')->insert(['prova_id'=>2,'turma_id'=>1,'espaco_id'=>5]);
        DB::table('cartoes')->insert(['id'=>1,'prova_id'=>2,'aluno_id'=>1,'turma_aplicacao_id'=>1,'espaco_id'=>5,
            'status'=>'lido','imagem_url'=>'SEGREDO-IMAGEM']);
        DB::table('cartao_respostas')->insert(['cartao_id'=>1,'espaco_id'=>5,'numero_questao'=>1,'alternativa'=>'A','ambigua'=>false]);
        DB::table('notas')->insert(['id'=>1,'cartao_id'=>1,'prova_id'=>2,'aluno_id'=>1,'turma_id'=>1,'espaco_id'=>5,
            'acertos'=>1,'total_questoes'=>1,'nota_final'=>10,'status_aprovacao'=>'aprovado','acertos_por_tema'=>'{"SEGREDO":1}']);
    }
    private function ator(int $v=2): Usuario { return Usuario::find($v===1?1:2)->noVinculo($v); }
    private function recusa(int $status,callable $f): void
    { try { $f(); $this->fail('Deveria recusar.'); } catch(HttpException $e) { $this->assertSame($status,$e->getStatusCode()); } }
    private function pedido(int $v=1,int $destino=7): array
    {
        config(['copias.interface_ativa'=>true]); if(!DB::table('notas')->exists()) $this->resultado();
        $codigo=(new RecebimentosCopia('resultados_identificados'))->emitir($this->ator($v),$destino)['codigo'];
        $p=(new LotesResultadosCopia)->prepararInterface($this->ator(),5,$codigo,[1]);
        return [$this->ator(),5,$codigo,$p['recebimento']['destino_digest'],$p['conteudo_digest'],(string)Str::uuid(),[1],$p['dependencias']];
    }
    private function evidencia(LotesResultadosCopia $s,string $id): string
    {
        $dig=$s->previaInterface($this->ator(),$id,'origem')['conteudo_digest'];
        $s->evidenciaInterface($this->ator(),$id,$dig,'DOCUMENTO-RESTRITO','GUARDA-RESTRITA','PRESERVACAO-RESTRITA',true,true,CarbonImmutable::now()->addHours(2)); return $dig;
    }
    private function saida(LotesResultadosCopia $s,string $id): string
    { $dig=$this->evidencia($s,$id); $s->aprovarInterface($this->ator(),$id,'origem',$dig,true,CarbonImmutable::now()->addHour()); return $dig; }
    public function test_preparacao_completa_finalidade_cifra_consumo_e_retry(): void
    {
        $a=$this->pedido(); $s=new LotesResultadosCopia; $p=$s->prepararInterface($this->ator(),5,$a[2],[1]);
        $this->assertSame('A',$p['pacote']['resultados'][0]['respostas'][0]['alternativa']);
        $this->assertSame('A',$p['pacote']['materiais'][0]['material']['questoes'][0]['alternativa']);
        $this->assertSame('legado_sem_versao_original',$p['pacote']['condicao_historica']);
        $this->assertDatabaseCount('copias_lotes',0); $this->assertDatabaseCount('copias_resultados',0);
        foreach(['preparacao_material','cadastro_minimo'] as $f) $this->recusa(404,fn()=>(new RecebimentosCopia($f))->consultar($this->ator(),5,$a[2]));
        $id=$s->criarViaRecebimento(...$a); $this->assertSame($id,$s->criarViaRecebimento(...$a));
        $cifra=DB::table('copias_resultados')->value('conteudo_cifrado'); $this->assertStringNotContainsString('NOME-ESCOLAR',$cifra);
        $this->assertSame('NOME-ESCOLAR-FICTICIO',json_decode(Crypt::decryptString($cifra),true)['alunos'][0]['nome']);
        DB::table('copias_recebimentos')->update(['valida_ate'=>now()->subHour()]);
        DB::table('cartao_respostas')->update(['alternativa'=>'B']);
        $this->assertSame($id,$s->criarViaRecebimento(...$a)); $this->assertSame($cifra,DB::table('copias_resultados')->value('conteudo_cifrado'));
        $bad=$a; $bad[5]=(string)Str::uuid(); $this->recusa(409,fn()=>$s->criarViaRecebimento(...$bad));
        $this->assertDatabaseCount('copias_outbox',1); $this->assertDatabaseCount('copias_autorizacoes',0);
        $this->recusa(409,fn()=>$s->previaInterface($this->ator(),$id,'origem'));
    }
    public function test_confirmacao_integral_e_dependencias_exatas_sem_consumir_em_falha(): void
    {
        $a=$this->pedido(); $s=new LotesResultadosCopia;
        $bad=$a; $bad[7]['alunos']=[2]; $this->recusa(422,fn()=>$s->criarViaRecebimento(...$bad));
        $bad=$a; $bad[3]=str_repeat('0',64); $this->recusa(409,fn()=>$s->criarViaRecebimento(...$bad));
        DB::table('cartao_respostas')->update(['alternativa'=>'B']);
        $this->recusa(409,fn()=>$s->criarViaRecebimento(...$a));
        $this->assertNull(DB::table('copias_recebimentos')->value('lote_id')); $this->assertDatabaseCount('copias_lotes',0); $this->assertDatabaseCount('copias_resultados',0);
    }
    public function test_evidencia_legado_e_duas_decisoes_sem_exposicao_ou_execucao(): void
    {
        Queue::fake(); $s=new LotesResultadosCopia; $id=$s->criarViaRecebimento(...$this->pedido());
        $this->recusa(404,fn()=>$s->previaInterface($this->ator(1),$id,'destino'));
        $dig=$s->previaInterface($this->ator(),$id,'origem')['conteudo_digest'];
        $this->recusa(409,fn()=>$s->aprovarInterface($this->ator(),$id,'origem',$dig,true,CarbonImmutable::now()->addHour()));
        $this->evidencia($s,$id); $this->assertDatabaseCount('copias_autorizacoes',0);
        $this->recusa(404,fn()=>$s->previaInterface($this->ator(1),$id,'destino'));
        $this->recusa(422,fn()=>$s->aprovarInterface($this->ator(),$id,'origem',$dig,false,CarbonImmutable::now()->addHour()));
        $s->aprovarInterface($this->ator(),$id,'origem',$dig,true,CarbonImmutable::now()->addHour());
        $p=$s->previaInterface($this->ator(1),$id,'destino');
        foreach(['DOCUMENTO-RESTRITO','GUARDA-RESTRITA','PRESERVACAO-RESTRITA','origem_id','cartao_origem_id','ator_id','vinculo_id','espaco_id','SEGREDO-IMAGEM','contexto_observado_digest'] as $k) $this->assertStringNotContainsString($k,json_encode($p));
        $this->assertSame('10.00',$p['pacote']['resultados'][0]['dados']['nota_final']);
        $ate=CarbonImmutable::now()->addMinutes(30)->startOfSecond();
        $s->aprovarInterface($this->ator(1),$id,'destino',$dig,true,$ate); $s->aprovarInterface($this->ator(1),$id,'destino',$dig,true,$ate);
        $this->assertDatabaseCount('copias_resultados_decisoes',2); $this->assertDatabaseCount('copias_outbox',4);
        $this->assertDatabaseCount('copias_execucoes',0); Queue::assertNothingPushed();
    }
    public function test_guarda_preservacao_legado_digest_e_prazos_obrigatorios(): void
    {
        $s=new LotesResultadosCopia; $id=$s->criarViaRecebimento(...$this->pedido()); $dig=$s->previaInterface($this->ator(),$id,'origem')['conteudo_digest'];
        foreach([['doc','','p',true,true],['doc','g','',true,true],['https://example.test/doc','g','p',true,true],['doc','g','p',false,true],['doc','g','p',true,false],['doc',str_repeat('x',2001),'p',true,true]] as [$doc,$g,$p,$ok,$legado]) {
            $this->recusa(422,fn()=>$s->evidenciaInterface($this->ator(),$id,$dig,$doc,$g,$p,$ok,$legado,CarbonImmutable::now()->addHour()));
        }
        $this->recusa(422,fn()=>$s->evidenciaInterface($this->ator(),$id,$dig,'doc','g','p',true,true,CarbonImmutable::now()->addDays(2)));
        $this->recusa(409,fn()=>$s->evidenciaInterface($this->ator(),$id,str_repeat('0',64),'doc','g','p',true,true,CarbonImmutable::now()->addHour()));
        $this->saida($s,$id);
        $this->recusa(422,fn()=>$s->aprovarInterface($this->ator(),$id,'origem',$dig,true,CarbonImmutable::now()->addHours(3)));
        $this->recusa(422,fn()=>$s->aprovarInterface($this->ator(1),$id,'destino',$dig,true,CarbonImmutable::now()->addMinutes(90)));
        $this->recusa(409,fn()=>$s->aprovarInterface($this->ator(1),$id,'destino',str_repeat('0',64),true,CarbonImmutable::now()->addMinutes(30)));
    }
    public function test_contexto_expiracao_estado_e_integridade_fecham_leitura(): void
    {
        $s=new LotesResultadosCopia; $id=$s->criarViaRecebimento(...$this->pedido()); $this->saida($s,$id);
        $this->recusa(403,fn()=>$s->previaInterface($this->ator(4),$id,'destino'));
        $e=DB::table('copias_evidencias')->first(); DB::table('copias_evidencias')->update(['documento_cifrado'=>'corrompido']);
        $this->recusa(404,fn()=>$s->previaInterface($this->ator(1),$id,'destino'));
        DB::table('copias_evidencias')->update(['documento_cifrado'=>$e->documento_cifrado,'valida_ate'=>now()->subMinute()]);
        $this->recusa(404,fn()=>$s->previaInterface($this->ator(1),$id,'destino'));
        DB::table('copias_evidencias')->update(['valida_ate'=>$e->valida_ate]);
        DB::table('usuario_vinculos')->where('id',2)->increment('versao_autorizacao');
        $this->recusa(404,fn()=>$s->previaInterface($this->ator(1),$id,'destino'));
        DB::table('copias_lotes')->update(['estado'=>'em_processamento']); $this->recusa(409,fn()=>$s->previaInterface($this->ator(),$id,'origem'));
        config(['copias.interface_ativa'=>false]); $this->recusa(404,fn()=>$s->previaInterface($this->ator(),$id,'origem'));
    }
    public function test_rollback_pacote_lote_outbox_e_consumo(): void
    {
        $a=$this->pedido(); $falha=true;
        DB::listen(function($q) use(&$falha) { if($falha && str_contains(strtolower($q->sql),'insert into') && str_contains($q->sql,'copias_outbox')) throw new \RuntimeException('Falha simulada'); });
        try { (new LotesResultadosCopia)->criarViaRecebimento(...$a); $this->fail('Esperada falha'); }
        catch(\RuntimeException $e) { $this->assertSame('Falha simulada',$e->getMessage()); } finally { $falha=false; }
        foreach(['copias_lotes','copias_resultados','copias_outbox'] as $t) $this->assertDatabaseCount($t,0);
        $this->assertNull(DB::table('copias_recebimentos')->value('lote_id'));
        (new LotesResultadosCopia)->criarViaRecebimento(...$a); $this->assertDatabaseCount('copias_resultados',1);
    }
    public function test_http_criacao_exige_tres_confirmacoes_e_nao_aceita_ator(): void
    {
        $a=$this->pedido(); $u=Usuario::find(2); $pai=$u->createToken('api-token')->accessToken;
        $h=['Authorization'=>'Bearer '.$u->createToken('vinculo-contexto',['vinculo:2','identidade:'.$pai->id])->plainTextToken];
        $d=['espaco_id'=>5,'codigo'=>$a[2],'destino_digest'=>$a[3],'conteudo_digest'=>$a[4],'chave'=>$a[5],'notas'=>[1],'dependencias'=>$a[7],
            'confirmado'=>true,'dependencias_confirmadas'=>true,'legado_reconhecido'=>true];
        foreach(['confirmado','dependencias_confirmadas','legado_reconhecido'] as $k) { $bad=$d; unset($bad[$k]); app('auth')->forgetGuards(); $this->postJson('/api/v1/copias/resultados/lotes',$bad,$h)->assertUnprocessable(); }
        app('auth')->forgetGuards(); $this->postJson('/api/v1/copias/resultados/lotes',$d+['ator_id'=>1],$h)->assertUnprocessable();
        app('auth')->forgetGuards(); $this->postJson('/api/v1/copias/resultados/lotes',$d,$h)->assertCreated()->assertJsonPath('data.autoriza_execucao',false);
        $this->assertDatabaseCount('copias_autorizacoes',0);
    }
    public function test_mesma_identidade_exige_contextos_separados_e_aceite_http_do_legado(): void
    {
        $s=new LotesResultadosCopia; $id=$s->criarViaRecebimento(...$this->pedido(4,6)); $dig=$this->saida($s,$id);
        $this->recusa(403,fn()=>$s->previaInterface($this->ator(),$id,'destino')); $s->previaInterface($this->ator(4),$id,'destino');
        $u=Usuario::find(2); $pai=$u->createToken('api-token')->accessToken;
        $h=['Authorization'=>'Bearer '.$u->createToken('vinculo-contexto',['vinculo:4','identidade:'.$pai->id])->plainTextToken];
        $d=['lado'=>'destino','conteudo_digest'=>$dig,'confirmado'=>true,'valida_ate'=>now()->utc()->addMinutes(30)->format('Y-m-d\TH:i:s\Z')];
        app('auth')->forgetGuards(); $this->postJson('/api/v1/copias/resultados/lotes/'.$id.'/aprovar',$d,$h)->assertUnprocessable();
        app('auth')->forgetGuards(); $this->postJson('/api/v1/copias/resultados/lotes/'.$id.'/aprovar',$d+['legado_reconhecido'=>true],$h)->assertOk();
        $this->assertDatabaseCount('copias_resultados_decisoes',2); $this->assertDatabaseCount('copias_execucoes',0);
    }
}
