<?php

namespace Tests\Feature\Api\V2\Escolas;

use App\Enums\StatusEnum;
use App\Enums\UserRole;
use App\Models\Escola;
use App\Models\Nucleo;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithIdentity;
use Tests\TestCase;

class EscolaOrganizationTest extends TestCase
{
    use InteractsWithIdentity, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccessControlSeeder::class);
    }

    public function test_admin_creates_escola_with_contract_shape(): void
    {
        $nucleo = Nucleo::factory()->create();
        $admin = $this->userWithRole(UserRole::ADMINISTRATOR);

        $response = $this->withToken($this->bearerToken($admin))
            ->postJson('/api/v2/escolas', [
                'nome' => 'Escola Modelo',
                'inep' => '12345678',
                'rede' => 'municipal',
                'endereco' => 'Rua das Flores, 100',
                'cidade' => 'Olinda',
                'uf' => 'pe',
                'diretor' => 'Maria Diretora',
                'nucleo_id' => $nucleo->id,
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.nome', 'Escola Modelo')
            ->assertJsonPath('data.cidade', 'Olinda')
            ->assertJsonPath('data.uf', 'PE')
            ->assertJsonPath('data.rede', 'municipal')
            ->assertJsonPath('data.endereco', 'Rua das Flores, 100')
            ->assertJsonPath('data.diretor', 'Maria Diretora')
            ->assertJsonPath('data.status', 'ativa');

        $this->assertDatabaseHas('escolas', [
            'nucleo_id' => $nucleo->id,
            'nome' => 'Escola Modelo',
            'municipio' => 'Olinda',
            'estado' => 'PE',
            'rede' => 'municipal',
        ]);
    }

    public function test_education_manager_only_sees_own_nucleo_escolas(): void
    {
        $nucleo = Nucleo::factory()->create();
        $outroNucleo = Nucleo::factory()->create();
        Escola::factory()->for($nucleo)->create();
        Escola::factory()->for($outroNucleo)->create();

        $gestor = $this->userWithRole(UserRole::EDUCATION_MANAGER, nucleoId: $nucleo->id);

        $this->withToken($this->bearerToken($gestor))
            ->getJson('/api/v2/escolas')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_education_manager_create_derives_nucleo_from_scope(): void
    {
        $nucleo = Nucleo::factory()->create();
        $gestor = $this->userWithRole(UserRole::EDUCATION_MANAGER, nucleoId: $nucleo->id);

        $this->withToken($this->bearerToken($gestor))
            ->postJson('/api/v2/escolas', [
                'nome' => 'Escola do Gestor',
                'cidade' => 'Caruaru',
                'uf' => 'PE',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('escolas', ['nucleo_id' => $nucleo->id, 'nome' => 'Escola do Gestor']);
    }

    public function test_admin_reactivates_escola(): void
    {
        $escola = Escola::factory()->create(['status' => StatusEnum::INACTIVE]);
        $admin = $this->userWithRole(UserRole::ADMINISTRATOR);

        $this->withToken($this->bearerToken($admin))
            ->postJson("/api/v2/escolas/{$escola->id}/reativar")
            ->assertOk()
            ->assertJsonPath('data.status', 'ativa');

        $this->assertDatabaseHas('auditorias', ['acao' => 'escola.reactivated', 'entidade_id' => $escola->id]);
    }

    public function test_indicadores_return_kpi_structure(): void
    {
        $escola = Escola::factory()->create();
        $admin = $this->userWithRole(UserRole::ADMINISTRATOR);

        $this->withToken($this->bearerToken($admin))
            ->getJson("/api/v2/escolas/{$escola->id}/indicadores")
            ->assertOk()
            ->assertJsonPath('data.kpis.turmas', 0)
            ->assertJsonPath('data.kpis.alunos', 0)
            ->assertJsonStructure(['data' => ['kpis' => ['turmas', 'alunos', 'provas', 'aplicacoes', 'membros']]]);
    }

    public function test_status_filter_maps_to_internal_status(): void
    {
        Escola::factory()->create(['status' => StatusEnum::ACTIVE]);
        Escola::factory()->create(['status' => StatusEnum::INACTIVE]);
        $admin = $this->userWithRole(UserRole::ADMINISTRATOR);

        $this->withToken($this->bearerToken($admin))
            ->getJson('/api/v2/escolas?status=inativa')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.status', 'inativa');
    }
}
