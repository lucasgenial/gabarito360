<?php
namespace Tests\Feature;
use App\Models\Usuario;
use App\Services\SelecaoCadastroCopia;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\{DB,Schema};
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class SelecaoCadastroCopiaTest extends TestCase
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
    private function servico(): SelecaoCadastroCopia { config(['copias.interface_ativa'=>true]); return new SelecaoCadastroCopia; }
    private function recusa(int $status,callable $f): void
    { try { $f(); $this->fail('Deveria recusar.'); } catch(HttpException $e) { $this->assertSame($status,$e->getStatusCode()); } }
    public function test_catalogo_restringe_turma_e_campos_antes_das_contagens(): void
    {
        $s=$this->servico(); $c=$s->catalogo($this->ator(),5); $this->assertSame([1],array_column($c['turmas']['data'],'id'));
        $this->assertNull($c['turma']); $this->assertSame(0,$c['alunos']['total']);
        $c=$s->catalogo($this->ator(),5,1); $this->assertSame([1],array_column($c['alunos']['data'],'id'));
        $this->assertSame(['id','nome','matricula'],array_keys($c['alunos']['data'][0]));
        $this->recusa(404,fn()=>$s->catalogo($this->ator(),5,2));
        $this->recusa(404,fn()=>$s->catalogo($this->ator(4),5,1));
        $this->assertSame([2],array_column($s->catalogo($this->ator(4),6,2)['alunos']['data'],'id'));
        DB::table('turmas')->where('id',1)->update(['escola_id'=>2]);
        $this->recusa(404,fn()=>$s->catalogo($this->ator(),5,1));
    }
    public function test_paginacao_independente_e_sem_alunos_de_outra_turma(): void
    {
        $s=$this->servico();
        for($id=10;$id<31;$id++) {
            DB::table('turmas')->insert(['id'=>$id,'espaco_id'=>5,'espaco_tipo'=>'escola','escola_id'=>1,'nome'=>'Turma','serie'=>'3','turno'=>'manha','ano_letivo'=>2026]);
            DB::table('alunos')->insert(['id'=>$id,'espaco_id'=>5,'espaco_tipo'=>'escola','turma_id'=>1,'nome'=>'Aluno','matricula'=>'M-'.$id]);
        }
        $c=$s->catalogo($this->ator(),5,1); $this->assertSame(22,$c['turmas']['total']); $this->assertCount(20,$c['turmas']['data']);
        $this->assertSame(22,$c['alunos']['total']); $this->assertCount(20,$c['alunos']['data']);
        $c=$s->catalogo($this->ator(),5,1,2,2); $this->assertCount(2,$c['turmas']['data']); $this->assertCount(2,$c['alunos']['data']);
        $this->assertSame(0,$s->catalogo($this->ator(),5,10)['alunos']['total']);
    }
    public function test_previa_minima_tipo_simulado_e_dependencias_sem_efeitos(): void
    {
        $s=$this->servico(); $p=$s->previa($this->ator(),5,'pessoal',[1],[1]);
        $this->assertFalse($p['autoriza_execucao']); $this->assertTrue($p['evidencia_identificados_obrigatoria']);
        foreach(['contexto_origem','vinculo_id','versao_acesso','data_nascimento','foto_path','nome_responsavel'] as $campo) $this->assertStringNotContainsString('"'.$campo.'"',json_encode($p));
        $this->assertSame('2010-01-01',$s->previa($this->ator(),5,'escola',[1],[1])['alunos'][0]['dados']['data_nascimento']);
        $this->assertSame([],$s->previa($this->ator(),5,'pessoal',[1],[])['alunos']);
        $p=$s->previa($this->ator(1),7,'escola',[3],[3]); $this->assertCount(1,$p['pendencias']);
        $this->assertArrayNotHasKey('data_nascimento',$p['alunos'][0]['dados']);
        foreach(['copias_lotes','copias_autorizacoes','copias_evidencias','copias_outbox','copias_execucoes','copias_recebimentos'] as $t) $this->assertDatabaseCount($t,0);
        foreach([[[],[1]],[[1,2],[]],[[1],[1,1]],[[1],range(1,21)]] as [$t,$a]) $this->recusa(422,fn()=>$s->previa($this->ator(),5,'pessoal',$t,$a));
        $this->recusa(404,fn()=>$s->previa($this->ator(),5,'pessoal',[1],[2]));
        DB::table('turmas')->insert(['id'=>10,'espaco_id'=>5,'espaco_tipo'=>'escola','escola_id'=>1,'nome'=>'Outra','serie'=>'3','turno'=>'manha','ano_letivo'=>2026]);
        $this->recusa(422,fn()=>$s->previa($this->ator(),5,'pessoal',[10],[1]));
    }
    public function test_flags_papeis_revogacao_e_contexto(): void
    {
        $s=$this->servico();
        $this->recusa(403,fn()=>$s->catalogo(Usuario::find(1)->noVinculo(3),6));
        config(['copias.interface_ativa'=>false]); $this->recusa(404,fn()=>$s->catalogo($this->ator(),5));
        config(['copias.interface_ativa'=>true]);
        DB::table('perfil_permissoes')->where('perfil','dir_escolar')->where('permissao','copia.autorizar_origem')->delete();
        $this->recusa(403,fn()=>$s->previa($this->ator(),5,'pessoal',[1],[1]));
        DB::table('usuario_vinculos')->where('id',1)->update(['situacao'=>'encerrado']);
        $this->recusa(403,fn()=>$s->catalogo($this->ator(1),7,3));
    }
    public function test_http_nao_aceita_atores_destino_real_ou_dependencia_implicita(): void
    {
        $this->servico(); $u=Usuario::find(2); $pai=$u->createToken('api-token')->accessToken;
        $h=['Authorization'=>'Bearer '.$u->createToken('vinculo-contexto',['vinculo:2','identidade:'.$pai->id])->plainTextToken];
        $d=['espaco_id'=>5,'tipo_destino'=>'pessoal','turmas'=>[1],'alunos'=>[1]];
        app('auth')->forgetGuards(); $this->postJson('/api/v1/copias/cadastros/previa',$d,$h)->assertOk()->assertJsonPath('data.autoriza_execucao',false)->assertJsonMissingPath('data.contexto_origem');
        foreach(['vinculo_id'=>1,'ator_id'=>1,'destino_espaco_id'=>7] as $k=>$v) {
            app('auth')->forgetGuards(); $this->postJson('/api/v1/copias/cadastros/previa',$d+[$k=>$v],$h)->assertUnprocessable();
        }
        unset($d['turmas']); app('auth')->forgetGuards(); $this->postJson('/api/v1/copias/cadastros/previa',$d,$h)->assertUnprocessable();
        app('auth')->forgetGuards(); $this->getJson('/api/v1/copias/cadastros?espaco_id=5&turma_id=1',$h)->assertOk()->assertJsonPath('data.alunos.total',1);
        $this->assertDatabaseCount('copias_lotes',0);
    }
}
