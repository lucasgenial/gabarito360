<?php
namespace Tests\Feature;

use App\Models\Usuario;
use App\Support\Permissao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SecretarioEscolarTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void
    {
        parent::setUp();
        config(['espacos.ativo' => true]);
        $this->travelTo(now()->startOfSecond());
        Permissao::limparCache();
        foreach ([1, 2] as $id) {
            Usuario::forceCreate(['id' => $id, 'perfil' => 'admin_rede', 'nome' => 'Pessoa fictícia', 'email' => $id.'@example.test', 'cpf' => str_pad((string) $id, 11, '0', STR_PAD_LEFT), 'password' => 'ficticio', 'ativo' => true]);
            DB::table('clientes')->insert(['id' => $id, 'tipo' => 'rede', 'nome' => 'Cliente', 'modalidade' => 'interno', 'situacao' => 'ativo']);
            DB::table('redes')->insert(['id' => $id, 'nome' => 'Rede', 'tipo' => 'estadual', 'uf' => 'BA']);
            DB::table('nucleos')->insert(['id' => $id, 'rede_id' => $id, 'nome' => 'Núcleo']);
            DB::table('escolas')->insert(['id' => $id, 'nucleo_id' => $id, 'nome' => 'Escola', 'inep' => '0000000'.$id, 'tipo_rede' => 'estadual']);
            foreach (['rede' => 0, 'nucleo' => 2, 'escola' => 4] as $tipo => $offset) {
                DB::table('espacos')->insert(['id' => $id + $offset, 'cliente_id' => $id, 'tipo' => $tipo, 'nome' => 'Espaço', 'situacao' => 'ativo', $tipo.'_id' => $id]);
            }
            DB::table('usuario_escopos')->insert(['usuario_id' => 1, 'escopo_tipo' => 'rede', 'escopo_id' => $id]);
            DB::table('turmas')->insert(['id' => $id, 'escola_id' => $id, 'espaco_id' => $id + 4, 'nome' => '3A', 'serie' => '3', 'turno' => 'tarde', 'ano_letivo' => 2026]);
            DB::table('alunos')->insert(['id' => $id, 'turma_id' => $id, 'espaco_id' => $id + 4, 'nome' => 'Fictício', 'matricula' => 'C-'.$id]);
            DB::table('provas')->insert(['id' => $id, 'escola_id' => $id, 'espaco_id' => $id + 4, 'criado_por' => 1,
                'titulo' => 'Prova', 'disciplina' => 'Teste', 'serie' => '3', 'num_questoes' => 1, 'num_alternativas' => 4]);
            DB::table('prova_turmas')->insert(['prova_id' => $id, 'turma_id' => $id, 'espaco_id' => $id + 4]);
        }
        $this->vinculo(1, 1, 5, 'dir_escolar');
        $this->vinculo(2, 1, 6, 'professor');
        $this->vinculo(3, 2, 5, 'professor');
        $this->vinculo(4, 2, 5, 'secretario_escolar');
        DB::table('vinculo_turmas')->insert(['vinculo_id' => 2, 'espaco_id' => 6, 'turma_id' => 2]);
    }

    private function vinculo(int $id, int $usuario, int $espaco, string $perfil): void
    {
        DB::table('usuario_vinculos')->insert(['id' => $id, 'usuario_id' => $usuario, 'espaco_id' => $espaco, 'perfil' => $perfil,
            'situacao' => 'ativo', 'inicio_em' => now()->subDay(), 'versao_autorizacao' => 1]);
    }

    private function entrar(int $v = 1, int $u = 1): void
    {
        Sanctum::actingAs(Usuario::findOrFail($u)->noVinculo($v));
    }

    public function test_secretario_administra_apenas_alunos_e_turmas_da_escola(): void
    {
        $this->entrar(4, 2);
        $this->getJson('/api/v1/turmas')->assertOk()->assertJsonCount(1, 'data.turmas')->assertJsonMissingPath('data.turmas.0.media_turma');
        $this->getJson('/api/v1/turmas/1')->assertOk()->assertJsonCount(1, 'data.alunos')
            ->assertJsonMissingPath('data.provas')->assertJsonMissingPath('data.media_turma')->assertJsonMissingPath('data.alunos.0.media_geral');
        $this->getJson('/api/v1/escolas')->assertOk()->assertJsonCount(1, 'data.escolas')->assertJsonMissingPath('data.escolas.0.total_provas');
        $this->getJson('/api/v1/escolas/1')->assertOk()->assertJsonMissingPath('data.provas')->assertJsonMissingPath('data.equipe');
        $this->getJson('/api/v1/alunos')->assertOk()->assertJsonCount(1, 'data.alunos');
        foreach (['turmas', 'alunos', 'escolas'] as $recurso) $this->getJson('/api/v1/'.$recurso.'/2')->assertNotFound();
        $dados = ['escola_id'=>1, 'nome'=>'Turma administrativa', 'serie'=>'4', 'turno'=>'tarde', 'ano_letivo'=>2026];
        $turma = $this->postJson('/api/v1/turmas', $dados)->assertCreated()->json('data.id');
        $this->postJson('/api/v1/turmas', array_replace($dados, ['escola_id'=>2]))->assertNotFound();
        $this->putJson('/api/v1/turmas/'.$turma, ['nome'=>'Turma editada'])->assertOk();
        $this->putJson('/api/v1/turmas/2', ['nome'=>'Invasão'])->assertNotFound();
        $this->postJson('/api/v1/turmas/'.$turma.'/toggle')->assertOk();
        $aluno = $this->postJson('/api/v1/alunos', ['turma_id'=>1,'nome'=>'Novo aluno fictício','matricula'=>'NOVO','data_nascimento'=>'2015-01-01'])->assertCreated()->json('data.id');
        $this->putJson('/api/v1/alunos/'.$aluno, ['nome'=>'Aluno editado'])->assertOk();
        $this->putJson('/api/v1/alunos/'.$aluno, ['turma_id'=>2])->assertNotFound();
        $this->postJson('/api/v1/alunos/'.$aluno.'/toggle')->assertOk();
        $this->assertDatabaseHas('turmas', ['id'=>$turma, 'espaco_id'=>5]);
    }

    public function test_nao_recebe_resultados_e_nem_eleva_poderes_por_extras(): void
    {
        DB::table('vinculo_permissoes')->insert(['vinculo_id'=>4, 'permissao'=>'resultado.ver', 'efeito'=>'conceder']);
        $this->entrar(4, 2);
        foreach (['provas','usuarios','cartoes/1','alunos/1/resultados/1','relatorios/turma/1','dashboard/admin','dashboard/secretaria'] as $rota) {
            $this->getJson('/api/v1/'.$rota)->assertForbidden();
        }
        $this->postJson('/api/v1/turmas/1/professores', ['usuario_id'=>1,'vinculo_id'=>1])->assertForbidden();
        $this->postJson('/api/v1/usuarios', [])->assertForbidden();
        $this->putJson('/api/v1/escolas/1', ['nome'=>'Alterada'])->assertForbidden();
        $this->assertFalse(Permissao::pode(Usuario::find(2)->noVinculo(4), 'cliente.gerir'));
        DB::table('usuario_vinculos')->where('id',4)->update(['situacao'=>'suspenso','versao_autorizacao'=>2]);
        $this->getJson('/api/v1/turmas')->assertForbidden();
    }

    public function test_diretor_cadastra_secretario_contextual_e_coordenador_nao_administra(): void
    {
        Mail::fake();
        $this->entrar();
        $r = $this->postJson('/api/v1/usuarios', ['nome'=>'Secretaria fictícia','email'=>'secretaria@example.test','cpf'=>'00000000099','perfil'=>'secretario_escolar','escola_id'=>1])->assertCreated();
        $id=$r->json('data.id');
        $this->assertDatabaseHas('usuarios', ['id'=>$id,'perfil'=>'secretario_escolar']);
        $this->assertDatabaseHas('usuario_vinculos', ['usuario_id'=>$id,'espaco_id'=>5,'perfil'=>'secretario_escolar','situacao'=>'convidado']);
        $this->assertDatabaseMissing('usuario_escopos', ['usuario_id'=>$id]);
        $this->assertSame([], Permissao::efetivas(Usuario::find($id)));
        $this->putJson('/api/v1/usuarios/2', ['vinculo_id'=>4,'perfil'=>'secretario_escolar','ativo'=>true])->assertOk();
        $this->putJson('/api/v1/usuarios/2/permissoes', ['vinculo_id'=>4,'itens'=>[['permissao'=>'resultado.ver','estado'=>'conceder']]])->assertForbidden();
        $this->vinculo(9, 1, 5, 'coordenador');
        $this->entrar(9);
        $this->postJson('/api/v1/usuarios/2/toggle', ['vinculo_id'=>4])->assertForbidden();
        $this->assertDatabaseHas('usuario_vinculos', ['id'=>4,'situacao'=>'ativo']);
    }

    public function test_hierarquia_e_convite_de_identidade_existente_preservam_outros_papeis(): void
    {
        $this->vinculo(5, 1, 1, 'admin_rede');
        $this->vinculo(6, 1, 3, 'dir_nucleo');
        foreach ([1,5,6] as $v) $this->assertTrue(\App\Support\Cadastro::podeGerirPerfil(Usuario::find(1)->noVinculo($v), 'secretario_escolar'));
        $this->vinculo(7, 1, 6, 'dir_escolar');
        $convite=app(\App\Services\ConviteVinculo::class)->emitir(Usuario::find(1)->noVinculo(7), '2@example.test', 6, 'secretario_escolar');
        $this->assertNotNull($convite);
        $this->assertDatabaseHas('usuarios', ['id'=>2,'perfil'=>'admin_rede']);
        $this->assertDatabaseHas('usuario_vinculos', ['usuario_id'=>2,'espaco_id'=>6,'perfil'=>'secretario_escolar','situacao'=>'convidado']);
        $this->assertDatabaseHas('usuario_vinculos', ['id'=>4,'situacao'=>'ativo']);
    }
}
