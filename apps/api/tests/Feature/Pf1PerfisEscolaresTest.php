<?php
namespace Tests\Feature;
use App\Models\Usuario;
use App\Support\CatalogoPermissoes;
use App\Support\Permissao;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

class Pf1PerfisEscolaresTest extends SecretarioEscolarTest
{
    private function pf(string $perfil, int $id=10): void
    {
        if (!DB::table('usuario_vinculos')->where('id',$id)->exists()) DB::table('usuario_vinculos')->insert([
            'id'=>$id,'usuario_id'=>2,'espaco_id'=>5,'perfil'=>$perfil,'situacao'=>'ativo','inicio_em'=>now()->subDay(),'versao_autorizacao'=>1]);
        app('auth')->forgetGuards();
        Sanctum::actingAs(Usuario::findOrFail(2)->noVinculo($id));
    }
    public function test_matriz_preserva_secretario_e_novos_papeis_exigem_contexto(): void
    {
        $c=CatalogoPermissoes::porPerfil();
        $this->assertCount(11,$c['vice_dir_escolar']['padrao']);
        $this->assertCount(18,$c['vice_dir_escolar']['extra']);
        $this->assertCount(8,$c['secretario_escolar']['padrao']);
        $this->assertSame([], $c['auxiliar_secretaria']['extra']);
        $this->assertSame(['escola.ver','turma.ver','aluno.ver','aluno.gerir'],$c['auxiliar_secretaria']['padrao']);
        foreach(['vice_dir_escolar','auxiliar_secretaria'] as $p){
            $u=Usuario::findOrFail(2);$u->perfil=$p;
            $this->assertSame([],Permissao::efetivas($u));
        }
    }
    public function test_auxiliar_so_cadastro_sem_status_desempenho_ou_extras(): void
    {
        $this->pf('auxiliar_secretaria');
        $this->getJson('/api/v1/turmas')->assertOk()->assertJsonCount(1,'data.turmas')->assertJsonMissingPath('data.turmas.0.media_turma');
        $this->getJson('/api/v1/turmas/1')->assertOk()->assertJsonMissingPath('data.provas')->assertJsonMissingPath('data.media_turma');
        $this->getJson('/api/v1/escolas/1')->assertOk()->assertJsonMissingPath('data.equipe')->assertJsonMissingPath('data.provas');
        $this->getJson('/api/v1/alunos/2')->assertNotFound();
        foreach(['provas','usuarios','relatorios/turma/1','alunos/1/resultados/1','dashboard/dir-escolar'] as $rota) $this->getJson('/api/v1/'.$rota)->assertForbidden();
        $this->postJson('/api/v1/turmas',[])->assertForbidden();
        $this->postJson('/api/v1/alunos/1/toggle')->assertForbidden();
        $this->putJson('/api/v1/alunos/1',['ativo'=>false])->assertForbidden();
        $this->postJson('/api/v1/alunos',['ativo'=>false])->assertForbidden();
        $this->putJson('/api/v1/alunos/1',['nome'=>'Cadastro fictício atualizado'])->assertOk();
        DB::table('vinculo_permissoes')->insert(['vinculo_id'=>10,'permissao'=>'resultado.ver','efeito'=>'conceder']);
        $this->getJson('/api/v1/alunos/1/resultados/1')->assertForbidden();
        $this->assertDatabaseHas('alunos',['id'=>1,'ativo'=>true]);
    }
    public function test_vice_acompanha_escola_sem_mutacoes_por_padrao(): void
    {
        $this->pf('vice_dir_escolar');
        $this->getJson('/api/v1/provas')->assertOk()->assertJsonCount(1,'data.provas');
        $this->getJson('/api/v1/turmas')->assertOk()->assertJsonCount(1,'data.turmas');
        $this->getJson('/api/v1/usuarios')->assertOk();
        $this->getJson('/api/v1/provas/2')->assertNotFound();
        foreach(['provas','turmas','usuarios'] as $r) $this->postJson('/api/v1/'.$r,[])->assertForbidden();
        $this->putJson('/api/v1/alunos/1',['nome'=>'Não alterar'])->assertForbidden();
        $this->putJson('/api/v1/usuarios/1/permissoes',['vinculo_id'=>1,'itens'=>[['permissao'=>'prova.criar','estado'=>'conceder']]])->assertForbidden();
        $this->getJson('/api/v1/dashboard/dir-escolar')->assertOk();
    }
    public function test_concessao_real_hierarquia_retirada_e_vigencia(): void
    {
        $this->pf('vice_dir_escolar');
        app('auth')->forgetGuards();Sanctum::actingAs(Usuario::findOrFail(1)->noVinculo(1));
        $this->putJson('/api/v1/usuarios/2/permissoes',['vinculo_id'=>10,'itens'=>[['permissao'=>'usuario.gerir','estado'=>'conceder']]])->assertOk();
        $this->assertDatabaseHas('vinculo_eventos',['vinculo_id'=>10,'acao'=>'conceder','ator_id'=>1]);
        $this->pf('vice_dir_escolar');
        $this->assertTrue(\App\Support\Cadastro::podeGerirPerfil(Usuario::find(2)->noVinculo(10),'auxiliar_secretaria'));
        $this->assertFalse(\App\Support\Cadastro::podeGerirPerfil(Usuario::find(2)->noVinculo(10),'vice_dir_escolar'));
        $this->postJson('/api/v1/usuarios/1/toggle',['vinculo_id'=>1])->assertForbidden();
        $this->postJson('/api/v1/usuarios/2/toggle',['vinculo_id'=>4])->assertForbidden();
        DB::table('vinculo_permissoes')->where('vinculo_id',10)->update(['valida_ate'=>now()->subDay()->toDateString()]);
        $this->postJson('/api/v1/usuarios',[])->assertForbidden();
        DB::table('usuario_vinculos')->where('id',10)->update(['situacao'=>'suspenso']);
        $this->getJson('/api/v1/provas')->assertForbidden();
    }
    public function test_vice_e_professor_da_mesma_pessoa_nao_somam_poderes(): void
    {
        $this->pf('vice_dir_escolar');
        foreach(['prova.criar','prova.editar','gabarito.editar'] as $c) DB::table('vinculo_permissoes')->insert(['vinculo_id'=>10,'permissao'=>$c,'efeito'=>'conceder']);
        $id=$this->postJson('/api/v1/provas',['escola_id'=>1,'titulo'=>'Institucional fictícia','disciplina'=>'Teste','serie'=>'3','num_questoes'=>1,'num_alternativas'=>4,'turma_ids'=>[1]])->assertCreated()->json('data.id');
        $this->assertDatabaseHas('provas',['id'=>$id,'perfil_criacao'=>'vice_dir_escolar','criado_por'=>2]);
        DB::table('vinculo_turmas')->insert(['vinculo_id'=>3,'espaco_id'=>5,'turma_id'=>1]);
        app('auth')->forgetGuards();Sanctum::actingAs(Usuario::find(2)->noVinculo(3));
        $this->getJson('/api/v1/provas/'.$id)->assertOk()->assertJsonPath('data.pode_copiar',false)->assertJsonPath('data.pode_editar',false);
        $this->postJson('/api/v1/provas/'.$id.'/copiar',['titulo'=>'Não copiar','chave'=>(string)\Illuminate\Support\Str::uuid()])->assertNotFound();
        $this->putJson('/api/v1/provas/'.$id,['titulo'=>'Não editar'])->assertNotFound();
        DB::table('vinculo_turmas')->where('vinculo_id',3)->delete();
        $this->getJson('/api/v1/provas/'.$id)->assertNotFound();
        $this->pf('vice_dir_escolar');$this->getJson('/api/v1/provas/'.$id)->assertOk();
        $this->assertDatabaseHas('usuarios',['id'=>2,'perfil'=>'admin_rede']);
    }
    public function test_convites_novos_papeis_preservam_conta_e_exigem_aceite(): void
    {
        $gestor=Usuario::find(1)->noVinculo(1);
        foreach(['vice_dir_escolar','auxiliar_secretaria'] as $p){
            $c=app(\App\Services\ConviteVinculo::class)->emitir($gestor,'2@example.test',5,$p);
            $this->assertNotNull($c);
            $this->assertDatabaseHas('usuario_vinculos',['id'=>$c['vinculo_id'],'situacao'=>'convidado']);
            app(\App\Services\ConviteVinculo::class)->responder(Usuario::find(2),$c['convite_id'],$c['token'],true);
            $this->assertDatabaseHas('usuario_vinculos',['id'=>$c['vinculo_id'],'situacao'=>'ativo']);
        }
        $this->assertDatabaseHas('usuario_vinculos',['id'=>4,'situacao'=>'ativo']);
        $this->assertDatabaseHas('usuarios',['id'=>2,'perfil'=>'admin_rede']);
    }

