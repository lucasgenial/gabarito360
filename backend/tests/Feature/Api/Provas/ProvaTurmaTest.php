<?php

namespace Tests\Feature\Api\Provas;

use App\Enums\GabaritoOficialStatus;
use App\Enums\ProvaStatus;
use App\Enums\StatusEnum;
use App\Enums\UserRole;
use App\Models\Escola;
use App\Models\GabaritoOficial;
use App\Models\Nucleo;
use App\Models\Perfil;
use App\Models\Prova;
use App\Models\ProvaTurma;
use App\Models\Turma;
use App\Models\User;
use App\Models\UsuarioPerfil;
use App\Services\Audit\AuditAction;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProvaTurmaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccessControlSeeder::class);
    }

    public function test_administrator_links_lists_and_unlinks_class_with_audit(): void
    {
        $admin = $this->actingAsRole(UserRole::ADMINISTRATOR);
        $school = Escola::factory()->create();
        $class = Turma::factory()->create(['escola_id' => $school->id]);
        $exam = $this->publishedExam(nucleus: $school->nucleo);
        $url = '/api/v1/provas/'.$exam->id.'/turmas';

        $linkId = $this->postJson($url, [
            'turma_id' => $class->id,
            'data_prevista' => '2026-08-10',
        ])
            ->assertCreated()
            ->assertJsonPath('data.prova_id', $exam->id)
            ->assertJsonPath('data.turma_id', $class->id)
            ->assertJsonPath('data.data_prevista', '2026-08-10')
            ->assertJsonPath('data.turma.nome', $class->nome)
            ->json('data.id');

        $this->getJson($url)
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.id', $linkId);

        $this->postJson($url, ['turma_id' => $class->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('turma_id', 'error.details');

        $this->assertDatabaseHas('auditorias', [
            'acao' => AuditAction::EXAM_CLASS_LINKED->value,
            'usuario_id' => $admin->id,
            'entidade_id' => $linkId,
        ]);

        $this->deleteJson($url.'/'.$class->id)
            ->assertOk()
            ->assertJsonPath('data', null);

        $this->assertDatabaseMissing('prova_turmas', ['id' => $linkId]);
        $this->assertDatabaseHas('auditorias', [
            'acao' => AuditAction::EXAM_CLASS_UNLINKED->value,
            'usuario_id' => $admin->id,
            'entidade_id' => $linkId,
        ]);
    }

    public function test_only_published_exam_and_active_compatible_class_can_be_linked(): void
    {
        $nucleus = Nucleo::factory()->create();
        $foreignNucleus = Nucleo::factory()->create();
        $school = Escola::factory()->create(['nucleo_id' => $nucleus->id]);
        $otherSchool = Escola::factory()->create(['nucleo_id' => $nucleus->id]);
        $foreignSchool = Escola::factory()->create(['nucleo_id' => $foreignNucleus->id]);
        $activeClass = Turma::factory()->create(['escola_id' => $school->id]);
        $inactiveClass = Turma::factory()->create([
            'escola_id' => $school->id,
            'status' => StatusEnum::INACTIVE,
        ]);
        $foreignClass = Turma::factory()->create(['escola_id' => $foreignSchool->id]);
        $sameNucleusOtherClass = Turma::factory()->create(['escola_id' => $otherSchool->id]);
        $draftExam = Prova::factory()->create(['nucleo_id' => $nucleus->id]);
        $publishedExam = $this->publishedExam(nucleus: $nucleus);
        $schoolExam = $this->publishedExam(school: $school);
        $this->actingAsRole(UserRole::EDUCATION_MANAGER, nucleus: $nucleus);

        $this->postJson('/api/v1/provas/'.$draftExam->id.'/turmas', [
            'turma_id' => $activeClass->id,
        ])->assertNotFound();

        foreach ([$inactiveClass, $foreignClass] as $class) {
            $this->postJson('/api/v1/provas/'.$publishedExam->id.'/turmas', [
                'turma_id' => $class->id,
            ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('turma_id', 'error.details');
        }

        $this->postJson('/api/v1/provas/'.$schoolExam->id.'/turmas', [
            'turma_id' => $sameNucleusOtherClass->id,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('turma_id', 'error.details');
    }

    public function test_school_manager_links_nucleus_exam_only_to_own_school_class(): void
    {
        $nucleus = Nucleo::factory()->create();
        $ownSchool = Escola::factory()->create(['nucleo_id' => $nucleus->id]);
        $otherSchool = Escola::factory()->create(['nucleo_id' => $nucleus->id]);
        $ownClass = Turma::factory()->create(['escola_id' => $ownSchool->id]);
        $otherClass = Turma::factory()->create(['escola_id' => $otherSchool->id]);
        $exam = $this->publishedExam(nucleus: $nucleus);
        $this->actingAsRole(UserRole::SCHOOL_MANAGER, school: $ownSchool);
        $url = '/api/v1/provas/'.$exam->id.'/turmas';

        $this->postJson($url, ['turma_id' => $ownClass->id])->assertCreated();
        $this->postJson($url, ['turma_id' => $otherClass->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('turma_id', 'error.details');
        $this->deleteJson($url.'/'.$ownClass->id)->assertOk();
    }

    public function test_nested_unlink_does_not_remove_link_from_another_exam(): void
    {
        $admin = $this->actingAsRole(UserRole::ADMINISTRATOR);
        $school = Escola::factory()->create();
        $class = Turma::factory()->create(['escola_id' => $school->id]);
        $firstExam = $this->publishedExam(nucleus: $school->nucleo);
        $secondExam = $this->publishedExam(nucleus: $school->nucleo);
        ProvaTurma::query()->create([
            'prova_id' => $secondExam->id,
            'turma_id' => $class->id,
            'vinculado_por' => $admin->id,
        ]);

        $this->deleteJson('/api/v1/provas/'.$firstExam->id.'/turmas/'.$class->id)->assertNotFound();
        $this->assertDatabaseHas('prova_turmas', [
            'prova_id' => $secondExam->id,
            'turma_id' => $class->id,
        ]);
    }

    public function test_database_rejects_incompatible_class(): void
    {
        $firstNucleus = Nucleo::factory()->create();
        $secondNucleus = Nucleo::factory()->create();
        $foreignSchool = Escola::factory()->create(['nucleo_id' => $secondNucleus->id]);
        $foreignClass = Turma::factory()->create(['escola_id' => $foreignSchool->id]);
        $exam = $this->publishedExam(nucleus: $firstNucleus);

        $this->expectException(QueryException::class);

        ProvaTurma::query()->create([
            'prova_id' => $exam->id,
            'turma_id' => $foreignClass->id,
        ]);
    }

    public function test_database_rejects_duplicate_link(): void
    {
        $school = Escola::factory()->create();
        $class = Turma::factory()->create(['escola_id' => $school->id]);
        $exam = $this->publishedExam(nucleus: $school->nucleo);
        $attributes = [
            'prova_id' => $exam->id,
            'turma_id' => $class->id,
        ];
        ProvaTurma::query()->create($attributes);

        $this->expectException(QueryException::class);

        ProvaTurma::query()->create($attributes);
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

    private function actingAsRole(
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

        Sanctum::actingAs($user, ['api']);

        return $user;
    }
}
