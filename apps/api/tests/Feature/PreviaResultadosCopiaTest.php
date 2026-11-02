<?php
namespace Tests\Feature;

use App\Models\Usuario;
use App\Services\PreviaResultadosCopia;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\{DB,Schema};
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class PreviaResultadosCopiaTest extends TestCase
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
    private function previa(array $ids=[1],int $v=2,int $espaco=5): array
    {
        return (new PreviaResultadosCopia)->previaOrigem(Usuario::find($v===1?1:2)->noVinculo($v),$espaco,'pessoal',$ids);
    }
    private function recusa(int $status,callable $f): void
    {
        try { $f(); $this->fail('Deveria recusar.'); }
        catch (HttpException $e) { $this->assertSame($status,$e->getStatusCode()); }
    }
    public function test_previa_minima_sem_efeitos_ou_autorizacao(): void
    {
        $this->resultado(); $r=$this->previa();
        $this->assertFalse($r['autoriza_execucao']); $this->assertTrue($r['evidencia_resultados_obrigatoria']);
        $this->assertCount(3,$r['bloqueios']); $this->assertCount(1,$r['resultados']);
        $this->assertFalse($r['resultados'][0]['versao_historica_comprovada']);
        $this->assertSame('10.00',$r['resultados'][0]['dados']['nota_final']);
        $this->assertSame(1,$r['resultados'][0]['turma_origem_id']);
        $this->assertSame($r,$this->previa());
        foreach (['SEGREDO','NOME-ESCOLAR','MAT-','imagem_url','confianca','data_nascimento'] as $s) $this->assertStringNotContainsString($s,json_encode($r));
        foreach (['copias_lotes','copias_evidencias','copias_execucoes','copias_outbox','registros_origem'] as $t) $this->assertDatabaseCount($t,0);
        $this->assertDatabaseCount('notas',1); $this->assertDatabaseCount('cartoes',1);
    }
    public function test_selecao_estrita_e_isolamento(): void
    {
        $this->resultado();
        foreach ([[],[1,1],['1'],[0],range(1,21)] as $ids) $this->recusa(422,fn()=>$this->previa($ids));
        $this->recusa(404,fn()=>$this->previa([1,999]));
        $this->recusa(404,fn()=>$this->previa([1],4,6));
        $this->recusa(403,fn()=>$this->previa([1],1,5));
        config(['copias.ativo'=>false]); $this->recusa(403,fn()=>$this->previa());
    }
    public function test_dependencias_divergentes_e_filhos_fora_do_espaco(): void
    {
        $this->resultado();
        DB::table('notas')->where('id',1)->update(['aluno_id'=>2]);
        $this->recusa(409,fn()=>$this->previa());
        DB::table('notas')->where('id',1)->update(['aluno_id'=>1]);
        DB::table('cartao_respostas')->where('cartao_id',1)->update(['espaco_id'=>6]);
        $this->recusa(409,fn()=>$this->previa());
        DB::table('cartao_respostas')->where('cartao_id',1)->update(['espaco_id'=>5]);
        DB::table('prova_turmas')->where('prova_id',2)->delete();
        $this->recusa(409,fn()=>$this->previa());
    }
    public function test_ambiguidades_e_respostas_incompletas_bloqueiam(): void
    {
        $this->resultado();
        DB::table('cartoes')->where('id',1)->update(['status'=>'ambiguo']);
        $this->recusa(422,fn()=>$this->previa());
        DB::table('cartoes')->where('id',1)->update(['status'=>'lido']);
        DB::table('cartao_respostas')->where('cartao_id',1)->update(['ambigua'=>true]);
        $this->recusa(422,fn()=>$this->previa());
        DB::table('cartao_respostas')->where('cartao_id',1)->delete();
        $this->recusa(422,fn()=>$this->previa());
    }
    public function test_digest_observa_mudancas_sem_recalcular_nota(): void
    {
        $this->resultado(); $a=$this->previa();
        DB::table('gabarito_questoes')->where('gabarito_id',2)->update(['alternativa'=>'B']);
        $b=$this->previa(); $this->assertNotSame($a['conteudo_digest'],$b['conteudo_digest']);
        $this->assertSame($a['resultados'][0]['dados'],$b['resultados'][0]['dados']);
        DB::table('notas')->where('id',1)->update(['nota_final'=>8]);
        $c=$this->previa(); $this->assertNotSame($b['conteudo_digest'],$c['conteudo_digest']);
        $this->assertFalse($c['autoriza_execucao']);
        DB::table('usuario_vinculos')->where('id',2)->update(['situacao'=>'encerrado']);
        $this->recusa(403,fn()=>$this->previa());
    }
    public function test_selecao_nao_expande_colegas_e_ordem_nao_muda_digest(): void
    {
        $this->resultado();
        DB::table('alunos')->insert(['id'=>4,'espaco_id'=>5,'espaco_tipo'=>'escola','turma_id'=>1,
            'nome'=>'OUTRO-ALUNO','matricula'=>'OUTRO','data_nascimento'=>'2010-01-01']);
        DB::table('cartoes')->insert(['id'=>2,'prova_id'=>2,'aluno_id'=>4,'turma_aplicacao_id'=>1,'espaco_id'=>5,'status'=>'lido']);
        DB::table('cartao_respostas')->insert(['cartao_id'=>2,'espaco_id'=>5,'numero_questao'=>1,'alternativa'=>null,'ambigua'=>false]);
        DB::table('notas')->insert(['id'=>2,'cartao_id'=>2,'prova_id'=>2,'aluno_id'=>4,'turma_id'=>1,'espaco_id'=>5,
            'acertos'=>0,'total_questoes'=>1,'nota_final'=>0,'status_aprovacao'=>'recuperacao']);
        $this->assertCount(1,$this->previa()['resultados']);
        $this->assertSame($this->previa([1,2]),$this->previa([2,1]));
        $this->assertCount(2,$this->previa([1,2])['resultados']);
        DB::table('gabarito_questoes')->where('gabarito_id',2)->update(['espaco_id'=>6]);
        $this->recusa(409,fn()=>$this->previa());
    }
}
