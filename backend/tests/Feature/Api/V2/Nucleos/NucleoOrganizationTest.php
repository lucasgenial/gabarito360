<?php

namespace Tests\Feature\Api\V2\Nucleos;

use App\Enums\StatusEnum;
use App\Enums\UserRole;
use App\Models\Nucleo;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithIdentity;
use Tests\TestCase;

class NucleoOrganizationTest extends TestCase
{
    use InteractsWithIdentity, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccessControlSeeder::class);
    }

    public function test_admin_lists_nucleos_with_pagination_meta(): void
    {
        Nucleo::factory()->count(3)->create();
        $admin = $this->userWithRole(UserRole::ADMINISTRATOR);

        $response = $this->withToken($this->bearerToken($admin))->getJson('/api/v2/nucleos');

        $response
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'codigo', 'nome', 'status']], 'meta' => ['request_id', 'page', 'per_page', 'total']])
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.page', 1);
    }

    public function test_admin_can_create_nucleo(): void
    {
        $admin = $this->userWithRole(UserRole::ADMINISTRATOR);

        $this->withToken($this->bearerToken($admin))
            ->postJson('/api/v2/nucleos', [
                'codigo' => 'NUC-XYZ',
                'nome' => 'Núcleo Central',
                'municipio' => 'Recife',
                'estado' => 'PE',
            ])
            ->assertCreated()
            ->assertJsonPath('data.codigo', 'NUC-XYZ')
            ->assertJsonPath('data.status', 'ativo');

        $this->assertDatabaseHas('nucleos', ['codigo' => 'NUC-XYZ', 'nome' => 'Núcleo Central']);
        $this->assertDatabaseHas('auditorias', ['acao' => 'nucleo.created']);
    }

    public function test_admin_can_reactivate_nucleo(): void
    {
        $nucleo = Nucleo::factory()->create(['status' => StatusEnum::INACTIVE]);
        $admin = $this->userWithRole(UserRole::ADMINISTRATOR);

        $this->withToken($this->bearerToken($admin))
            ->postJson("/api/v2/nucleos/{$nucleo->id}/reativar")
            ->assertOk()
            ->assertJsonPath('data.status', 'ativo');

        $this->assertDatabaseHas('auditorias', ['acao' => 'nucleo.reactivated', 'entidade_id' => $nucleo->id]);
    }

    public function test_status_filter_narrows_results(): void
    {
        Nucleo::factory()->create(['status' => StatusEnum::ACTIVE]);
        Nucleo::factory()->create(['status' => StatusEnum::INACTIVE]);
        $admin = $this->userWithRole(UserRole::ADMINISTRATOR);

        $this->withToken($this->bearerToken($admin))
            ->getJson('/api/v2/nucleos?status=inativo')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.status', 'inativo');
    }

    public function test_non_admin_cannot_manage_nucleos(): void
    {
        $nucleo = Nucleo::factory()->create();
        $gestor = $this->userWithRole(UserRole::EDUCATION_MANAGER, nucleoId: $nucleo->id);

        $this->withToken($this->bearerToken($gestor))
            ->getJson('/api/v2/nucleos')
            ->assertForbidden();
    }
}
