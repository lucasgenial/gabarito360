<?php

namespace Tests\Feature\Authorization;

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

class TurmaAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccessControlSeeder::class);
    }

    public function test_manager_assigns_teacher_and_assignment_grants_only_linked_class_visibility(): void
    {
        $school = Escola::factory()->create();
        $linkedClass = Turma::factory()->create(['escola_id' => $school->id]);
        $otherClass = Turma::factory()->create(['escola_id' => $school->id]);
        $teacher = $this->userWithRole(UserRole::TEACHER, escola: $school);
        $manager = $this->actingAsRole(UserRole::SCHOOL_MANAGER, escola: $school);

        $response = $this->postJson('/api/v1/turmas/'.$linkedClass->id.'/aplicadores', [
            'usuario_id' => $teacher->id,
            'papel' => AplicadorTurmaPapel::TEACHER->value,
            'inicio_em' => '2026-02-02',
        ]);

        $linkId = $response->json('data.id');

        $response
            ->assertCreated()
            ->assertJsonPath('data.turma_id', $linkedClass->id)
            ->assertJsonPath('data.usuario_id', $teacher->id)
            ->assertJsonPath('data.papel', AplicadorTurmaPapel::TEACHER->value);
        $this->assertDatabaseHas('auditorias', [
            'acao' => AuditAction::CLASS_STAFF_ASSIGNED->value,
            'usuario_id' => $manager->id,
            'entidade_id' => $linkId,
        ]);

        Sanctum::actingAs($teacher, ['api']);

        $this->getJson('/api/v1/turmas')
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.id', $linkedClass->id);
        $this->getJson('/api/v1/turmas/'.$linkedClass->id)->assertOk();
        $this->getJson('/api/v1/turmas/'.$otherClass->id)->assertForbidden();
        $this->patchJson('/api/v1/turmas/'.$linkedClass->id, ['nome' => 'Nao autorizado'])->assertForbidden();
    }

    public function test_active_equivalent_assignment_cannot_duplicate_and_wrong_profile_is_rejected(): void
    {
        $school = Escola::factory()->create();
        $class = Turma::factory()->create(['escola_id' => $school->id]);
        $teacher = $this->userWithRole(UserRole::TEACHER, escola: $school);
        $applicator = $this->userWithRole(UserRole::APPLICATOR, escola: $school);
        $this->actingAsRole(UserRole::SCHOOL_MANAGER, escola: $school);

        $payload = [
            'usuario_id' => $teacher->id,
            'papel' => AplicadorTurmaPapel::TEACHER->value,
            'inicio_em' => '2026-02-02',
        ];

        $this->postJson('/api/v1/turmas/'.$class->id.'/aplicadores', $payload)->assertCreated();
        $this->postJson('/api/v1/turmas/'.$class->id.'/aplicadores', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('papel', 'error.details');

        $this->postJson('/api/v1/turmas/'.$class->id.'/aplicadores', [
            'usuario_id' => $applicator->id,
            'papel' => AplicadorTurmaPapel::TEACHER->value,
            'inicio_em' => '2026-02-02',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('usuario_id', 'error.details');
    }

    public function test_assignment_from_another_school_or_outside_manager_scope_is_rejected(): void
    {
        $ownSchool = Escola::factory()->create();
        $otherSchool = Escola::factory()->create();
        $ownClass = Turma::factory()->create(['escola_id' => $ownSchool->id]);
        $otherClass = Turma::factory()->create(['escola_id' => $otherSchool->id]);
        $foreignTeacher = $this->userWithRole(UserRole::TEACHER, escola: $otherSchool);
        $ownTeacher = $this->userWithRole(UserRole::TEACHER, escola: $ownSchool);
        $this->actingAsRole(UserRole::SCHOOL_MANAGER, escola: $ownSchool);

        $this->postJson('/api/v1/turmas/'.$ownClass->id.'/aplicadores', [
            'usuario_id' => $foreignTeacher->id,
            'papel' => AplicadorTurmaPapel::TEACHER->value,
            'inicio_em' => '2026-02-02',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('usuario_id', 'error.details');

        $this->postJson('/api/v1/turmas/'.$otherClass->id.'/aplicadores', [
            'usuario_id' => $ownTeacher->id,
            'papel' => AplicadorTurmaPapel::TEACHER->value,
            'inicio_em' => '2026-02-02',
        ])->assertForbidden();
    }

    public function test_closing_assignment_preserves_history_and_revokes_operational_access(): void
    {
        $school = Escola::factory()->create();
        $class = Turma::factory()->create(['escola_id' => $school->id]);
        $teacher = $this->userWithRole(UserRole::TEACHER, escola: $school);
        $manager = $this->actingAsRole(UserRole::SCHOOL_MANAGER, escola: $school);
        $link = AplicadorTurma::factory()->create([
            'turma_id' => $class->id,
            'usuario_id' => $teacher->id,
            'papel' => AplicadorTurmaPapel::TEACHER,
            'inicio_em' => '2026-02-02',
        ]);

        $this->deleteJson('/api/v1/turmas/'.$class->id.'/aplicadores/'.$link->id, [
            'fim_em' => '2026-06-10',
        ])
            ->assertOk()
            ->assertJsonPath('data.fim_em', '2026-06-10');

        $this->assertDatabaseHas('aplicadores_turmas', [
            'id' => $link->id,
            'fim_em' => '2026-06-10',
        ]);
        $this->assertDatabaseHas('auditorias', [
            'acao' => AuditAction::CLASS_STAFF_CLOSED->value,
            'usuario_id' => $manager->id,
            'entidade_id' => $link->id,
        ]);

        Sanctum::actingAs($teacher, ['api']);
        $this->getJson('/api/v1/turmas')->assertForbidden();
        $this->getJson('/api/v1/turmas/'.$class->id)->assertForbidden();
    }

    public function test_future_assignment_does_not_grant_operational_access_before_start_date(): void
    {
        $school = Escola::factory()->create();
        $class = Turma::factory()->create(['escola_id' => $school->id]);
        $teacher = $this->userWithRole(UserRole::TEACHER, escola: $school);
        AplicadorTurma::factory()->create([
            'turma_id' => $class->id,
            'usuario_id' => $teacher->id,
            'papel' => AplicadorTurmaPapel::TEACHER,
            'inicio_em' => today()->addDay(),
        ]);

        Sanctum::actingAs($teacher, ['api']);

        $this->getJson('/api/v1/turmas')->assertForbidden();
        $this->getJson('/api/v1/turmas/'.$class->id)->assertForbidden();
    }

    public function test_inactive_class_rejects_new_assignment_but_existing_link_can_be_closed(): void
    {
        $school = Escola::factory()->create();
        $class = Turma::factory()->create([
            'escola_id' => $school->id,
            'status' => StatusEnum::INACTIVE,
        ]);
        $teacher = $this->userWithRole(UserRole::TEACHER, escola: $school);
        $link = AplicadorTurma::factory()->create([
            'turma_id' => $class->id,
            'usuario_id' => $teacher->id,
            'papel' => AplicadorTurmaPapel::TEACHER,
            'inicio_em' => '2026-02-02',
        ]);
        $this->actingAsRole(UserRole::SCHOOL_MANAGER, escola: $school);

        $this->postJson('/api/v1/turmas/'.$class->id.'/aplicadores', [
            'usuario_id' => $teacher->id,
            'papel' => AplicadorTurmaPapel::APPLICATOR->value,
            'inicio_em' => '2026-02-02',
        ])->assertForbidden();

        $this->deleteJson('/api/v1/turmas/'.$class->id.'/aplicadores/'.$link->id, [
            'fim_em' => '2026-06-10',
        ])->assertOk();
    }

    public function test_linked_teacher_can_read_only_students_enrolled_in_linked_class(): void
    {
        $school = Escola::factory()->create();
        $class = Turma::factory()->create(['escola_id' => $school->id]);
        $linkedStudent = Aluno::factory()->create(['escola_id' => $school->id]);
        $unlinkedStudent = Aluno::factory()->create(['escola_id' => $school->id]);
        MatriculaTurma::factory()->create([
            'aluno_id' => $linkedStudent->id,
            'turma_id' => $class->id,
            'ano_letivo' => $class->ano_letivo,
        ]);
        $teacher = $this->userWithRole(UserRole::TEACHER, escola: $school);
        AplicadorTurma::factory()->create([
            'turma_id' => $class->id,
            'usuario_id' => $teacher->id,
            'papel' => AplicadorTurmaPapel::TEACHER,
        ]);

        Sanctum::actingAs($teacher, ['api']);

        $this->getJson('/api/v1/alunos')
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.id', $linkedStudent->id);
        $this->getJson('/api/v1/alunos/'.$unlinkedStudent->id)->assertNotFound();
    }

    private function actingAsRole(
        UserRole $role,
        ?Nucleo $nucleo = null,
        ?Escola $escola = null,
    ): User {
        $user = $this->userWithRole($role, $nucleo, $escola);
        Sanctum::actingAs($user, ['api']);

        return $user;
    }

    private function userWithRole(
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

        return $user;
    }
}