    public function test_arquivos_exportacoes_e_mutacoes_de_correcao_exigem_extras():void
    {
        $this->pf('vice_dir_escolar');
        foreach(['provas/1/cartoes/pdf','provas/1/resultados/pdf','alunos/1/resultados/1/pdf'] as $r)$this->getJson('/api/v1/'.$r)->assertForbidden();
        $this->postJson('/api/v1/provas/1/gabarito',['questoes'=>[]])->assertForbidden();
        $this->postJson('/api/v1/provas/1/cartoes',[])->assertForbidden();
        DB::table('vinculo_permissoes')->insert(['vinculo_id'=>10,'permissao'=>'resultado.ver','efeito'=>'retirar']);
        $this->getJson('/api/v1/alunos/1/resultados/1')->assertForbidden();
        $this->getJson('/api/v1/dashboard/dir-escolar')->assertForbidden();
    }
    public function test_gestao_nao_concede_o_que_nao_possui_nem_extras_proibidos():void
    {
        $this->pf('vice_dir_escolar');$this->pf('auxiliar_secretaria',11);
        app('auth')->forgetGuards();Sanctum::actingAs(Usuario::find(1)->noVinculo(1));
        $this->putJson('/api/v1/usuarios/2/permissoes',['vinculo_id'=>10,'itens'=>[['permissao'=>'prova.criar','estado'=>'conceder']]])->assertForbidden();
        $this->putJson('/api/v1/usuarios/2/permissoes',['vinculo_id'=>11,'itens'=>[['permissao'=>'resultado.ver','estado'=>'conceder']]])->assertForbidden();
        $this->assertDatabaseMissing('vinculo_permissoes',['vinculo_id'=>10,'permissao'=>'prova.criar']);
    }
    public function test_inicio_futuro_e_encerramento_bloqueiam_contexto_existente():void
    {
        $this->pf('vice_dir_escolar');
        DB::table('usuario_vinculos')->where('id',10)->update(['inicio_em'=>now()->addDay()]);
        $this->getJson('/api/v1/provas')->assertForbidden();
        DB::table('usuario_vinculos')->where('id',10)->update(['inicio_em'=>now()->subDay(),'fim_em'=>now()->subSecond()]);
        $this->getJson('/api/v1/provas')->assertForbidden();
    }
}
