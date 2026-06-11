<?php

namespace Tests\Feature\Web;

use App\Enums\GabaritoOficialStatus;
use App\Enums\ProvaStatus;
use App\Enums\UserRole;
use App\Models\Escola;
use App\Models\GabaritoOficial;
use App\Models\Nucleo;
use App\Models\Perfil;
use App\Models\Prova;
use App\Models\Turma;
use App\Models\User;
use App\Models\UsuarioPerfil;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProvaTurmaPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccessControlSeeder::class);
    }

    public function test_administrator_operates_exam_class_links_from_panel(): void
    {
        $admin = $this->userWithRole(UserRole::ADMINISTRATOR);
        $school = Escola::factory()->create();
        $class = Turma::factory()->create(['escola_id' => $school->id]);
        $exam = $this->publishedExam(nucleus: $school->nucleo);
        $this->actingAs($admin);

        $this->get('/admin/provas')
            ->assertOk()
            ->assertSee($exam->titulo)
            ->assertSee('Versao 1 vigente')
            ->assertSee($class->nome);

        $this->post('/admin/provas/'.$exam->id.'/turmas', [
            'turma_id' => $class->id,
            'data_prevista' => '2026-08-10',
        ])->assertRedirect();

        $this->get('/admin/provas')
            ->assertOk()
            ->assertSee('10/08/2026');

        $this->delete('/admin/provas/'.$exam->id.'/turmas/'.$class->id)->assertRedirect();
        $this->assertDatabaseMissing('prova_turmas', [
            'prova_id' => $exam->id,
            'turma_id' => $class->id,
        ]);
    }

    public function test_school_manager_panel_shows_only_available_exams_and_classes(): void
    {
        $nucleus = Nucleo::factory()->create();
        $ownSchool = Escola::factory()->create(['nucleo_id' => $nucleus->id]);
        $otherSchool = Escola::factory()->create(['nucleo_id' => $nucleus->id]);
        $ownClass = Turma::factory()->create(['escola_id' => $ownSchool->id, 'nome' => 'Turma autorizada']);
        $otherClass = Turma::factory()->create(['escola_id' => $otherSchool->id, 'nome' => 'Turma restrita']);
        $nucleusExam = $this->publishedExam(nucleus: $nucleus);
        $foreignSchoolExam = $this->publishedExam(school: $otherSchool);
        $manager = $this->userWithRole(UserRole::SCHOOL_MANAGER, school: $ownSchool);
        $this->actingAs($manager);

        $this->get('/admin/provas')
            ->assertOk()
            ->assertSee($nucleusExam->titulo)
            ->assertSee($ownClass->nome)
            ->assertDontSee($otherClass->nome)
            ->assertDontSee($foreignSchoolExam->titulo);
    }

    private function publishedExam(?Nucleo $nucleus = null, ?Escola $school = null): Prova
    {
        $exam = Prova::factory()->create([
            'nucleo_id' => $school === null ? $nucleus?->id ?? Nucleo::factory() : null,
            'escola_id' => $school?->id,
        ]);
        $publisher = User::factory()->create();
        $answerKey = GabaritoOficial::factory()->create(['prova_id' => $exam->id]);
        $answerKey->update([
            'status' => GabaritoOficialStatus::CURRENT,
            'publicado_por' => $publisher->id,
            'publicado_at' => now(),
        ]);
        $exam->update([
            'status' => ProvaStatus::PUBLISHED,
            'publicada_at' => now(),
        ]);

        return $exam->refresh();
    }

    private function userWithRole(
        UserRole $role,
        ?Nucleo $nucleus = null,
        ?Escola $school = null,
    ): User {
        $user = User::factory()->create();
        $profile = Perfil::query()->where('codigo', $role->value)->firstOrFail();

        UsuarioPerfil::factory()->create([
            'usuario_id' => $user->id,
            'perfil_id' => $profile->id,
            'nucleo_id' => $role === UserRole::EDUCATION_MANAGER ? $nucleus?->id : null,
            'escola_id' => $role === UserRole::SCHOOL_MANAGER ? $school?->id : null,
            'inicio_at' => now()->subMinute(),
        ]);

        return $user;
    }
}
