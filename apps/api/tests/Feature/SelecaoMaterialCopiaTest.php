<?php
namespace Tests\Feature;
use App\Models\Usuario;
use App\Services\SelecaoMaterialCopia;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\{DB,Schema};
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class SelecaoMaterialCopiaTest extends TestCase
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
    private function servico(): SelecaoMaterialCopia { config(['copias.interface_ativa'=>true]); return new SelecaoMaterialCopia; }
    private function recusa(int $status,callable $f): void
    { try { $f(); $this->fail('Deveria recusar.'); } catch(HttpException $e) { $this->assertSame($status,$e->getStatusCode()); } }
    public function test_catalogo_e_previa_minimos_sem_efeitos(): void
    {
        $s=$this->servico(); $c=$s->catalogo($this->ator(),5); $this->assertSame([2],array_column($c['lista']['data'],'id'));
        $p=$s->previa($this->ator(),5,[2]); $this->assertFalse($p['autoriza_execucao']); $this->assertTrue($p['revisao_obrigatoria']);
        $this->assertSame('A',$p['materiais'][0]['material']['questoes'][0]['alternativa']);
        $this->assertSame(2,$p['materiais'][0]['origem_id']);
        foreach (['escola_id','criado_por','NOME-ESCOLAR','aluno_id','imagem_url','destino_espaco_id'] as $campo) $this->assertStringNotContainsString($campo,json_encode($p));
        foreach (['copias_lotes','copias_autorizacoes','copias_revisoes','copias_outbox','copias_execucoes'] as $t) $this->assertDatabaseCount($t,0);
    }
    public function test_isolamento_selecao_e_gabarito_incompleto(): void
    {
        $s=$this->servico();
        $this->recusa(404,fn()=>$s->catalogo($this->ator(4),5));
        $this->assertSame([3],array_column($s->catalogo($this->ator(4),6)['lista']['data'],'id'));
        $this->recusa(404,fn()=>$s->previa($this->ator(),5,[2,1]));
        foreach ([[],[2,2],['2'],[0],range(1,21)] as $ids) $this->recusa(422,fn()=>$s->previa($this->ator(),5,$ids));
        DB::table('gabarito_questoes')->where('gabarito_id',2)->delete(); $this->recusa(422,fn()=>$s->previa($this->ator(),5,[2]));
    }
    public function test_paginacao_papel_flags_e_revogacao(): void
    {
        $s=$this->servico();
        for ($id=10;$id<31;$id++) DB::table('provas')->insert(['id'=>$id,'espaco_id'=>5,'espaco_tipo'=>'escola','escola_id'=>1,'criado_por'=>2,
            'titulo'=>'Material fictício','disciplina'=>'Matemática','serie'=>'3','num_questoes'=>1,'num_alternativas'=>4]);
        $c=$s->catalogo($this->ator(),5); $this->assertSame(22,$c['lista']['total']); $this->assertCount(20,$c['lista']['data']);
        $this->assertCount(2,$s->catalogo($this->ator(),5,2)['lista']['data']);
        $this->assertSame(1,$s->catalogo($this->ator(4),6)['lista']['total']);
        config(['copias.interface_ativa'=>false]); $this->recusa(404,fn()=>$s->previa($this->ator(),5,[2]));
        config(['copias.interface_ativa'=>true]);
        $this->recusa(403,fn()=>$s->catalogo(Usuario::find(1)->noVinculo(3),6));
        DB::table('perfil_permissoes')->where('perfil','dir_escolar')->where('permissao','copia.autorizar_origem')->delete();
        $this->recusa(403,fn()=>$s->catalogo($this->ator(),5));
    }
    public function test_post_revalida_contexto_e_nao_aceita_ator_ou_destinatario(): void
    {
        $this->servico(); $u=Usuario::find(2); $pai=$u->createToken('api-token')->accessToken;
        $token=$u->createToken('vinculo-contexto',['vinculo:2','identidade:'.$pai->id])->plainTextToken;
        $h=['Authorization'=>'Bearer '.$token]; app('auth')->forgetGuards();
        $this->postJson('/api/v1/copias/materiais/previa',['espaco_id'=>5,'provas'=>[2]],$h)->assertOk()->assertJsonPath('data.autoriza_execucao',false);
        foreach (['vinculo_id'=>4,'destino_espaco_id'=>7,'autorizado_por'=>1] as $k=>$v) {
            app('auth')->forgetGuards(); $this->postJson('/api/v1/copias/materiais/previa',['espaco_id'=>5,'provas'=>[2],$k=>$v],$h)->assertUnprocessable();
        }
        DB::table('usuario_vinculos')->where('id',2)->update(['situacao'=>'encerrado']); app('auth')->forgetGuards();
        $this->postJson('/api/v1/copias/materiais/previa',['espaco_id'=>5,'provas'=>[2]],$h)->assertForbidden();
        $this->assertDatabaseCount('copias_lotes',0);
    }
}
