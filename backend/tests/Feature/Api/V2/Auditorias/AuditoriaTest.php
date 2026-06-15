<?php

namespace Tests\Feature\Api\V2\Auditorias;

use App\Enums\UserRole;
use App\Models\Nucleo;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithIdentity;
use Tests\TestCase;

class AuditoriaTest extends TestCase
{
    use InteractsWithIdentity, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccessControlSeeder::class);
    }

    private function registrar(string $actorId, string $nucleoId): void
    {
        app(AuditService::class)->record(
            AuditAction::SCHOOL_CREATED,
            'escola',
            actorUserId: $actorId,
            nucleoId: $nucleoId,
        );
    }

    public function test_admin_consulta_todas_as_auditorias(): void
    {
        $admin = $this->userWithRole(UserRole::ADMINISTRATOR);
        $this->registrar($admin->id, Nucleo::factory()->create()->id);
        $this->registrar($admin->id, Nucleo::factory()->create()->id);

        $this->actingAsToken($admin)
            ->getJson('/api/v2/auditorias?acao=escola.created')
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonStructure(['data' => [['id', 'acao', 'entidade_tipo', 'created_at']]]);
    }

    public function test_gestor_ve_apenas_auditorias_do_seu_escopo(): void
    {
        $admin = $this->userWithRole(UserRole::ADMINISTRATOR);
        $nucleoA = Nucleo::factory()->create();
        $nucleoB = Nucleo::factory()->create();
        $this->registrar($admin->id, $nucleoA->id);
        $this->registrar($admin->id, $nucleoB->id);

        $gestorA = $this->userWithRole(UserRole::EDUCATION_MANAGER, nucleoId: $nucleoA->id);

        // Filtra por ação para isolar do ruído de auditoria (ex.: concessão de perfil).
        $this->actingAsToken($gestorA)
            ->getJson('/api/v2/auditorias?acao=escola.created')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.nucleo_id', $nucleoA->id);
    }

    public function test_filtra_por_acao(): void
    {
        $admin = $this->userWithRole(UserRole::ADMINISTRATOR);
        $this->registrar($admin->id, Nucleo::factory()->create()->id);

        $this->actingAsToken($admin)
            ->getJson('/api/v2/auditorias?acao=escola.created')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);

        $this->actingAsToken($admin)
            ->getJson('/api/v2/auditorias?acao=inexistente.acao')
            ->assertOk()
            ->assertJsonPath('meta.total', 0);
    }

    public function test_sem_permissao_de_gestao_retorna_403(): void
    {
        $viewer = $this->userWithRole(UserRole::VIEWER, nucleoId: Nucleo::factory()->create()->id);

        $this->actingAsToken($viewer)
            ->getJson('/api/v2/auditorias')
            ->assertForbidden();
    }
}
