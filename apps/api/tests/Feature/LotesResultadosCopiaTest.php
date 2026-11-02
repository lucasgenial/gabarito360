<?php
namespace Tests\Feature;
use App\Models\Usuario;
use App\Services\{LotesResultadosCopia,LotesCadastroCopia,OutboxCopia};
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\{Crypt,DB,Schema};
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class LotesResultadosCopiaTest extends TestCase
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
    private function ator(int $v): Usuario { return Usuario::find($v===1?1:2)->noVinculo($v); }
    private function prazo(): CarbonImmutable { return CarbonImmutable::now()->startOfHour()->addHours(2); }
    private function criar(?string $chave=null,?array $deps=null): string
    {
        if (!DB::table('notas')->exists()) $this->resultado();
        return (new LotesResultadosCopia)->criar($this->ator(2),5,7,$chave??(string)Str::uuid(),[1],$deps??['provas'=>[2],'alunos'=>[1],'turmas'=>[1]]);
    }
    private function previa(string $id): array { return (new LotesResultadosCopia)->previa($this->ator(2),$id,'origem'); }
    private function evidencia(string $id): string
    {
        return (new LotesResultadosCopia)->revisarEvidencia($this->ator(2),$id,$this->previa($id)['conteudo_digest'],
            'DOCUMENTO-FICTICIO: finalidade, categorias e espaços revisados','Prazo conforme contrato fictício','Exceções documentadas',true,true,$this->prazo());
    }
    private function aprovar(string $id,string $lado='origem'): string
    {
        return (new LotesResultadosCopia)->aprovar($this->ator($lado==='origem'?2:1),$id,$lado,$this->previa($id)['conteudo_digest'],true,$this->prazo());
    }
    private function recusa(int $status,callable $f): void
    {
        try { $f(); $this->fail('Deveria recusar.'); } catch(HttpException $e) { $this->assertSame($status,$e->getStatusCode()); }
    }
    public function test_pacote_minimo_cifrado_sem_efeitos_operacionais(): void
    {
        $id=$this->criar(); $p=$this->previa($id); $r=DB::table('copias_resultados')->first();
        $this->assertFalse($p['autoriza_execucao']);
        $this->assertSame('legado_sem_versao_original',$p['pacote']['condicao_historica']);
        $this->assertSame('NOME-ESCOLAR-FICTICIO',$p['pacote']['alunos'][0]['nome']);
        $this->assertSame('A',$p['pacote']['resultados'][0]['respostas'][0]['alternativa']);
        $this->assertSame($p['conteudo_digest'],hash('sha256',Crypt::decryptString($r->conteudo_cifrado)));
        $this->assertStringNotContainsString('NOME-ESCOLAR',$r->conteudo_cifrado);
        foreach (['SEGREDO','imagem_url','data_nascimento','cpf','resolvido_por','acertos_por_tema'] as $s) $this->assertStringNotContainsString($s,json_encode($p));
        foreach (['copias_execucoes','copias_staging','registros_origem'] as $t) $this->assertDatabaseCount($t,0);
        $this->assertDatabaseCount('notas',1); $this->assertDatabaseCount('cartoes',1); $this->assertDatabaseCount('alunos',3);
        $this->assertStringNotContainsString('NOME-ESCOLAR',DB::table('copias_outbox')->value('evento'));
    }
    public function test_dependencias_explicitas_chave_idempotente_e_isolamento(): void
    {
        $this->recusa(422,fn()=>$this->criar(null,['provas'=>[2],'alunos'=>[],'turmas'=>[1]]));
        $this->assertDatabaseCount('copias_lotes',0);
        $chave=(string)Str::uuid(); $id=$this->criar($chave); $cifra=DB::table('copias_resultados')->value('conteudo_cifrado');
        $this->assertSame($id,$this->criar($chave)); $this->assertDatabaseCount('copias_outbox',1);
        $this->recusa(409,fn()=>$this->criar($chave,['provas'=>[2],'alunos'=>[1,2],'turmas'=>[1]]));
        $this->assertSame($cifra,DB::table('copias_resultados')->value('conteudo_cifrado'));
        $s=new LotesResultadosCopia;
        $this->recusa(403,fn()=>$s->previa($this->ator(4),$id,'origem'));
        $this->recusa(403,fn()=>$s->previa($this->ator(1),$id,'origem'));
        $this->recusa(409,fn()=>$s->previa($this->ator(1),$id,'destino'));
    }
    public function test_evidencia_exclusiva_duas_decisoes_e_recibos(): void
    {
        $id=$this->criar(); $s=new LotesResultadosCopia;
        $this->recusa(409,fn()=>$this->aprovar($id));
        $e=$this->evidencia($id); $this->assertSame($e,$this->evidencia($id));
        $this->recusa(409,fn()=>$this->aprovar($id,'destino'));
        $a=$this->aprovar($id); $this->assertSame($a,$this->aprovar($id));
        $this->assertSame($this->previa($id)['pacote'],$s->previa($this->ator(1),$id,'destino')['pacote']);
        $d=$this->aprovar($id,'destino'); $this->assertSame($d,$this->aprovar($id,'destino'));
        $this->assertDatabaseCount('copias_resultados_decisoes',2); $this->assertDatabaseCount('copias_outbox',4);
        $this->assertDatabaseHas('copias_resultados_decisoes',['decisao_id'=>$d,'legado_reconhecido'=>true]);
        $r=$s->situacao($this->ator(1),$id); $this->assertTrue($r['decisoes_validas']); $this->assertFalse($r['auditoria_confirmada']);
        foreach (DB::table('copias_outbox')->pluck('id') as $oid) (new OutboxCopia)->entregar($oid);
        $r=$s->situacao($this->ator(1),$id); $this->assertTrue($r['auditoria_confirmada']); $this->assertFalse($r['autoriza_execucao']);
        $this->assertDatabaseCount('copias_execucoes',0);
        $this->assertStringNotContainsString('DOCUMENTO-FICTICIO',DB::table('copias_evidencias')->value('documento_cifrado'));
        $this->recusa(404,fn()=>(new LotesCadastroCopia)->previaOrigem($this->ator(2),$id));
        $cad=(new LotesCadastroCopia)->criar($this->ator(2),5,7,(string)Str::uuid(),[1],[1]);
        $this->recusa(404,fn()=>$s->previa($this->ator(2),$cad,'origem'));
    }
    public function test_legado_guarda_e_prazo_exigem_revisao_explicita(): void
    {
        $id=$this->criar(); $s=new LotesResultadosCopia; $hash=$this->previa($id)['conteudo_digest'];
        $this->recusa(422,fn()=>$s->revisarEvidencia($this->ator(2),$id,$hash,'Documento','Contrato','Exceções',true,false,$this->prazo()));
        $this->recusa(422,fn()=>$s->revisarEvidencia($this->ator(2),$id,$hash,'Documento','','Exceções',true,true,$this->prazo()));
        $this->recusa(422,fn()=>$s->revisarEvidencia($this->ator(2),$id,$hash,'https://example.test','Contrato','Exceções',true,true,$this->prazo()));
        $this->evidencia($id);
        $this->recusa(422,fn()=>$s->aprovar($this->ator(2),$id,'origem',$hash,false,$this->prazo()));
        $this->recusa(422,fn()=>$s->aprovar($this->ator(2),$id,'origem',$hash,true,$this->prazo()->addHour()));
        $this->recusa(409,fn()=>$s->aprovar($this->ator(2),$id,'origem',str_repeat('0',64),true,$this->prazo()));
        $this->assertDatabaseCount('copias_autorizacoes',0);
    }
    public function test_fonte_alterada_nao_sobrescreve_pacote(): void
    {
        $id=$this->criar(); $this->evidencia($id); $this->aprovar($id); $this->aprovar($id,'destino');
        $antes=DB::table('copias_resultados')->first();
        DB::table('notas')->where('id',1)->update(['nota_final'=>7]);
        $this->recusa(409,fn()=>$this->previa($id));
        $this->assertFalse((new LotesResultadosCopia)->situacao($this->ator(1),$id)['decisoes_validas']);
        $this->assertEquals($antes,DB::table('copias_resultados')->first());
        $novo=$this->criar(); $this->assertNotSame($id,$novo);
        $this->assertSame('7.00',$this->previa($novo)['pacote']['resultados'][0]['dados']['nota_final']);
    }
    public function test_expiracao_revogacao_e_versao_invalidam(): void
    {
        $s=new LotesResultadosCopia; $id=$this->criar(); $this->evidencia($id); $this->aprovar($id); $this->aprovar($id,'destino');
        DB::table('usuario_vinculos')->where('id',2)->increment('versao_autorizacao');
        $this->assertFalse($s->situacao($this->ator(1),$id)['decisoes_validas']);
        $id=$this->criar(); $this->evidencia($id); $this->aprovar($id); $this->aprovar($id,'destino');
        DB::table('copias_evidencias')->where('lote_id',$id)->update(['valida_ate'=>now()->subMinute()]);
        $this->assertFalse($s->situacao($this->ator(1),$id)['decisoes_validas']);
        $id=$this->criar(); $this->evidencia($id); $this->aprovar($id); $this->aprovar($id,'destino');
        $s->revogar($this->ator(1),$id,'destino'); $s->revogar($this->ator(1),$id,'destino');
        $this->assertFalse($s->situacao($this->ator(1),$id)['decisoes_validas']);
        $this->recusa(409,fn()=>$this->previa($id)); $this->assertDatabaseCount('copias_resultados',3);
    }
    public function test_adulteracao_do_pacote_e_da_evidencia_bloqueia(): void
    {
        $id=$this->criar(); $this->evidencia($id); $this->aprovar($id); $this->aprovar($id,'destino'); $s=new LotesResultadosCopia;
        DB::table('copias_evidencias')->where('lote_id',$id)->update(['categorias'=>'["alunos"]']);
        $this->assertFalse($s->situacao($this->ator(1),$id)['decisoes_validas']);
        DB::table('copias_resultados')->where('lote_id',$id)->update(['conteudo_digest'=>str_repeat('0',64)]);
        $this->recusa(409,fn()=>$this->previa($id));
        $id=$this->criar(); DB::table('copias_resultados')->where('lote_id',$id)->update(['conteudo_cifrado'=>'invalido']);
        $this->recusa(409,fn()=>$this->previa($id));
    }
    public function test_falha_no_recibo_reverte_pacote_inteiro(): void
    {
        $falhar=true;
        DB::listen(function($q) use(&$falhar) { if ($falhar && str_contains(strtolower($q->sql),'insert into') && str_contains($q->sql,'copias_outbox')) throw new \RuntimeException('falha fictícia'); });
        try { $this->criar(); $this->fail('Deveria falhar.'); } catch(\RuntimeException $e) { $this->assertSame('falha fictícia',$e->getMessage()); }
        foreach (['copias_lotes','copias_itens','copias_resultados','copias_outbox'] as $t) $this->assertDatabaseCount($t,0);
        $falhar=false; $this->criar(); $this->assertDatabaseCount('copias_resultados',1);
    }
}
