<?php

namespace Tests\Feature\Api\V2\Alunos;

use App\Enums\UserRole;
use App\Models\Aluno;
use App\Models\Escola;
use App\Models\Turma;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithIdentity;
use Tests\TestCase;

class AlunoAcademicTest extends TestCase
{
    use InteractsWithIdentity, RefreshDatabase;

    private const CPF_VALIDO = '111.444.777-35';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccessControlSeeder::class);
    }

    public function test_create_student_with_enrollment_and_guardian(): void
    {
        $escola = Escola::factory()->create();
        $turma = Turma::factory()->for($escola)->create();
        $admin = $this->userWithRole(UserRole::ADMINISTRATOR);

        $response = $this->withToken($this->bearerToken($admin))
            ->postJson('/api/v2/alunos', [
                'nome' => 'Pedro Aluno',
                'matricula' => 'M-001',
                'turma_id' => $turma->id,
                'responsavel' => 'Maria Mae',
                'cpf' => self::CPF_VALIDO,
                'genero' => 'masculino',
                'data_nascimento' => '2014-05-10',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.nome', 'Pedro Aluno')
            ->assertJsonPath('data.cpf', '11144477735')
            ->assertJsonPath('data.genero', 'masculino')
            ->assertJsonPath('data.responsavel', 'Maria Mae')
            ->assertJsonPath('data.turma_id', $turma->id)
            ->assertJsonPath('data.status', 'ativo');

        $aluno = Aluno::query()->where('matricula', 'M-001')->firstOrFail();
        $this->assertSame($escola->id, $aluno->escola_id);
        $this->assertDatabaseHas('matriculas_turmas', ['aluno_id' => $aluno->id, 'turma_id' => $turma->id, 'status' => 'ativa']);
        $this->assertDatabaseHas('responsaveis', ['nome' => 'Maria Mae']);
        $this->assertDatabaseHas('aluno_responsaveis', ['aluno_id' => $aluno->id, 'principal' => true]);
    }

    public function test_show_returns_contract_shape(): void
    {
        $escola = Escola::factory()->create();
        $turma = Turma::factory()->for($escola)->create();
        $admin = $this->userWithRole(UserRole::ADMINISTRATOR);
        $token = $this->bearerToken($admin);

        $this->withToken($token)->postJson('/api/v2/alunos', [
            'nome' => 'Ana Aluna', 'matricula' => 'M-002', 'turma_id' => $turma->id,
        ])->assertCreated();

        $aluno = Aluno::query()->where('matricula', 'M-002')->firstOrFail();

        $this->withToken($token)->getJson("/api/v2/alunos/{$aluno->id}")
            ->assertOk()
            ->assertJsonPath('data.matricula', 'M-002')
            ->assertJsonPath('data.turma_id', $turma->id)
            ->assertJsonPath('data.status', 'ativo')
            ->assertJsonPath('data.media_geral', 0)
            ->assertJsonPath('data.frequencia', 0);
    }

    public function test_update_can_inactivate_student(): void
    {
        $escola = Escola::factory()->create();
        $turma = Turma::factory()->for($escola)->create();
        $admin = $this->userWithRole(UserRole::ADMINISTRATOR);
        $token = $this->bearerToken($admin);

        $this->withToken($token)->postJson('/api/v2/alunos', [
            'nome' => 'Carlos', 'matricula' => 'M-003', 'turma_id' => $turma->id,
        ])->assertCreated();
        $aluno = Aluno::query()->where('matricula', 'M-003')->firstOrFail();

        $this->withToken($token)->putJson("/api/v2/alunos/{$aluno->id}", ['status' => 'inativo'])
            ->assertOk()
            ->assertJsonPath('data.status', 'inativo');

        $this->assertDatabaseHas('alunos', ['id' => $aluno->id, 'status' => 'inativo']);
    }

    public function test_transfer_creates_new_enrollment_and_closes_old(): void
    {
        $escola = Escola::factory()->create();
        $turmaA = Turma::factory()->for($escola)->create();
        $turmaB = Turma::factory()->for($escola)->create(['ano_letivo' => $turmaA->ano_letivo]);
        $admin = $this->userWithRole(UserRole::ADMINISTRATOR);
        $token = $this->bearerToken($admin);

        $this->withToken($token)->postJson('/api/v2/alunos', [
            'nome' => 'Bia', 'matricula' => 'M-004', 'turma_id' => $turmaA->id,
        ])->assertCreated();
        $aluno = Aluno::query()->where('matricula', 'M-004')->firstOrFail();

        $this->withToken($token)->putJson("/api/v2/alunos/{$aluno->id}", ['turma_id' => $turmaB->id])
            ->assertOk()
            ->assertJsonPath('data.turma_id', $turmaB->id);

        $this->assertDatabaseHas('matriculas_turmas', ['aluno_id' => $aluno->id, 'turma_id' => $turmaA->id, 'status' => 'transferida']);
        $this->assertDatabaseHas('matriculas_turmas', ['aluno_id' => $aluno->id, 'turma_id' => $turmaB->id, 'status' => 'ativa']);
    }

    public function test_invalid_cpf_is_rejected(): void
    {
        $escola = Escola::factory()->create();
        $turma = Turma::factory()->for($escola)->create();
        $admin = $this->userWithRole(UserRole::ADMINISTRATOR);

        $this->withToken($this->bearerToken($admin))
            ->postJson('/api/v2/alunos', [
                'nome' => 'Z', 'matricula' => 'M-005', 'turma_id' => $turma->id, 'cpf' => '111.111.111-11',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['cpf']);
    }

    public function test_avaliacoes_returns_empty_list(): void
    {
        $escola = Escola::factory()->create();
        $aluno = Aluno::factory()->for($escola)->create();
        $admin = $this->userWithRole(UserRole::ADMINISTRATOR);

        $this->withToken($this->bearerToken($admin))
            ->getJson("/api/v2/alunos/{$aluno->id}/avaliacoes")
            ->assertOk()
            ->assertJsonPath('meta.total', 0);
    }

    public function test_viewer_cannot_create_student(): void
    {
        $escola = Escola::factory()->create();
        $turma = Turma::factory()->for($escola)->create();
        $viewer = $this->userWithRole(UserRole::VIEWER, escolaId: $escola->id);

        $this->withToken($this->bearerToken($viewer))
            ->postJson('/api/v2/alunos', ['nome' => 'X', 'matricula' => 'M-006', 'turma_id' => $turma->id])
            ->assertForbidden();
    }
}
