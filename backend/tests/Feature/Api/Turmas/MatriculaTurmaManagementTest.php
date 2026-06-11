<?php

namespace Tests\Feature\Api\Turmas;

use App\Enums\MatriculaTurmaStatus;
use App\Enums\StatusEnum;
use App\Enums\UserRole;
use App\Models\Aluno;
use App\Models\Escola;
use App\Models\MatriculaTurma;
use App\Models\Nucleo;
use App\Models\Perfil;
use App\Models\Turma;
use App\Models\User;
use App\Models\UsuarioPerfil;
use App\Services\Audit\AuditAction;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MatriculaTurmaManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccessControlSeeder::class);
    }

    public function test_school_manager_can_enroll_active_student_without_exposing_personal_data(): void
    {
        $manager = $this->actingAsRole(
            UserRole::SCHOOL_MANAGER,
            escola: $school = Escola::factory()->create(),
        );
        $turma = Turma::factory()->create([
            'escola_id' => $school->id,
            'ano_letivo' => 2026,
        ]);
        $aluno = Aluno::factory()->create([
            'escola_id' => $school->id,
            'nome' => 'Nome pessoal nao exposto',
            'documento' => '12345678900',
        ]);

        $response = $this->postJson('/api/v1/turmas/'.$turma->id.'/matriculas', [
            'aluno_id' => $aluno->id,
            'numero_chamada' => ' 15 ',
            'inicio_em' => '2026-02-02',
        ]);

        $matriculaId = $response->json('data.id');

        $response
            ->assertCreated()
            ->assertJsonPath('data.aluno_id', $aluno->id)
            ->assertJsonPath('data.turma_id', $turma->id)
            ->assertJsonPath('data.ano_letivo', 2026)
            ->assertJsonPath('data.numero_chamada', '15')
            ->assertJsonPath('data.status', MatriculaTurmaStatus::ACTIVE->value)
            ->assertJsonMissingPath('data.nome')
            ->assertJsonMissingPath('data.documento');

        $this->assertDatabaseHas('matriculas_turmas', [
            'id' => $matriculaId,
            'aluno_id' => $aluno->id,
            'turma_id' => $turma->id,
            'ano_letivo' => 2026,
            'status' => MatriculaTurmaStatus::ACTIVE->value,
            'fim_em' => null,
        ]);
        $this->assertDatabaseHas('auditorias', [
            'acao' => AuditAction::ENROLLMENT_CREATED->value,
            'usuario_id' => $manager->id,
            'entidade_id' => $matriculaId,
        ]);
    }

    public function test_student_can_have_only_one_active_enrollment_per_school_year(): void
    {
        $this->actingAsRole(UserRole::ADMINISTRATOR);
        $school = Escola::factory()->create();
        $firstClass = Turma::factory()->create([
            'escola_id' => $school->id,
            'ano_letivo' => 2026,
        ]);
        $secondClass = Turma::factory()->create([
            'escola_id' => $school->id,
            'ano_letivo' => 2026,
        ]);
        $aluno = Aluno::factory()->create(['escola_id' => $school->id]);

        $this->postJson('/api/v1/turmas/'.$firstClass->id.'/matriculas', [
            'aluno_id' => $aluno->id,
            'inicio_em' => '2026-02-02',
        ])->assertCreated();

        $this->postJson('/api/v1/turmas/'.$secondClass->id.'/matriculas', [
            'aluno_id' => $aluno->id,
            'inicio_em' => '2026-02-03',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('aluno_id', 'error.details');

        $this->assertDatabaseCount('matriculas_turmas', 1);
    }

    public function test_transfer_closes_old_enrollment_and_preserves_history_before_new_enrollment(): void
    {
        $manager = $this->actingAsRole(
            UserRole::SCHOOL_MANAGER,
            escola: $school = Escola::factory()->create(),
        );
        $oldClass = Turma::factory()->create([
            'escola_id' => $school->id,
            'ano_letivo' => 2026,
        ]);
        $newClass = Turma::factory()->create([
            'escola_id' => $school->id,
            'ano_letivo' => 2026,
        ]);
        $aluno = Aluno::factory()->create(['escola_id' => $school->id]);
        $oldEnrollment = MatriculaTurma::query()->create([
            'aluno_id' => $aluno->id,
            'turma_id' => $oldClass->id,
            'ano_letivo' => 2026,
            'status' => MatriculaTurmaStatus::ACTIVE,
            'inicio_em' => '2026-02-02',
        ]);

        $this->patchJson('/api/v1/turmas/'.$oldClass->id.'/matriculas/'.$oldEnrollment->id, [
            'status' => MatriculaTurmaStatus::TRANSFERRED->value,
            'fim_em' => '2026-05-10',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', MatriculaTurmaStatus::TRANSFERRED->value)
            ->assertJsonPath('data.fim_em', '2026-05-10');

        $newResponse = $this->postJson('/api/v1/turmas/'.$newClass->id.'/matriculas', [
            'aluno_id' => $aluno->id,
            'inicio_em' => '2026-05-11',
        ])->assertCreated();

        $this->assertDatabaseCount('matriculas_turmas', 2);
        $this->assertDatabaseHas('matriculas_turmas', [
            'id' => $oldEnrollment->id,
            'status' => MatriculaTurmaStatus::TRANSFERRED->value,
            'fim_em' => '2026-05-10',
        ]);
        $this->assertDatabaseHas('matriculas_turmas', [
            'id' => $newResponse->json('data.id'),
            'turma_id' => $newClass->id,
            'status' => MatriculaTurmaStatus::ACTIVE->value,
            'fim_em' => null,
        ]);
        $this->assertDatabaseHas('auditorias', [
            'acao' => AuditAction::ENROLLMENT_CLOSED->value,
            'usuario_id' => $manager->id,
            'entidade_id' => $oldEnrollment->id,
        ]);
    }

    public function test_class_inactivation_preserves_enrollments_for_historical_consultation(): void
    {
        $this->actingAsRole(
            UserRole::SCHOOL_MANAGER,
            escola: $school = Escola::factory()->create(),
        );
        $turma = Turma::factory()->create(['escola_id' => $school->id]);
        $aluno = Aluno::factory()->create(['escola_id' => $school->id]);
        $matricula = MatriculaTurma::query()->create([
            'aluno_id' => $aluno->id,
            'turma_id' => $turma->id,
            'ano_letivo' => $turma->ano_letivo,
            'status' => MatriculaTurmaStatus::ACTIVE,
            'inicio_em' => '2026-02-02',
        ]);

        $this->deleteJson('/api/v1/turmas/'.$turma->id)->assertOk();

        $this->getJson('/api/v1/turmas/'.$turma->id.'/matriculas')
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.id', $matricula->id);

        $this->postJson('/api/v1/turmas/'.$turma->id.'/matriculas', [
            'aluno_id' => $aluno->id,
            'inicio_em' => '2026-02-03',
        ])->assertForbidden();

        $this->patchJson('/api/v1/turmas/'.$turma->id.'/matriculas/'.$matricula->id, [
            'status' => MatriculaTurmaStatus::CLOSED->value,
            'fim_em' => '2026-12-10',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', MatriculaTurmaStatus::CLOSED->value);

        $this->assertDatabaseHas('matriculas_turmas', [
            'id' => $matricula->id,
            'status' => MatriculaTurmaStatus::CLOSED->value,
            'fim_em' => '2026-12-10',
        ]);
    }

    public function test_enrollment_rejects_student_from_another_school_and_inactive_student(): void
    {
        $this->actingAsRole(UserRole::ADMINISTRATOR);
        $school = Escola::factory()->create();
        $turma = Turma::factory()->create(['escola_id' => $school->id]);
        $otherSchoolStudent = Aluno::factory()->create();
        $inactiveStudent = Aluno::factory()->create([
            'escola_id' => $school->id,
            'status' => StatusEnum::INACTIVE,
        ]);

        foreach ([$otherSchoolStudent, $inactiveStudent] as $aluno) {
            $this->postJson('/api/v1/turmas/'.$turma->id.'/matriculas', [
                'aluno_id' => $aluno->id,
                'inicio_em' => '2026-02-02',
            ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('aluno_id', 'error.details');
        }

        $this->assertDatabaseCount('matriculas_turmas', 0);
    }

    public function test_database_rejects_enrollment_year_different_from_class(): void
    {
        $school = Escola::factory()->create();
        $turma = Turma::factory()->create([
            'escola_id' => $school->id,
            'ano_letivo' => 2026,
        ]);
        $aluno = Aluno::factory()->create(['escola_id' => $school->id]);

        $this->expectException(QueryException::class);

        MatriculaTurma::query()->create([
            'aluno_id' => $aluno->id,
            'turma_id' => $turma->id,
            'ano_letivo' => 2027,
            'status' => MatriculaTurmaStatus::ACTIVE,
            'inicio_em' => '2026-02-02',
        ]);
    }

    public function test_nested_enrollment_from_another_class_is_not_found_and_outside_scope_is_forbidden(): void
    {
        $ownSchool = Escola::factory()->create();
        $otherSchool = Escola::factory()->create();
        $ownClass = Turma::factory()->create(['escola_id' => $ownSchool->id]);
        $otherClass = Turma::factory()->create(['escola_id' => $otherSchool->id]);
        $otherStudent = Aluno::factory()->create(['escola_id' => $otherSchool->id]);
        $otherEnrollment = MatriculaTurma::query()->create([
            'aluno_id' => $otherStudent->id,
            'turma_id' => $otherClass->id,
            'ano_letivo' => $otherClass->ano_letivo,
            'status' => MatriculaTurmaStatus::ACTIVE,
            'inicio_em' => '2026-02-02',
        ]);

        $this->actingAsRole(UserRole::SCHOOL_MANAGER, escola: $ownSchool);

        $this->getJson('/api/v1/turmas/'.$otherClass->id.'/matriculas')->assertForbidden();
        $this->patchJson('/api/v1/turmas/'.$ownClass->id.'/matriculas/'.$otherEnrollment->id, [
            'status' => MatriculaTurmaStatus::CLOSED->value,
            'fim_em' => '2026-12-10',
        ])->assertNotFound();

        $this->assertSame(MatriculaTurmaStatus::ACTIVE, $otherEnrollment->fresh()->status);
    }

    private function actingAsRole(
        UserRole $role,
        ?Nucleo $nucleo = null,
        ?Escola $escola = null,
    ): User {
        $user = User::factory()->create();
        $profile = Perfil::query()->where('codigo', $role->value)->firstOrFail();

        UsuarioPerfil::factory()->create([
            'usuario_id' => $user->id,
            'perfil_id' => $profile->id,
            'nucleo_id' => $role === UserRole::EDUCATION_MANAGER ? $nucleo?->id : null,
            'escola_id' => $role === UserRole::SCHOOL_MANAGER ? $escola?->id : null,
            'inicio_at' => now()->subMinute(),
        ]);

        Sanctum::actingAs($user, ['api']);

        return $user;
    }
}
