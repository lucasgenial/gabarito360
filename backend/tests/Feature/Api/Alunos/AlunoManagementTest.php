<?php

namespace Tests\Feature\Api\Alunos;

use App\Enums\AplicadorTurmaPapel;
use App\Enums\StatusEnum;
use App\Enums\UserRole;
use App\Models\Aluno;
use App\Models\AplicadorTurma;
use App\Models\Escola;
use App\Models\MatriculaTurma;
use App\Models\Nucleo;
use App\Models\Perfil;
use App\Models\Turma;
use App\Models\User;
use App\Models\UsuarioPerfil;
use App\Services\Audit\AuditAction;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AlunoManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccessControlSeeder::class);
    }

    public function test_administrator_creates_normalized_minimal_student_and_can_enroll_it(): void
    {
        $admin = $this->actingAsRole(UserRole::ADMINISTRATOR);
        $school = Escola::factory()->create();
        $class = Turma::factory()->create([
            'escola_id' => $school->id,
            'ano_letivo' => 2026,
        ]);

        $response = $this->postJson('/api/v1/alunos', [
            'escola_id' => $school->id,
            'matricula' => ' mat-001 ',
            'codigo_interno' => ' INT-01 ',
            'nome' => ' Aluno Teste ',
        ]);

        $studentId = $response->json('data.id');

        $response
            ->assertCreated()
            ->assertJsonPath('data.escola_id', $school->id)
            ->assertJsonPath('data.matricula', 'MAT-001')
            ->assertJsonPath('data.codigo_interno', 'INT-01')
            ->assertJsonPath('data.nome', 'Aluno Teste')
            ->assertJsonMissingPath('data.documento')
            ->assertJsonMissingPath('data.data_nascimento')
            ->assertJsonMissingPath('data.observacoes');

        $this->postJson('/api/v1/turmas/'.$class->id.'/matriculas', [
            'aluno_id' => $studentId,
            'inicio_em' => '2026-02-02',
        ])->assertCreated();

        $this->assertDatabaseHas('auditorias', [
            'acao' => AuditAction::STUDENT_CREATED->value,
            'usuario_id' => $admin->id,
            'entidade_id' => $studentId,
        ]);
    }

    public function test_student_personal_fields_are_rejected_and_enrollment_is_unique_only_per_school(): void
    {
        $this->actingAsRole(UserRole::ADMINISTRATOR);
        $firstSchool = Escola::factory()->create();
        $secondSchool = Escola::factory()->create();

        Aluno::factory()->create([
            'escola_id' => $firstSchool->id,
            'matricula' => 'MAT-UNICA',
        ]);

        $payload = [
            'matricula' => 'mat-unica',
            'nome' => 'Aluno',
        ];

        $this->postJson('/api/v1/alunos', [
            ...$payload,
            'escola_id' => $firstSchool->id,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('matricula', 'error.details');

        $this->postJson('/api/v1/alunos', [
            ...$payload,
            'escola_id' => $secondSchool->id,
        ])->assertCreated();

        $this->postJson('/api/v1/alunos', [
            'escola_id' => $secondSchool->id,
            'matricula' => 'MAT-PII',
            'nome' => 'Aluno',
            'documento' => '12345678900',
            'data_nascimento' => '2012-01-01',
            'observacoes' => 'Dado desnecessario',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['documento', 'data_nascimento', 'observacoes'], 'error.details');
    }

    public function test_school_manager_only_accesses_own_students_and_foreign_student_is_not_discoverable(): void
    {
        $ownSchool = Escola::factory()->create();
        $otherSchool = Escola::factory()->create();
        $ownStudent = Aluno::factory()->create(['escola_id' => $ownSchool->id]);
        $otherStudent = Aluno::factory()->create(['escola_id' => $otherSchool->id]);

        $this->actingAsRole(UserRole::SCHOOL_MANAGER, escola: $ownSchool);

        $this->getJson('/api/v1/alunos')
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.id', $ownStudent->id);
        $this->getJson('/api/v1/alunos/'.$otherStudent->id)->assertNotFound();
        $this->patchJson('/api/v1/alunos/'.$otherStudent->id, [
            'nome' => 'Tentativa',
        ])->assertNotFound();
        $this->deleteJson('/api/v1/alunos/'.$otherStudent->id)->assertNotFound();
    }

    public function test_authorized_manager_updates_and_inactivates_student_preserving_history(): void
    {
        $manager = $this->actingAsRole(
            UserRole::SCHOOL_MANAGER,
            escola: $school = Escola::factory()->create(),
        );
        $student = Aluno::factory()->create(['escola_id' => $school->id]);
        $class = Turma::factory()->create(['escola_id' => $school->id]);
        $enrollment = MatriculaTurma::factory()->create([
            'aluno_id' => $student->id,
            'turma_id' => $class->id,
            'ano_letivo' => $class->ano_letivo,
        ]);

        $this->patchJson('/api/v1/alunos/'.$student->id, [
            'nome' => 'Nome atualizado',
            'codigo_interno' => 'INT-ATUAL',
            'status' => StatusEnum::INACTIVE->value,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status', 'error.details');

        $this->patchJson('/api/v1/alunos/'.$student->id, [
            'nome' => 'Nome atualizado',
            'codigo_interno' => 'INT-ATUAL',
        ])
            ->assertOk()
            ->assertJsonPath('data.nome', 'Nome atualizado');

        $this->deleteJson('/api/v1/alunos/'.$student->id)
            ->assertOk()
            ->assertJsonPath('data.status', StatusEnum::INACTIVE->value);

        $this->assertDatabaseHas('matriculas_turmas', ['id' => $enrollment->id]);
        $this->assertDatabaseHas('auditorias', [
            'acao' => AuditAction::STUDENT_UPDATED->value,
            'usuario_id' => $manager->id,
            'entidade_id' => $student->id,
        ]);
        $this->assertDatabaseHas('auditorias', [
            'acao' => AuditAction::STUDENT_INACTIVATED->value,
            'usuario_id' => $manager->id,
            'entidade_id' => $student->id,
        ]);
    }

    public function test_linked_teacher_only_reads_students_from_assigned_class_with_reduced_resource(): void
    {
        $school = Escola::factory()->create();
        $linkedClass = Turma::factory()->create(['escola_id' => $school->id]);
        $otherClass = Turma::factory()->create(['escola_id' => $school->id]);
        $linkedStudent = Aluno::factory()->create(['escola_id' => $school->id]);
        $otherStudent = Aluno::factory()->create(['escola_id' => $school->id]);
        MatriculaTurma::factory()->create([
            'aluno_id' => $linkedStudent->id,
            'turma_id' => $linkedClass->id,
            'ano_letivo' => $linkedClass->ano_letivo,
        ]);
        MatriculaTurma::factory()->create([
            'aluno_id' => $otherStudent->id,
            'turma_id' => $otherClass->id,
            'ano_letivo' => $otherClass->ano_letivo,
        ]);

        $teacher = $this->actingAsRole(UserRole::TEACHER, escola: $school);
        AplicadorTurma::factory()->create([
            'turma_id' => $linkedClass->id,
            'usuario_id' => $teacher->id,
            'papel' => AplicadorTurmaPapel::TEACHER,
        ]);

        $this->getJson('/api/v1/alunos')
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.id', $linkedStudent->id)
            ->assertJsonMissingPath('data.items.0.escola_id')
            ->assertJsonMissingPath('data.items.0.codigo_interno');
        $this->getJson('/api/v1/alunos/'.$otherStudent->id)->assertNotFound();
        $this->patchJson('/api/v1/alunos/'.$linkedStudent->id, [
            'nome' => 'Nao autorizado',
        ])->assertNotFound();
    }

    public function test_mixed_profile_receives_reduced_resource_outside_its_administrative_school(): void
    {
        $managedSchool = Escola::factory()->create();
        $operationalSchool = Escola::factory()->create();
        $managedStudent = Aluno::factory()->create(['escola_id' => $managedSchool->id]);
        $operationalStudent = Aluno::factory()->create(['escola_id' => $operationalSchool->id]);
        $class = Turma::factory()->create(['escola_id' => $operationalSchool->id]);
        MatriculaTurma::factory()->create([
            'aluno_id' => $operationalStudent->id,
            'turma_id' => $class->id,
            'ano_letivo' => $class->ano_letivo,
        ]);

        $user = $this->actingAsRole(UserRole::SCHOOL_MANAGER, escola: $managedSchool);
        $teacherProfile = Perfil::query()->where('codigo', UserRole::TEACHER->value)->firstOrFail();
        UsuarioPerfil::factory()->create([
            'usuario_id' => $user->id,
            'perfil_id' => $teacherProfile->id,
            'escola_id' => $operationalSchool->id,
            'inicio_at' => now()->subMinute(),
        ]);
        AplicadorTurma::factory()->create([
            'turma_id' => $class->id,
            'usuario_id' => $user->id,
            'papel' => AplicadorTurmaPapel::TEACHER,
        ]);

        $this->getJson('/api/v1/alunos')
            ->assertOk()
            ->assertJsonCount(2, 'data.items')
            ->assertJsonFragment([
                'id' => $managedStudent->id,
                'escola_id' => $managedSchool->id,
            ]);

        $this->getJson('/api/v1/alunos/'.$operationalStudent->id)
            ->assertOk()
            ->assertJsonPath('data.id', $operationalStudent->id)
            ->assertJsonMissingPath('data.escola_id')
            ->assertJsonMissingPath('data.codigo_interno');
    }

    public function test_unlinked_operational_profile_cannot_access_students(): void
    {
        $student = Aluno::factory()->create();

        $this->getJson('/api/v1/alunos')->assertUnauthorized();

        $this->actingAsRole(UserRole::APPLICATOR, escola: $student->escola);

        $this->getJson('/api/v1/alunos')->assertForbidden();
        $this->getJson('/api/v1/alunos/'.$student->id)->assertNotFound();
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
            'escola_id' => in_array($role, [
                UserRole::SCHOOL_MANAGER,
                UserRole::TEACHER,
                UserRole::APPLICATOR,
            ], strict: true) ? $escola?->id : null,
            'inicio_at' => now()->subMinute(),
        ]);

        Sanctum::actingAs($user, ['api']);

        return $user;
    }
}
