<?php

namespace Tests\Feature\Api\V2\Escolas;

use App\Enums\UserRole;
use App\Models\Escola;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithIdentity;
use Tests\TestCase;

class EquipeOrganizationTest extends TestCase
{
    use InteractsWithIdentity, RefreshDatabase;

    private const CPF_VALIDO = '111.444.777-35';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccessControlSeeder::class);
    }

    public function test_lists_school_profiles_with_fixed_permissions(): void
    {
        $escola = Escola::factory()->create();
        $admin = $this->userWithRole(UserRole::ADMINISTRATOR);

        $this->withToken($this->bearerToken($admin))
            ->getJson("/api/v2/escolas/{$escola->id}/perfis")
            ->assertOk()
            ->assertJsonCount(7, 'data')
            ->assertJsonPath('data.0.fixo', true)
            ->assertJsonStructure(['data' => [['chave', 'nome', 'fixo', 'membros', 'permissoes' => [['chave', 'permitido', 'fixo']]]]]);
    }

    public function test_reaffirming_fixed_permissions_is_accepted(): void
    {
        $escola = Escola::factory()->create();
        $admin = $this->userWithRole(UserRole::ADMINISTRATOR);

        // professor possui 'turmas_alunos.consultar' (reafirmar = 200)
        $this->withToken($this->bearerToken($admin))
            ->putJson("/api/v2/escolas/{$escola->id}/perfis/professor/permissoes", [
                'permissoes' => [['chave' => 'turmas_alunos.consultar', 'permitido' => true]],
            ])
            ->assertOk();

        $this->assertDatabaseHas('auditorias', ['acao' => 'escola.perfil.permissoes_attempted']);
    }

    public function test_changing_fixed_permission_is_forbidden(): void
    {
        $escola = Escola::factory()->create();
        $admin = $this->userWithRole(UserRole::ADMINISTRATOR);

        $this->withToken($this->bearerToken($admin))
            ->putJson("/api/v2/escolas/{$escola->id}/perfis/professor/permissoes", [
                'permissoes' => [['chave' => 'turmas_alunos.consultar', 'permitido' => false]],
            ])
            ->assertForbidden();
    }

    public function test_admin_creates_member_in_school(): void
    {
        $escola = Escola::factory()->create();
        $admin = $this->userWithRole(UserRole::ADMINISTRATOR);

        $response = $this->withToken($this->bearerToken($admin))
            ->postJson("/api/v2/escolas/{$escola->id}/membros", [
                'nome' => 'Carlos Professor',
                'cpf' => self::CPF_VALIDO,
                'email' => 'carlos@escola.test',
                'perfil' => 'professor',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.nome', 'Carlos Professor')
            ->assertJsonPath('data.escopo', 'operacional')
            ->assertJsonPath('data.status', 'ativo');

        $membro = User::query()->where('email', 'carlos@escola.test')->firstOrFail();
        $this->assertDatabaseHas('usuario_perfis', [
            'usuario_id' => $membro->id,
            'escola_id' => $escola->id,
            'fim_at' => null,
        ]);
    }

    public function test_member_list_is_scoped_to_school(): void
    {
        $escola = Escola::factory()->create();
        $admin = $this->userWithRole(UserRole::ADMINISTRATOR);
        $token = $this->bearerToken($admin);

        $this->withToken($token)->postJson("/api/v2/escolas/{$escola->id}/membros", [
            'nome' => 'Membro Um',
            'cpf' => self::CPF_VALIDO,
            'email' => 'um@escola.test',
            'perfil' => 'professor',
        ])->assertCreated();

        $this->withToken($token)
            ->getJson("/api/v2/escolas/{$escola->id}/membros")
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_member_update_keeps_cpf_and_login_immutable(): void
    {
        $escola = Escola::factory()->create();
        $admin = $this->userWithRole(UserRole::ADMINISTRATOR);
        $token = $this->bearerToken($admin);

        $this->withToken($token)->postJson("/api/v2/escolas/{$escola->id}/membros", [
            'nome' => 'Nome Antigo',
            'cpf' => self::CPF_VALIDO,
            'email' => 'fixo@escola.test',
            'perfil' => 'professor',
        ])->assertCreated();

        $membro = User::query()->where('email', 'fixo@escola.test')->firstOrFail();

        $this->withToken($token)
            ->putJson("/api/v2/escolas/{$escola->id}/membros/{$membro->id}", [
                'nome' => 'Nome Novo',
                'email' => 'tentativa@escola.test',
                'cpf' => '529.982.247-25',
            ])
            ->assertOk()
            ->assertJsonPath('data.nome', 'Nome Novo');

        $membro->refresh();
        $this->assertSame('fixo@escola.test', $membro->email);
        $this->assertSame('11144477735', $membro->documento);
    }

    public function test_member_can_be_suspended(): void
    {
        $escola = Escola::factory()->create();
        $admin = $this->userWithRole(UserRole::ADMINISTRATOR);
        $token = $this->bearerToken($admin);

        $this->withToken($token)->postJson("/api/v2/escolas/{$escola->id}/membros", [
            'nome' => 'A Suspender',
            'cpf' => self::CPF_VALIDO,
            'email' => 'suspender@escola.test',
            'perfil' => 'professor',
        ])->assertCreated();

        $membro = User::query()->where('email', 'suspender@escola.test')->firstOrFail();

        $this->withToken($token)
            ->postJson("/api/v2/escolas/{$escola->id}/membros/{$membro->id}/suspender")
            ->assertOk()
            ->assertJsonPath('data.status', 'suspenso');

        $this->assertDatabaseHas('usuarios', ['id' => $membro->id, 'status' => 'bloqueado']);
    }
}
