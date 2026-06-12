<?php

namespace Tests\Feature\Web;

use App\Enums\GabaritoOficialStatus;
use App\Enums\ModeloCartaoStatus;
use App\Enums\ProvaStatus;
use App\Enums\UserRole;
use App\Models\Aluno;
use App\Models\Aplicacao;
use App\Models\AplicacaoAluno;
use App\Models\Escola;
use App\Models\GabaritoOficial;
use App\Models\MatriculaTurma;
use App\Models\ModeloCartao;
use App\Models\Nucleo;
use App\Models\Perfil;
use App\Models\Prova;
use App\Models\Turma;
use App\Models\User;
use App\Models\UsuarioPerfil;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalR5Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AccessControlSeeder::class);
    }

    public function test_authenticated_user_can_manage_own_profile_and_preferences(): void
    {
        $user = $this->userWithRole(UserRole::ADMINISTRATOR);
        $this->actingAs($user);

        $this->get('/perfil')
            ->assertOk()
            ->assertSee($user->nome)
            ->assertSee('Vinculos vigentes');

        $this->patch('/perfil', [
            'nome' => 'Conta Atualizada',
            'email' => 'conta.atualizada@example.test',
        ])->assertRedirect();

        $this->patch('/configuracoes/preferencias', [
            'tema' => 'escuro',
            'densidade' => 'compacta',
            'contraste_alto' => '1',
            'reduzir_movimento' => '1',
            'idioma' => 'pt-BR',
        ])->assertRedirect();

        $this->assertDatabaseHas('usuarios', ['id' => $user->id, 'nome' => 'Conta Atualizada']);
        $this->assertDatabaseHas('preferencias_usuario', [
            'usuario_id' => $user->id,
            'tema' => 'escuro',
            'densidade' => 'compacta',
            'contraste_alto' => true,
        ]);
    }

    public function test_school_manager_sees_only_school_context_across_canonical_screens(): void
    {
        $ownSchool = Escola::factory()->create(['nome' => 'Escola autorizada']);
        $foreignSchool = Escola::factory()->create(['nome' => 'Escola restrita']);
        $ownClass = Turma::factory()->create(['escola_id' => $ownSchool->id, 'nome' => 'Turma autorizada']);
        $foreignClass = Turma::factory()->create(['escola_id' => $foreignSchool->id, 'nome' => 'Turma restrita']);
        Aluno::factory()->create(['escola_id' => $ownSchool->id, 'nome' => 'Aluno autorizado']);
        Aluno::factory()->create(['escola_id' => $foreignSchool->id, 'nome' => 'Aluno restrito']);
        $manager = $this->userWithRole(UserRole::SCHOOL_MANAGER, school: $ownSchool);
        $this->actingAs($manager);

        $this->get('/painel')->assertOk()->assertSee('Painel Gabarito360');
        $this->get('/escolas')
            ->assertOk()
            ->assertSee($ownSchool->nome)
            ->assertDontSee($foreignSchool->nome);
        $this->get('/turmas')
            ->assertOk()
            ->assertSee($ownClass->nome)
            ->assertDontSee($foreignClass->nome);
        $this->get('/turmas/'.$foreignClass->id)->assertNotFound();
    }

    public function test_manager_can_create_student_from_class_screen_and_update_it(): void
    {
        $school = Escola::factory()->create();
        $class = Turma::factory()->create(['escola_id' => $school->id, 'ano_letivo' => 2026]);
        $manager = $this->userWithRole(UserRole::SCHOOL_MANAGER, school: $school);
        $this->actingAs($manager);

        $response = $this->post('/turmas/'.$class->id.'/alunos', [
            'escola_id' => $school->id,
            'nome' => 'Aluno Portal',
            'matricula' => 'PORTAL-001',
            'codigo_interno' => 'G360-IMPRESSO-001-C',
        ]);

        $student = Aluno::query()->where('matricula', 'PORTAL-001')->firstOrFail();
        $response->assertRedirect(route('portal.students.show', $student));
        $this->assertDatabaseHas('matriculas_turmas', [
            'turma_id' => $class->id,
            'aluno_id' => $student->id,
        ]);

        $this->patch('/alunos/'.$student->id, [
            'nome' => 'Aluno Portal Atualizado',
            'matricula' => 'PORTAL-001',
            'codigo_interno' => 'G360-IMPRESSO-001-C',
        ])->assertRedirect(route('portal.students.show', $student));

        $this->assertSame('Aluno Portal Atualizado', $student->fresh()->nome);
    }

    public function test_administrator_can_create_exam_draft_from_canonical_screen(): void
    {
        $admin = $this->userWithRole(UserRole::ADMINISTRATOR);
        $nucleus = Nucleo::factory()->create();
        $model = ModeloCartao::factory()->create([
            'nucleo_id' => null,
            'status' => ModeloCartaoStatus::APPROVED,
            'homologado_por' => $admin->id,
            'homologado_at' => now(),
        ]);
        $this->actingAs($admin);

        $this->get('/provas/nova')
            ->assertOk()
            ->assertSee($model->nome);

        $response = $this->post('/provas', [
            'nucleo_id' => $nucleus->id,
            'escola_id' => null,
            'modelo_cartao_id' => $model->id,
            'codigo' => 'PROVA-PORTAL',
            'titulo' => 'Prova criada no portal',
            'tipo' => 'diagnostico',
            'nivel' => '6 ano',
            'ano_referencia' => 2026,
            'quantidade_questoes' => 20,
            'quantidade_alternativas' => 5,
            'alternativas' => ['A', 'B', 'C', 'D', 'E'],
        ]);

        $exam = Prova::query()->where('codigo', 'PROVA-PORTAL')->firstOrFail();
        $response->assertRedirect(route('portal.exams.show', $exam));
        $this->assertSame(ProvaStatus::DRAFT, $exam->status);
    }

    public function test_operational_and_report_screens_use_persisted_authorized_data(): void
    {
        $school = Escola::factory()->create();
        $class = Turma::factory()->create(['escola_id' => $school->id]);
        $student = Aluno::factory()->create(['escola_id' => $school->id]);
        $enrollment = MatriculaTurma::factory()->create([
            'aluno_id' => $student->id,
            'turma_id' => $class->id,
            'ano_letivo' => $class->ano_letivo,
        ]);
        $exam = Prova::factory()->create([
            'nucleo_id' => $school->nucleo_id,
            'status' => ProvaStatus::PUBLISHED,
        ]);
        $key = GabaritoOficial::factory()->create([
            'prova_id' => $exam->id,
            'status' => GabaritoOficialStatus::CURRENT,
        ]);
        $application = Aplicacao::query()->create([
            'prova_id' => $exam->id,
            'turma_id' => $class->id,
            'escola_id' => $school->id,
            'gabarito_oficial_id' => $key->id,
            'titulo' => 'Aplicacao persistida',
            'status' => 'finalizada',
        ]);
        AplicacaoAluno::query()->create([
            'aplicacao_id' => $application->id,
            'aluno_id' => $student->id,
            'matricula_turma_id' => $enrollment->id,
            'status' => 'previsto',
        ]);
        $manager = $this->userWithRole(UserRole::SCHOOL_MANAGER, school: $school);
        $this->actingAs($manager);

        $this->get('/correcoes')
            ->assertOk()
            ->assertSee('Aplicacao persistida');
        $this->get('/aplicacoes/'.$application->id.'/correcao')
            ->assertOk()
            ->assertSee($student->nome);
        $this->get('/provas/'.$exam->id.'/relatorio')
            ->assertOk()
            ->assertSee('Sem resultados consolidados');
    }

    private function userWithRole(
        UserRole $role,
        ?Escola $school = null,
    ): User {
        $user = User::factory()->create();
        $profile = Perfil::query()->where('codigo', $role->value)->firstOrFail();

        UsuarioPerfil::factory()->create([
            'usuario_id' => $user->id,
            'perfil_id' => $profile->id,
            'nucleo_id' => null,
            'escola_id' => $school?->id,
            'inicio_at' => now()->subMinute(),
        ]);

        return $user;
    }
}
