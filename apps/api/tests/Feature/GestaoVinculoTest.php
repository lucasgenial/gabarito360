<?php
namespace Tests\Feature;

use App\Models\Usuario;
use App\Support\Permissao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GestaoVinculoTest extends TestCase
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

    public function test_gestao_lista_papel_local_e_suspende_so_vinculo_alvo(): void
    {
        $this->vinculo(4, 2, 6, 'dir_escolar');
        $this->entrar();
        $this->getJson('/api/v1/usuarios')->assertOk()->assertJsonCount(1, 'data.usuarios')
            ->assertJsonPath('data.usuarios.0.perfil', 'professor')->assertJsonPath('data.usuarios.0.vinculo_id', 3);
        $this->postJson('/api/v1/usuarios/2/toggle', ['vinculo_id' => 4])->assertNotFound();
        $this->postJson('/api/v1/usuarios/2/toggle', ['vinculo_id' => 3])->assertOk()->assertJsonPath('data.ativo', false);
        $this->assertDatabaseHas('usuario_vinculos', ['id' => 3, 'situacao' => 'suspenso', 'versao_autorizacao' => 2]);
        $this->assertDatabaseHas('usuario_vinculos', ['id' => 4, 'situacao' => 'ativo', 'versao_autorizacao' => 1]);
        $this->assertDatabaseHas('usuarios', ['id' => 2, 'ativo' => 1, 'perfil' => 'admin_rede']);
        $this->postJson('/api/v1/usuarios/2/toggle', ['vinculo_id' => 3])->assertOk()->assertJsonPath('data.ativo', true);
        $this->assertDatabaseCount('vinculo_eventos', 2);
    }

    public function test_ambiguo_exige_vinculo_e_nao_edita_identidade_ou_proprio_acesso(): void
    {
        $this->vinculo(4, 2, 5, 'coordenador');
        $this->entrar();
        $this->postJson('/api/v1/usuarios/2/toggle')->assertUnprocessable();
        $this->putJson('/api/v1/usuarios/2', ['vinculo_id' => 3, 'nome' => 'Alterado', 'ativo' => false])->assertUnprocessable();
        $this->putJson('/api/v1/usuarios/2', ['vinculo_id' => 3, 'email' => 'outro@example.test'])->assertUnprocessable();
        $this->putJson('/api/v1/usuarios/2', ['vinculo_id' => 3, 'escola_id' => 2])->assertUnprocessable();
        $this->postJson('/api/v1/usuarios/1/toggle', ['vinculo_id' => 1])->assertForbidden();
        $this->postJson('/api/v1/usuarios/2/link-senha', ['vinculo_id' => 3])->assertUnprocessable();
        $this->assertDatabaseHas('usuarios', ['id' => 2, 'nome' => 'Pessoa fictícia', 'ativo' => 1]);
        $this->assertDatabaseCount('vinculo_eventos', 0);
    }

    public function test_permissoes_isoladas_e_lote_invalido_reverte_tudo(): void
    {
        $this->vinculo(4, 2, 6, 'professor');
        $this->entrar();
        $url = '/api/v1/usuarios/2/permissoes';
        $this->putJson($url, ['vinculo_id' => 3, 'itens' => [['permissao' => 'prova.ver', 'estado' => 'retirar']]])->assertOk();
        $this->assertFalse(Permissao::pode(Usuario::find(2)->noVinculo(3), 'prova.ver'));
        $this->assertTrue(Permissao::pode(Usuario::find(2)->noVinculo(4), 'prova.ver'));
        $this->assertDatabaseCount('usuario_permissoes', 0);
        $this->getJson($url.'?vinculo_id=3')->assertOk()->assertJsonPath('data.usuario.perfil', 'professor')->assertJsonCount(1, 'data.historico');
        $this->putJson($url, ['vinculo_id' => 3, 'itens' => [
            ['permissao' => 'prova.ver', 'estado' => 'padrao'], ['permissao' => 'rede.configurar', 'estado' => 'conceder'],
        ]])->assertForbidden();
        $this->assertDatabaseHas('vinculo_permissoes', ['vinculo_id' => 3, 'permissao' => 'prova.ver', 'efeito' => 'retirar']);
        $this->assertDatabaseCount('vinculo_eventos', 1);
    }

    public function test_turmas_e_equipes_usam_perfil_do_vinculo_sem_escopo_legado(): void
    {
        $this->vinculo(4, 2, 6, 'professor');
        DB::table('vinculo_turmas')->insert(['vinculo_id' => 4, 'espaco_id' => 6, 'turma_id' => 2]);
        $this->entrar();
        $this->postJson('/api/v1/turmas/1/professores', ['usuario_id' => 2, 'vinculo_id' => 3])->assertCreated();
        $this->getJson('/api/v1/turmas/1/professores')->assertOk()->assertJsonCount(1, 'data.professores')->assertJsonPath('data.professores.0.perfil', 'professor');
        $this->getJson('/api/v1/usuarios/2/turmas?vinculo_id=3')->assertOk()->assertJsonCount(1, 'data.turmas')->assertJsonPath('data.turmas.0.id', 1);
        $this->getJson('/api/v1/escolas/1')->assertOk()->assertJsonFragment(['vinculo_id' => 3, 'perfil' => 'professor']);
        $this->deleteJson('/api/v1/turmas/1/professores/2', ['vinculo_id' => 4])->assertNotFound();
        $this->deleteJson('/api/v1/turmas/1/professores/2', ['vinculo_id' => 3])->assertOk();
        $this->assertDatabaseMissing('vinculo_turmas', ['vinculo_id' => 3, 'turma_id' => 1]);
        $this->assertDatabaseHas('vinculo_turmas', ['vinculo_id' => 4, 'turma_id' => 2]);
    }

    public function test_encerrado_vencido_e_hierarquia_suspensa_nao_sao_reativados(): void
    {
        $this->entrar();
        DB::table('usuario_vinculos')->where('id', 3)->update(['situacao' => 'encerrado']);
        $this->postJson('/api/v1/usuarios/2/toggle', ['vinculo_id' => 3])->assertUnprocessable();
        DB::table('usuario_vinculos')->where('id', 3)->update(['situacao' => 'suspenso', 'fim_em' => now()->subMinute()]);
        $this->postJson('/api/v1/usuarios/2/toggle', ['vinculo_id' => 3])->assertUnprocessable();
        DB::table('espacos')->where('id', 1)->update(['situacao' => 'suspenso']);
        $this->getJson('/api/v1/usuarios')->assertForbidden();
    }

    public function test_convite_contextual_cria_conta_sem_escopos_e_recusa_so_vinculo(): void
    {
        Mail::fake();
        $this->entrar();
        $resposta = $this->postJson('/api/v1/usuarios', ['nome' => 'Nova pessoa', 'email' => 'nova@example.test', 'cpf' => '00000000099', 'perfil' => 'professor', 'escola_id' => 1, 'turma_ids' => [1]])->assertCreated();
        $id = $resposta->json('data.id');
        $u = Usuario::findOrFail($id);
        $v = DB::table('usuario_vinculos')->where('usuario_id', $id)->first();
        $this->assertSame('convidado', $v->situacao);
        $this->assertDatabaseMissing('usuario_escopos', ['usuario_id' => $id]);
        $convite = DB::table('password_reset_tokens')->where('vinculo_id', $v->id)->first();
        $this->assertNotNull($convite);
        app(\App\Services\ContaUsuario::class)->recusarConvite($u, $convite);
        $this->assertDatabaseHas('usuario_vinculos', ['id' => $v->id, 'situacao' => 'encerrado']);
        $this->assertDatabaseHas('usuarios', ['id' => $id, 'ativo' => 1]);
        $this->assertDatabaseMissing('password_reset_tokens', ['vinculo_id' => $v->id]);
    }

    public function test_aluno_recebe_so_resultado_proprio_sem_media_dos_colegas(): void
    {
        $this->prepararResultado();
        $this->getJson('/api/v1/alunos/1/resultados/1')->assertOk()->assertJsonPath('data.aluno.id', 1)->assertJsonPath('data.media_turma', null);
        $this->getJson('/api/v1/alunos/2/resultados/2')->assertNotFound();
        $this->getJson('/api/v1/alunos/2/resultados/1')->assertNotFound();
        $this->getJson('/api/v1/provas/1')->assertForbidden();
        DB::table('vinculo_alunos')->where('vinculo_id', 4)->delete();
        $this->getJson('/api/v1/alunos/1/resultados/1')->assertNotFound();
    }
    private function novoConvite(): array
    {
        Mail::fake();
        $this->entrar();
        $resposta = $this->postJson('/api/v1/usuarios', ['nome' => 'Convidado', 'email' => 'convite@example.test', 'cpf' => '00000000100', 'perfil' => 'professor', 'escola_id' => 1])->assertCreated();
        $token = null;
        Mail::assertQueued(\App\Mail\EmailModelo::class, function ($m) use (&$token) {
            if ($m->modelo !== 'convite-usuario') return false;
            parse_str(parse_url($m->dados['link'], PHP_URL_QUERY), $q);
            $token = $q['token'];
            return true;
        });
        return [$resposta->json('data.id'), $token];
    }

    public function test_aceite_contextual_ativa_so_vinculo_e_link_nao_repete(): void
    {
        [$id, $token] = $this->novoConvite();
        $dados = ['email' => 'convite@example.test', 'token' => $token, 'password' => 'nova-senha-segura', 'password_confirmation' => 'nova-senha-segura'];
        $this->postJson('/api/v1/auth/convite', $dados)->assertOk()->assertJsonPath('data.funcao', 'Professor');
        $this->postJson('/api/v1/auth/redefinir-senha', $dados)->assertOk();
        $this->assertDatabaseHas('usuario_vinculos', ['usuario_id' => $id, 'situacao' => 'ativo', 'versao_autorizacao' => 2]);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check($dados['password'], Usuario::find($id)->password));
        $this->postJson('/api/v1/auth/redefinir-senha', $dados)->assertUnprocessable();
    }

    public function test_convite_revogado_e_convite_legado_convertido_nao_mudam_conta(): void
    {
        [$id, $token] = $this->novoConvite();
        DB::table('usuario_vinculos')->where('usuario_id', $id)->update(['situacao' => 'suspenso']);
        $dados = ['email' => 'convite@example.test', 'token' => $token, 'password' => 'nova-senha-segura', 'password_confirmation' => 'nova-senha-segura'];
        $senha = Usuario::find($id)->password;
        $this->postJson('/api/v1/auth/redefinir-senha', $dados)->assertUnprocessable();
        $this->postJson('/api/v1/auth/convite/recusar', $dados)->assertUnprocessable();
        DB::table('password_reset_tokens')->where('vinculo_id', DB::table('usuario_vinculos')->where('usuario_id', $id)->value('id'))->update(['vinculo_id' => null]);
        $this->postJson('/api/v1/auth/convite/recusar', $dados)->assertUnprocessable();
        $this->assertSame($senha, Usuario::find($id)->password);
        $this->assertDatabaseHas('usuarios', ['id' => $id, 'ativo' => 1]);
    }

    public function test_gestor_legado_nao_altera_conta_convertida(): void
    {
        Sanctum::actingAs(Usuario::find(1));
        $this->postJson('/api/v1/usuarios/2/toggle')->assertNotFound();
        $this->putJson('/api/v1/usuarios/2', ['nome' => 'Alterado'])->assertNotFound();
        $this->assertDatabaseHas('usuarios', ['id' => 2, 'ativo' => 1, 'nome' => 'Pessoa fictícia']);
    }

    public function test_paineis_projetam_vinculo_local_e_omitem_suspenso(): void
    {
        $this->vinculo(4, 1, 1, 'admin_rede');
        $this->vinculo(5, 2, 6, 'dir_escolar');
        Usuario::find(2)->update(['ultimo_acesso' => now()]);
        $this->entrar(4);
        $this->getJson('/api/v1/dashboard/admin')->assertOk()->assertJsonFragment(['vinculo_id' => 3, 'perfil' => 'professor'])->assertJsonMissing(['vinculo_id' => 5]);
        DB::table('usuario_vinculos')->where('id', 3)->update(['situacao' => 'suspenso']);
        $this->getJson('/api/v1/escolas/1')->assertOk()->assertJsonMissing(['vinculo_id' => 3]);
    }

    private function prepararResultado(): void
    {
        $this->vinculo(4, 2, 5, 'aluno');
        DB::table('vinculo_alunos')->insert(['vinculo_id' => 4, 'espaco_id' => 5, 'aluno_id' => 1]);
        DB::table('cartoes')->insert(['id' => 1, 'prova_id' => 1, 'aluno_id' => 1, 'espaco_id' => 5, 'status' => 'lido', 'imagem_url' => 'ficticio.jpg']);
        DB::table('notas')->insert(['prova_id' => 1, 'aluno_id' => 1, 'turma_id' => 1, 'cartao_id' => 1, 'espaco_id' => 5, 'acertos' => 1, 'total_questoes' => 1, 'nota_final' => 10, 'status_aprovacao' => 'aprovado']);
        $this->entrar(4, 2);
    }

    public function test_pdf_individual_nao_recebe_colegas_e_revalida_apos_geracao(): void
    {
        $this->prepararResultado();
        $saida = null;
        \Illuminate\Support\Facades\Process::fake(function ($process) use (&$saida) {
            $payload = json_decode($process->input, true);
            $this->assertSame('Fictício', $payload['aluno']['nome']);
            $this->assertNull($payload['media_turma']);
            $saida = $payload['saida'];
            file_put_contents($saida, '%PDF-1.4 teste');
            DB::table('usuario_vinculos')->where('id', 4)->update(['situacao' => 'suspenso']);
            return \Illuminate\Support\Facades\Process::result(output: '{"ok":true}');
        });
        $this->getJson('/api/v1/alunos/1/resultados/1/pdf')->assertForbidden();
        $this->assertNotNull($saida);
        $this->assertFileDoesNotExist($saida);
    }

}
