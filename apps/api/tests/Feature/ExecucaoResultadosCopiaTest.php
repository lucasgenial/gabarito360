<?php
namespace Tests\Feature;
use App\Models\Usuario;
use App\Jobs\ProcessarCopiaResultados;
use App\Services\{LotesResultadosCopia,OutboxCopia,AcervoResultados,FilaExecucaoCopia};
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\{Bus,Crypt,DB,Schema};
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ExecucaoResultadosCopiaTest extends TestCase
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
    private function auditar(): void { foreach (DB::table('copias_outbox')->whereNull('entregue_em')->pluck('id') as $id) (new OutboxCopia)->entregar($id); }
    private function preparar(): string
    {
        config(['copias.resultados_execucao_ativa'=>true]);
        $id=$this->criar(); $this->evidencia($id); $this->aprovar($id); $this->aprovar($id,'destino'); $this->auditar();
        (new LotesResultadosCopia)->solicitarExecucao($this->ator(1),$id); $this->auditar(); return $id;
    }
    public function test_publicacao_atomica_replay_acervo_independente(): void
    {
        $id=$this->preparar(); $s=new LotesResultadosCopia; $p=$this->previa($id)['pacote'];
        $this->assertSame('preparado',$s->executarPasso($id)); $this->assertDatabaseCount('acervo_resultados',0);
        $this->assertSame('publicado',$s->executarPasso($id)); $this->assertDatabaseCount('acervo_resultados',1);
        $a=DB::table('acervo_resultados')->first();
        $this->assertSame($p,(new AcervoResultados)->consultar($this->ator(1),7,$a->id)['pacote']);
        $this->assertDatabaseCount('acervo_resultados_itens',1); $this->assertDatabaseCount('registros_origem',1);
        $this->assertDatabaseCount('notas',1); $this->assertDatabaseCount('cartoes',1); $this->assertDatabaseCount('alunos',3);
        $this->assertSame('aguardando_recibo',$s->executarPasso($id));
        $this->recusa(409,fn()=>$s->revogar($this->ator(2),$id,'origem'));
        $this->auditar(); $this->assertSame('concluido',$s->executarPasso($id));
        (new ProcessarCopiaResultados($id))->handle(); $this->assertDatabaseCount('acervo_resultados',1);
        $this->assertNull(DB::table('copias_staging')->value('conteudo_cifrado'));
        DB::table('notas')->where('id',1)->update(['nota_final'=>2]);
        DB::table('usuario_vinculos')->where('id',2)->update(['situacao'=>'encerrado']);
        $this->assertSame($p,(new AcervoResultados)->consultar($this->ator(1),7,$a->id)['pacote']);
        $this->recusa(403,fn()=>(new AcervoResultados)->consultar($this->ator(4),7,$a->id));
        $this->recusa(404,fn()=>(new AcervoResultados)->consultar($this->ator(4),6,$a->id));
        DB::table('acervo_resultados')->where('id',$a->id)->update(['conteudo_digest'=>str_repeat('0',64)]);
        $this->recusa(409,fn()=>(new AcervoResultados)->consultar($this->ator(1),7,$a->id));
    }
    public function test_revogacao_antes_de_publicar_preserva_stage_sem_acervo(): void
    {
        $id=$this->preparar(); $s=new LotesResultadosCopia; $s->executarPasso($id);
        $s->revogar($this->ator(2),$id,'origem'); $this->assertSame('bloqueado',$s->executarPasso($id));
        $this->assertDatabaseCount('acervo_resultados',0); $this->assertDatabaseCount('registros_origem',0);
        $this->assertNotNull(DB::table('copias_staging')->value('conteudo_cifrado'));
    }
    public function test_mudanca_entre_stage_e_publicacao_bloqueia(): void
    {
        $id=$this->preparar(); $s=new LotesResultadosCopia; $s->executarPasso($id);
        DB::table('cartao_respostas')->where('cartao_id',1)->update(['alternativa'=>'B']);
        $this->assertSame('bloqueado',$s->executarPasso($id)); $this->assertDatabaseCount('acervo_resultados',0);
        $this->assertDatabaseHas('copias_execucoes',['lote_id'=>$id,'erro_tecnico'=>'autorizacao_ou_conflito']);
    }
    public function test_flag_especifica_fila_e_ator_revalidado(): void
    {
        $id=$this->preparar(); $s=new LotesResultadosCopia;
        config(['copias.resultados_execucao_ativa'=>false,'auditoria.connection'=>'redis']); Bus::fake();
        $this->assertSame('pausado',$s->executarPasso($id));
        $this->recusa(403,fn()=>$s->solicitarExecucao($this->ator(1),$id));
        $this->assertSame(['enviados'=>0,'falhas'=>0],(new FilaExecucaoCopia)->publicar()); Bus::assertNothingDispatched();
        config(['copias.resultados_execucao_ativa'=>true]);
        $this->assertSame(['enviados'=>1,'falhas'=>0],(new FilaExecucaoCopia)->publicar());
        Bus::assertDispatched(ProcessarCopiaResultados::class);
        DB::table('usuario_vinculos')->where('id',1)->increment('versao_autorizacao');
        $this->assertSame('bloqueado',$s->executarPasso($id)); $this->assertDatabaseCount('acervo_resultados',0);
    }
    public function test_falha_depois_do_mapa_reverte_e_job_retoma(): void
    {
        $id=$this->preparar(); $s=new LotesResultadosCopia; $s->executarPasso($id); $falhar=true;
        DB::listen(function($q) use(&$falhar) { if ($falhar && str_contains(strtolower($q->sql),'insert into') && str_contains($q->sql,'registros_origem')) throw new \RuntimeException('SEGREDO'); });
        try { (new ProcessarCopiaResultados($id))->handle(); $this->fail('Deveria falhar.'); }
        catch (\RuntimeException $e) { $this->assertStringNotContainsString('SEGREDO',$e->getMessage()); $this->assertNull($e->getPrevious()); }
        foreach (['acervo_resultados','acervo_resultados_itens','registros_origem'] as $t) $this->assertDatabaseCount($t,0);
        $this->assertNotNull(DB::table('copias_staging')->value('conteudo_cifrado'));
        $falhar=false; $this->auditar(); (new ProcessarCopiaResultados($id))->handle();
        $this->assertDatabaseCount('acervo_resultados',1); $this->auditar(); (new ProcessarCopiaResultados($id))->handle();
        $this->assertDatabaseHas('copias_lotes',['id'=>$id,'estado'=>'concluido']);
    }
    public function test_outro_lote_nao_sobrescreve_mapa_ou_acervo(): void
    {
        $id=$this->preparar(); (new ProcessarCopiaResultados($id))->handle(); $this->auditar(); (new ProcessarCopiaResultados($id))->handle();
        $anterior=DB::table('acervo_resultados')->first();
        $novo=$this->criar(); $this->evidencia($novo); $this->aprovar($novo); $this->aprovar($novo,'destino'); $this->auditar();
        $this->recusa(409,fn()=>(new LotesResultadosCopia)->solicitarExecucao($this->ator(1),$novo));
        $this->assertDatabaseCount('acervo_resultados',1); $this->assertEquals($anterior,DB::table('acervo_resultados')->first());
    }
}
