<?php

namespace Tests\Feature\Api\V2\Turmas;

use App\Enums\UserRole;
use App\Models\Aluno;
use App\Models\Escola;
use App\Models\MatriculaTurma;
use App\Models\Turma;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithIdentity;
use Tests\TestCase;

class TurmaAcademicTest extends TestCase
{
    use InteractsWithIdentity, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccessControlSeeder::class);
    }

    public function test_admin_creates_turma_with_contract_shape(): void
    {
        $escola = Escola::factory()->create();
        $admin = $this->userWithRole(UserRole::ADMINISTRATOR);

        $response = $this->withToken($this->bearerToken($admin))
            ->postJson('/api/v2/turmas', [
                'nome' => '6º Ano A',
                'serie' => '6º Ano',
                'escola_id' => $escola->id,
                'periodo_letivo' => '2026.1',
                'turno' => 'matutino',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.nome', '6º Ano A')
            ->assertJsonPath('data.serie', '6º Ano')
            ->assertJsonPath('data.periodo_letivo', '2026')
            ->assertJsonPath('data.turno', 'matutino')
            ->assertJsonPath('data.alunos', 0)
            ->assertJsonPath('data.status', 'em-dia');

        $this->assertDatabaseHas('turmas', [
            'escola_id' => $escola->id,
            'nome' => '6º Ano A',
            'serie_ano' => '6º Ano',
            'ano_letivo' => 2026,
        ]);
    }

    public function test_school_manager_only_sees_own_school_turmas(): void
    {
        $escola = Escola::factory()->create();
        $outra = Escola::factory()->create();
        Turma::factory()->for($escola)->create();
        Turma::factory()->for($outra)->create();

        $gestor = $this->userWithRole(UserRole::SCHOOL_MANAGER, escolaId: $escola->id);

        $this->withToken($this->bearerToken($gestor))
            ->getJson('/api/v2/turmas')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_turma_alunos_lists_enrolled_students(): void
    {
        $escola = Escola::factory()->create();
        $turma = Turma::factory()->for($escola)->create();
        $aluno = Aluno::factory()->for($escola)->create();
        MatriculaTurma::factory()->create([
            'turma_id' => $turma->id,
            'aluno_id' => $aluno->id,
            'ano_letivo' => $turma->ano_letivo,
        ]);

        $admin = $this->userWithRole(UserRole::ADMINISTRATOR);

        $this->withToken($this->bearerToken($admin))
            ->getJson("/api/v2/turmas/{$turma->id}/alunos")
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $aluno->id);
    }

    public function test_viewer_cannot_create_turma(): void
    {
        $escola = Escola::factory()->create();
        $viewer = $this->userWithRole(UserRole::VIEWER, escolaId: $escola->id);

        $this->withToken($this->bearerToken($viewer))
            ->postJson('/api/v2/turmas', [
                'nome' => 'Turma X',
                'serie' => '7º Ano',
                'escola_id' => $escola->id,
                'periodo_letivo' => '2026',
            ])
            ->assertForbidden();
    }
}
