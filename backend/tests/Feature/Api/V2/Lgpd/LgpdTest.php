<?php

namespace Tests\Feature\Api\V2\Lgpd;

use App\Enums\UserRole;
use App\Models\Aluno;
use App\Models\Escola;
use App\Models\Nucleo;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithIdentity;
use Tests\TestCase;

class LgpdTest extends TestCase
{
    use InteractsWithIdentity, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccessControlSeeder::class);
    }

    private function titular(): User
    {
        return $this->userWithRole(UserRole::EDUCATION_MANAGER, nucleoId: Nucleo::factory()->create()->id);
    }

    private function abrirSolicitacao(User $user, string $tipo = 'exclusao'): string
    {
        return $this->actingAsToken($user)
            ->postJson('/api/v2/solicitacoes-lgpd', ['tipo' => $tipo, 'descricao' => 'Solicito tratamento dos meus dados.'])
            ->assertCreated()
            ->json('data.id');
    }

    public function test_titular_cria_solicitacao_sobre_a_propria_conta(): void
    {
        $user = $this->titular();

        $this->actingAsToken($user)
            ->postJson('/api/v2/solicitacoes-lgpd', ['tipo' => 'exclusao', 'descricao' => 'Quero encerrar a conta.'])
            ->assertCreated()
            ->assertJsonPath('data.status', 'aberta')
            ->assertJsonPath('data.tipo', 'exclusao')
            ->assertJsonPath('data.titular_tipo', 'usuario');

        $this->assertDatabaseHas('solicitacoes_lgpd', [
            'solicitante_id' => $user->id,
            'titular_id' => $user->id,
            'tipo' => 'exclusao',
            'status' => 'aberta',
        ]);
    }

    public function test_lista_restringe_ao_solicitante_e_admin_ve_todas(): void
    {
        $user = $this->titular();
        $this->abrirSolicitacao($user);

        $outro = $this->titular();
        $this->actingAsToken($outro)
            ->getJson('/api/v2/solicitacoes-lgpd')
            ->assertOk()
            ->assertJsonPath('meta.total', 0);

        $admin = $this->userWithRole(UserRole::ADMINISTRATOR);
        $this->actingAsToken($admin)
            ->getJson('/api/v2/solicitacoes-lgpd')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_processar_exclusao_anonimiza_o_titular_usuario(): void
    {
        $titular = $this->titular();
        $id = $this->abrirSolicitacao($titular, 'exclusao');

        $admin = $this->userWithRole(UserRole::ADMINISTRATOR);
        $this->actingAsToken($admin)
            ->postJson("/api/v2/solicitacoes-lgpd/{$id}/processar", ['decisao' => 'Deferido conforme LGPD.'])
            ->assertOk()
            ->assertJsonPath('data.status', 'concluida')
            ->assertJsonPath('data.execucoes.0.acao', 'anonimizacao');

        $this->assertDatabaseHas('usuarios', [
            'id' => $titular->id,
            'nome' => 'Titular anonimizado',
            'status' => 'inativo',
        ]);
        $this->assertDatabaseHas('execucoes_descarte', [
            'solicitacao_lgpd_id' => $id,
            'acao' => 'anonimizacao',
            'afetados' => 1,
        ]);
        $this->assertDatabaseHas('auditorias', ['acao' => 'lgpd.titular.anonimizado']);
    }

    public function test_processar_tipo_acesso_conclui_sem_descarte(): void
    {
        $titular = $this->titular();
        $id = $this->abrirSolicitacao($titular, 'acesso');

        $admin = $this->userWithRole(UserRole::ADMINISTRATOR);
        $this->actingAsToken($admin)
            ->postJson("/api/v2/solicitacoes-lgpd/{$id}/processar", ['decisao' => 'Dados fornecidos por e-mail.'])
            ->assertOk()
            ->assertJsonPath('data.status', 'concluida')
            ->assertJsonCount(0, 'data.execucoes');

        $this->assertDatabaseMissing('execucoes_descarte', ['solicitacao_lgpd_id' => $id]);
        $this->assertDatabaseHas('usuarios', ['id' => $titular->id, 'status' => 'ativo']);
    }

    public function test_processar_exige_admin_de_configuracoes(): void
    {
        $titular = $this->titular();
        $id = $this->abrirSolicitacao($titular);

        $outro = $this->titular();
        $this->actingAsToken($outro)
            ->postJson("/api/v2/solicitacoes-lgpd/{$id}/processar", ['decisao' => 'x'])
            ->assertForbidden();
    }

    public function test_processar_solicitacao_concluida_retorna_422(): void
    {
        $titular = $this->titular();
        $id = $this->abrirSolicitacao($titular, 'acesso');
        $admin = $this->userWithRole(UserRole::ADMINISTRATOR);

        $this->actingAsToken($admin)
            ->postJson("/api/v2/solicitacoes-lgpd/{$id}/processar", ['decisao' => 'ok'])
            ->assertOk();

        $this->actingAsToken($admin)
            ->postJson("/api/v2/solicitacoes-lgpd/{$id}/processar", ['decisao' => 'denovo'])
            ->assertStatus(422);
    }

    public function test_solicitacao_sobre_aluno_exige_permissao_e_anonimiza(): void
    {
        $nucleo = Nucleo::factory()->create();
        $escola = Escola::factory()->create(['nucleo_id' => $nucleo->id]);
        $aluno = Aluno::factory()->create(['escola_id' => $escola->id]);

        // Viewer não pode abrir solicitação sobre aluno.
        $viewer = $this->userWithRole(UserRole::VIEWER, nucleoId: $nucleo->id);
        $this->actingAsToken($viewer)
            ->postJson('/api/v2/solicitacoes-lgpd', ['tipo' => 'anonimizacao', 'descricao' => 'x', 'aluno_id' => $aluno->id])
            ->assertForbidden();

        // Gestor pode; admin processa e o aluno é anonimizado.
        $gestor = $this->userWithRole(UserRole::EDUCATION_MANAGER, nucleoId: $nucleo->id);
        $id = $this->actingAsToken($gestor)
            ->postJson('/api/v2/solicitacoes-lgpd', ['tipo' => 'anonimizacao', 'descricao' => 'Remover dados do aluno.', 'aluno_id' => $aluno->id])
            ->assertCreated()
            ->assertJsonPath('data.titular_tipo', 'aluno')
            ->json('data.id');

        $admin = $this->userWithRole(UserRole::ADMINISTRATOR);
        $this->actingAsToken($admin)
            ->postJson("/api/v2/solicitacoes-lgpd/{$id}/processar", ['decisao' => 'Deferido.'])
            ->assertOk()
            ->assertJsonPath('data.status', 'concluida');

        $this->assertDatabaseHas('alunos', [
            'id' => $aluno->id,
            'nome' => 'Aluno anonimizado',
            'status' => 'inativo',
        ]);
    }
}
