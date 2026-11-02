<?php
namespace Tests\Feature;

use App\Models\Usuario;
use App\Services\SelecaoResultadosCopia;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\{DB,Schema};
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class SelecaoResultadosCopiaTest extends TestCase
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
    private function servico(): SelecaoResultadosCopia { config(['copias.interface_ativa'=>true]); return new SelecaoResultadosCopia; }
    private function recusa(int $status,callable $f): void
    { try { $f(); $this->fail('Deveria recusar.'); } catch(HttpException $e) { $this->assertSame($status,$e->getStatusCode()); } }
    public function test_catalogo_previa_dependencias_minimas_sem_efeitos(): void
    {
        $this->resultado(); $s=$this->servico(); $c=$s->catalogo($this->ator(),5); $this->assertSame([1],array_column($c['lista']['data'],'id'));
        $p=$s->previa($this->ator(),5,'pessoal',[1]); $this->assertFalse($p['versao_historica_comprovada']); $this->assertFalse($p['autoriza_execucao']);
        $this->assertSame('10.00',$p['resultados'][0]['dados']['nota_final']);
        foreach(['provas'=>[2],'turmas'=>[1],'alunos'=>[1]] as $k=>$ids) $this->assertSame($ids,array_column($p['dependencias'][$k],'id'));
        foreach(['SEGREDO','imagem_url','contexto_origem','versao_acesso','cartao_origem_id','contexto_observado_digest','data_nascimento'] as $campo) $this->assertStringNotContainsString($campo,json_encode([$p,$c]));
        foreach(['copias_lotes','copias_evidencias','copias_autorizacoes','copias_execucoes','copias_outbox','copias_resultados','copias_recebimentos'] as $t) $this->assertDatabaseCount($t,0);
        DB::table('gabarito_questoes')->where('gabarito_id',2)->update(['alternativa'=>'B']);
        $this->assertSame('10.00',$s->previa($this->ator(),5,'escola',[1])['resultados'][0]['dados']['nota_final']);
    }
    public function test_isolamento_antes_de_contagens_e_rotulos(): void
    {
        $this->resultado(); $s=$this->servico(); $this->assertSame(0,$s->catalogo($this->ator(4),6)['lista']['total']);
        $this->recusa(404,fn()=>$s->catalogo($this->ator(4),5));
        $this->recusa(404,fn()=>$s->previa($this->ator(4),6,'pessoal',[1]));
        DB::table('alunos')->where('id',2)->update(['nome'=>'SEGREDO-OUTRO-ESPACO']);
        DB::table('notas')->where('id',1)->update(['aluno_id'=>2]);
        $c=$s->catalogo($this->ator(),5); $this->assertSame(0,$c['lista']['total']); $this->assertStringNotContainsString('SEGREDO',json_encode($c));
        $this->recusa(409,fn()=>$s->previa($this->ator(),5,'pessoal',[1]));
        DB::table('notas')->where('id',1)->update(['aluno_id'=>1]);
        DB::table('provas')->where('id',2)->update(['escola_id'=>2]); $this->assertSame(0,$s->catalogo($this->ator(),5)['lista']['total']);
        DB::table('provas')->where('id',2)->update(['escola_id'=>1]);
        DB::table('prova_turmas')->delete(); $this->assertSame(0,$s->catalogo($this->ator(),5)['lista']['total']);
    }
    public function test_paginacao_e_dependencias_unicas(): void
    {
        $this->resultado(); $s=$this->servico();
        for($id=10;$id<31;$id++) {
            DB::table('alunos')->insert(['id'=>$id,'espaco_id'=>5,'espaco_tipo'=>'escola','turma_id'=>1,'nome'=>'Aluno','matricula'=>'M-'.$id]);
            DB::table('cartoes')->insert(['id'=>$id,'prova_id'=>2,'aluno_id'=>$id,'turma_aplicacao_id'=>1,'espaco_id'=>5,'status'=>'lido']);
            DB::table('cartao_respostas')->insert(['cartao_id'=>$id,'espaco_id'=>5,'numero_questao'=>1,'alternativa'=>'A','ambigua'=>false]);
            DB::table('notas')->insert(['id'=>$id,'cartao_id'=>$id,'prova_id'=>2,'aluno_id'=>$id,'turma_id'=>1,'espaco_id'=>5,'acertos'=>1,'total_questoes'=>1,'nota_final'=>10,'status_aprovacao'=>'aprovado']);
        }
        $c=$s->catalogo($this->ator(),5); $this->assertSame(22,$c['lista']['total']); $this->assertCount(20,$c['lista']['data']);
        $this->assertCount(2,$s->catalogo($this->ator(),5,2)['lista']['data']);
        $p=$s->previa($this->ator(),5,'pessoal',[10,1]); $this->assertCount(1,$p['dependencias']['provas']); $this->assertCount(1,$p['dependencias']['turmas']); $this->assertCount(2,$p['dependencias']['alunos']);
    }
    public function test_validacao_finalizacao_flags_e_revogacao(): void
    {
        $this->resultado(); $s=$this->servico();
        foreach([[],[1,1],['1'],range(1,21)] as $ids) $this->recusa(422,fn()=>$s->previa($this->ator(),5,'pessoal',$ids));
        DB::table('cartao_respostas')->update(['ambigua'=>true]); $this->recusa(422,fn()=>$s->previa($this->ator(),5,'pessoal',[1]));
        DB::table('cartao_respostas')->update(['ambigua'=>false]);
        DB::table('cartoes')->update(['status'=>'ambiguo']); $this->recusa(422,fn()=>$s->previa($this->ator(),5,'pessoal',[1]));
        $this->recusa(403,fn()=>$s->catalogo(Usuario::find(1)->noVinculo(3),6));
        config(['copias.interface_ativa'=>false]); $this->recusa(404,fn()=>$s->catalogo($this->ator(),5)); config(['copias.interface_ativa'=>true]);
        DB::table('usuario_vinculos')->where('id',2)->update(['situacao'=>'encerrado']); $this->recusa(403,fn()=>$s->catalogo($this->ator(),5));
    }
    public function test_http_selecao_explicita_sem_atores_ou_dependencias_forjadas(): void
    {
        $this->resultado(); $this->servico(); $u=Usuario::find(2); $pai=$u->createToken('api-token')->accessToken;
        $h=['Authorization'=>'Bearer '.$u->createToken('vinculo-contexto',['vinculo:2','identidade:'.$pai->id])->plainTextToken];
        $d=['espaco_id'=>5,'tipo_destino'=>'pessoal','notas'=>[1]];
        app('auth')->forgetGuards(); $this->postJson('/api/v1/copias/resultados/previa',$d,$h)->assertOk()->assertJsonPath('data.versao_historica_comprovada',false);
        foreach(['ator_id'=>1,'destino_espaco_id'=>7,'dependencias'=>['alunos'=>[2]]] as $k=>$v) {
            app('auth')->forgetGuards(); $this->postJson('/api/v1/copias/resultados/previa',$d+[$k=>$v],$h)->assertUnprocessable();
        }
        app('auth')->forgetGuards(); $this->getJson('/api/v1/copias/resultados?espaco_id=5',$h)->assertOk()->assertJsonPath('data.lista.total',1);
        $this->assertDatabaseCount('copias_lotes',0);
    }
}
