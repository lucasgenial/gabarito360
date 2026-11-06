<?php
namespace Tests\Feature;

use App\Models\Usuario;
use App\Services\{GestaoDisciplinas,VinculoDocenteComposto};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VinculoDocenteCompostoTest extends TestCase
{
    use \Tests\Support\BancoCurriculoDescartavel,\Tests\Support\CenarioDisciplinas;
    private function disciplina(string $nome='Matemática'): int
    {
        return (int)app(GestaoDisciplinas::class)->salvar(Usuario::findOrFail(1)->noVinculo(1),['nome'=>$nome,'ativo'=>true])->id;
    }
    private function dados(int $disciplina,array $extra=[]):array
    {
        return array_replace(['disciplina_id'=>$disciplina,'professor_vinculo_id'=>3,'chave_idempotencia'=>(string)Str::uuid()],$extra);
    }
    private function url(int $turma=1):string {return '/api/v1/turmas/'.$turma.'/vinculos-docentes';}

    public function test_cria_todos_os_vinculos_e_replay_duravel_nao_ressuscita_acesso():void
    {
        DB::table('vinculo_turmas')->where('vinculo_id',3)->delete();
        $d=$this->dados($this->disciplina());
        $r=$this->postJson($this->url(),$d)->assertCreated()->assertJsonPath('data.repetido',false)
            ->assertJsonPath('data.efeitos.componente_criado',true)->assertJsonPath('data.efeitos.indicacao_criada_ou_reativada',true)->assertJsonPath('data.efeitos.acesso_concedido',true);
        $this->assertDatabaseCount('turma_disciplinas',1);$this->assertDatabaseCount('disciplina_professores',1);
        $this->assertDatabaseCount('docente_atribuicoes',1);$this->assertDatabaseCount('vinculos_docentes_compostos',1);
        $this->assertDatabaseHas('vinculo_turmas',['vinculo_id'=>3,'turma_id'=>1,'espaco_id'=>5]);
        $this->assertDatabaseHas('vinculo_eventos',['vinculo_id'=>3,'acao'=>'turma_adicionada']);
        DB::table('vinculo_turmas')->where('vinculo_id',3)->delete();
        $eventos=DB::table('disciplina_eventos')->count();
        $this->travel(1)->days();
        $this->postJson($this->url(),$d)->assertCreated()->assertJsonPath('data.repetido',true)
            ->assertJsonPath('data.atribuicao_id',$r->json('data.atribuicao_id'))->assertJsonPath('data.inicio_em',$r->json('data.inicio_em'));
        $this->assertSame($eventos,DB::table('disciplina_eventos')->count());$this->assertDatabaseCount('vinculo_turmas',0);
        $this->postJson($this->url(),array_replace($d,['chave_idempotencia'=>strtoupper($d['chave_idempotencia'])]))->assertCreated()->assertJsonPath('data.repetido',true);
        $this->postJson($this->url(),array_replace($d,['professor_vinculo_id'=>5]))->assertConflict();
    }

    public function test_reaproveita_componente_indicacao_e_acesso_sem_eventos_falsos():void
    {
        $id=$this->disciplina();$u=Usuario::findOrFail(1)->noVinculo(1);$g=app(GestaoDisciplinas::class);
        $g->adicionarComponente($u,1,$id);$g->indicar($u,$id,['vinculo_id'=>3,'ativo'=>true]);
        $eventos=DB::table('disciplina_eventos')->count();$acessos=DB::table('vinculo_eventos')->count();
        $this->postJson($this->url(),$this->dados($id))->assertCreated()->assertJsonPath('data.efeitos.componente_criado',false)
            ->assertJsonPath('data.efeitos.indicacao_criada_ou_reativada',false)->assertJsonPath('data.efeitos.acesso_concedido',false);
        $this->assertSame($eventos+1,DB::table('disciplina_eventos')->count());$this->assertSame($acessos,DB::table('vinculo_eventos')->count());
    }

    public function test_falha_tardia_reverte_componente_indicacao_acesso_eventos_e_recibo():void
    {
        $id=$this->disciplina();DB::table('vinculo_turmas')->where('vinculo_id',3)->delete();
        $eventos=DB::table('disciplina_eventos')->count();$versao=DB::table('usuario_vinculos')->where('id',3)->value('versao_autorizacao');
        $armado=true;
        DB::listen(function($q)use(&$armado){
            if($armado && preg_match('/^insert into [`"]vinculos_docentes_compostos[`"]/', $q->sql)){
                $armado=false;throw new \RuntimeException('Falha fictícia após inserir o recibo');
            }
        });
        try{app(VinculoDocenteComposto::class)->executar(Usuario::findOrFail(1)->noVinculo(1),1,$this->dados($id));$this->fail('Deve reverter');}
        catch(\RuntimeException $e){$this->assertSame('Falha fictícia após inserir o recibo',$e->getMessage());}
        finally{$armado=false;}
        foreach(['turma_disciplinas','disciplina_professores','vinculo_turmas','docente_atribuicoes','vinculos_docentes_compostos','vinculo_eventos']as$t)$this->assertDatabaseCount($t,0);
        $this->assertSame($eventos,DB::table('disciplina_eventos')->count());$this->assertSame($versao,DB::table('usuario_vinculos')->where('id',3)->value('versao_autorizacao'));
    }

    public function test_opcoes_completas_sem_contas_documentos_ou_pre_requisito_de_indicacao():void
    {
        for($i=0;$i<25;$i++)$this->disciplina('Disciplina '.$i);
        DB::table('vinculo_turmas')->where('vinculo_id',3)->delete();
        $r=$this->getJson('/api/v1/turmas/1/vinculo-docente/opcoes')->assertOk()->assertJsonPath('data.completo',true)->assertJsonCount(25,'data.disciplinas')->assertJsonCount(2,'data.professores');
        foreach($r->json('data.professores')as$p)$this->assertSame(['vinculo_id','nome'],array_keys($p));
        $r->assertJsonMissingPath('data.professores.0.email')->assertJsonMissingPath('data.professores.0.cpf');
        $this->assertDatabaseCount('turma_disciplinas',0);$this->assertDatabaseCount('disciplina_professores',0);
    }

    public function test_programada_ativa_inativa_e_outra_escola_nao_sao_disponiveis():void
    {
        $id=$this->disciplina();$d=$this->dados($id);$this->postJson($this->url(),$d)->assertCreated();
        $this->getJson('/api/v1/turmas/1/vinculo-docente/opcoes')->assertOk()->assertJsonCount(0,'data.disciplinas');
        DB::table('docente_atribuicoes')->update(['inicio_em'=>'2027-01-01']);
        $this->postJson($this->url(),$this->dados($id))->assertConflict();
        $this->getJson('/api/v1/turmas/1/vinculo-docente/opcoes')->assertOk()->assertJsonCount(0,'data.disciplinas');
        DB::table('docente_atribuicoes')->update(['situacao'=>'encerrada','fim_em'=>'2026-11-04']);
        DB::table('turma_disciplinas')->update(['ativo'=>false]);
        $this->postJson($this->url(),$this->dados($id))->assertUnprocessable();
        $this->postJson($this->url(2),$this->dados($id))->assertNotFound();
    }

    public function test_perfis_retirada_permissao_e_campos_arbitrarios_nao_criam_autoridade():void
    {
        $id=$this->disciplina();
        $this->postJson($this->url(),$this->dados($id,['espaco_id'=>5]))->assertUnprocessable();
        DB::table('vinculo_permissoes')->insert(['vinculo_id'=>1,'permissao'=>'turma.vincular_professor','efeito'=>'retirar']);
        $this->postJson($this->url(),$this->dados($id))->assertForbidden();
        app('auth')->forgetGuards();Sanctum::actingAs(Usuario::findOrFail(1)->noVinculo(4));
        $this->postJson($this->url(),$this->dados($id))->assertForbidden();
        $this->assertDatabaseCount('vinculos_docentes_compostos',0);$this->assertDatabaseCount('docente_atribuicoes',0);
    }

    public function test_nao_autoconcede_e_rejeita_vinculo_suspenso_futuro_ou_de_outra_escola():void
    {
        $id=$this->disciplina();
        DB::table('usuario_vinculos')->insert(['id'=>8,'usuario_id'=>1,'espaco_id'=>5,'perfil'=>'professor','situacao'=>'ativo','inicio_em'=>now()->subDay()]);
        $this->postJson($this->url(),$this->dados($id,['professor_vinculo_id'=>8]))->assertForbidden();
        DB::table('usuario_vinculos')->where('id',3)->update(['situacao'=>'suspenso']);
        $this->postJson($this->url(),$this->dados($id))->assertUnprocessable();
        DB::table('usuario_vinculos')->where('id',3)->update(['situacao'=>'ativo','inicio_em'=>now()->addDay()]);
        $this->postJson($this->url(),$this->dados($id))->assertUnprocessable();
        $this->postJson($this->url(),$this->dados($id,['professor_vinculo_id'=>2]))->assertNotFound();
        $this->assertDatabaseCount('turma_disciplinas',0);$this->assertDatabaseCount('disciplina_professores',0);
    }

    public function test_fim_conhecido_do_vinculo_limita_atribuicao_sem_criar_acesso_alem_da_vigencia():void
    {
        $id=$this->disciplina();DB::table('usuario_vinculos')->where('id',3)->update(['fim_em'=>'2026-12-01 03:00:00']);
        $this->postJson($this->url(),$this->dados($id))->assertCreated()->assertJsonPath('data.fim_em','2026-12-01');
        $this->assertDatabaseHas('docente_atribuicoes',['vinculo_id'=>3,'fim_em'=>'2026-12-01']);
    }

    public function test_recibo_e_imutavel_e_revogacao_impede_replay():void
    {
        $d=$this->dados($this->disciplina());$this->postJson($this->url(),$d)->assertCreated();
        DB::table('vinculo_permissoes')->insert(['vinculo_id'=>1,'permissao'=>'disciplina.gerir','efeito'=>'retirar']);
        $this->postJson($this->url(),$d)->assertForbidden();
        $this->expectException(\Illuminate\Database\QueryException::class);DB::table('vinculos_docentes_compostos')->delete();
    }
}
