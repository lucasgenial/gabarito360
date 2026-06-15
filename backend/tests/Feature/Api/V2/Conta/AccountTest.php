<?php

namespace Tests\Feature\Api\V2\Conta;

use App\Enums\UserRole;
use App\Models\Escola;
use App\Models\MatriculaTurma;
use App\Models\Nucleo;
use App\Models\Prova;
use App\Models\Turma;
use Database\Seeders\AcademicCatalogSeeder;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithIdentity;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use InteractsWithIdentity, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccessControlSeeder::class);
        $this->seed(AcademicCatalogSeeder::class);
    }

    public function test_consulta_plano_e_uso_real_do_nucleo(): void
    {
        $nucleo = Nucleo::factory()->create();
        $escola = Escola::factory()->create(['nucleo_id' => $nucleo->id]);
        $gestor = $this->userWithRole(UserRole::EDUCATION_MANAGER, nucleoId: $nucleo->id);

        $turma = Turma::factory()->create(['escola_id' => $escola->id]);
        MatriculaTurma::factory()->count(2)->create(['turma_id' => $turma->id]);
        Prova::factory()->create(['nucleo_id' => $nucleo->id]);

        $response = $this->actingAsToken($gestor)->getJson('/api/v2/plano-uso');

        $response->assertOk()
            ->assertJsonPath('data.nucleo_id', $nucleo->id)
            ->assertJsonPath('data.plano', 'institucional')
            ->assertJsonPath('data.uso.escolas', 1)
            ->assertJsonPath('data.uso.alunos', 2)
            ->assertJsonPath('data.uso.provas', 1)
            ->assertJsonStructure(['data' => ['limites' => ['escolas', 'alunos', 'provas'], 'uso']]);
    }

    public function test_admin_consulta_plano_de_outro_nucleo_por_parametro(): void
    {
        $nucleo = Nucleo::factory()->create();
        Escola::factory()->create(['nucleo_id' => $nucleo->id]);
        $admin = $this->userWithRole(UserRole::ADMINISTRATOR);

        $this->actingAsToken($admin)
            ->getJson("/api/v2/plano-uso?nucleo_id={$nucleo->id}")
            ->assertOk()
            ->assertJsonPath('data.nucleo_id', $nucleo->id)
            ->assertJsonPath('data.uso.escolas', 1);
    }

    public function test_usuario_sem_nucleo_acessivel_retorna_404(): void
    {
        $escola = Escola::factory()->create(['nucleo_id' => Nucleo::factory()->create()->id]);
        $diretor = $this->userWithRole(UserRole::SCHOOL_MANAGER, escolaId: $escola->id);

        $this->actingAsToken($diretor)
            ->getJson('/api/v2/plano-uso')
            ->assertNotFound();
    }
}
