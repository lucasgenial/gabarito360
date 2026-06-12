<?php

namespace Tests\Feature\Authorization;

use App\Enums\GabaritoOficialStatus;
use App\Enums\ProvaStatus;
use App\Enums\UserRole;
use App\Models\Aplicacao;
use App\Models\Escola;
use App\Models\GabaritoOficial;
use App\Models\Perfil;
use App\Models\Prova;
use App\Models\Relatorio;
use App\Models\Resultado;
use App\Models\Turma;
use App\Models\User;
use App\Models\UsuarioPerfil;
use Database\Factories\AplicadorTurmaFactory;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class R3OperationalPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_and_result_policies_respect_organizational_and_operational_scope(): void
    {
        $this->seed(AccessControlSeeder::class);
        [$application, $school] = $this->application();
        $admin = $this->userWithRole(UserRole::ADMINISTRATOR);
        $manager = $this->userWithRole(UserRole::SCHOOL_MANAGER, escola: $school);
        $foreignManager = $this->userWithRole(UserRole::SCHOOL_MANAGER, escola: Escola::factory()->create());
        $linkedTeacher = $this->userWithRole(UserRole::TEACHER);
        $unlinkedTeacher = $this->userWithRole(UserRole::TEACHER);

        AplicadorTurmaFactory::new()->create([
            'turma_id' => $application->turma_id,
            'usuario_id' => $linkedTeacher->id,
            'papel' => 'professor',
        ]);

        $result = (new Resultado)->setRelation('aplicacao', $application);

        $this->assertTrue(Gate::forUser($admin)->allows('view', $application));
        $this->assertTrue(Gate::forUser($manager)->allows('view', $application));
        $this->assertFalse(Gate::forUser($foreignManager)->allows('view', $application));
        $this->assertTrue(Gate::forUser($linkedTeacher)->allows('run', $application));
        $this->assertFalse(Gate::forUser($unlinkedTeacher)->allows('run', $application));
        $this->assertTrue(Gate::forUser($linkedTeacher)->allows('view', $result));
        $this->assertFalse(Gate::forUser($unlinkedTeacher)->allows('view', $result));
    }

    public function test_report_policy_allows_request_owner_and_authorized_scope_only(): void
    {
        $this->seed(AccessControlSeeder::class);
        $school = Escola::factory()->create();
        $owner = $this->userWithRole(UserRole::SCHOOL_MANAGER, escola: $school);
        $manager = $this->userWithRole(UserRole::SCHOOL_MANAGER, escola: $school);
        $foreignManager = $this->userWithRole(UserRole::SCHOOL_MANAGER, escola: Escola::factory()->create());
        $report = new Relatorio([
            'solicitante_id' => $owner->id,
            'escopo' => ['nucleo_id' => $school->nucleo_id, 'escola_id' => $school->id],
        ]);

        $this->assertTrue(Gate::forUser($owner)->allows('view', $report));
        $this->assertTrue(Gate::forUser($manager)->allows('view', $report));
        $this->assertFalse(Gate::forUser($foreignManager)->allows('view', $report));
    }

    /** @return array{Aplicacao, Escola} */
    private function application(): array
    {
        $school = Escola::factory()->create();
        $class = Turma::factory()->create(['escola_id' => $school->id]);
        $exam = Prova::factory()->create([
            'nucleo_id' => null,
            'escola_id' => $school->id,
            'status' => ProvaStatus::PUBLISHED,
            'publicada_at' => now(),
        ]);
        $answerKey = GabaritoOficial::factory()->create([
            'prova_id' => $exam->id,
            'status' => GabaritoOficialStatus::CURRENT,
        ]);

        return [
            Aplicacao::query()->create([
                'prova_id' => $exam->id,
                'turma_id' => $class->id,
                'escola_id' => $school->id,
                'gabarito_oficial_id' => $answerKey->id,
                'titulo' => 'Aplicacao da R3',
                'status' => 'agendada',
            ]),
            $school,
        ];
    }

    private function userWithRole(UserRole $role, ?Escola $escola = null): User
    {
        $user = User::factory()->create();
        $profile = Perfil::query()->where('codigo', $role->value)->firstOrFail();

        UsuarioPerfil::factory()->create([
            'usuario_id' => $user->id,
            'perfil_id' => $profile->id,
            'escola_id' => $escola?->id,
        ]);

        return $user;
    }
}
