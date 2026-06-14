<?php

namespace Tests\Feature\Api\V2\Turmas;

use App\Enums\UserRole;
use App\Models\Escola;
use App\Models\Turma;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\InteractsWithIdentity;
use Tests\TestCase;

class TurmaImportAcademicTest extends TestCase
{
    use InteractsWithIdentity, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccessControlSeeder::class);
        Storage::fake('private');
    }

    private function csv(string $content): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('alunos.csv', $content);
    }

    public function test_valid_import_creates_students_and_enrollments(): void
    {
        $escola = Escola::factory()->create();
        $turma = Turma::factory()->for($escola)->create();
        $admin = $this->userWithRole(UserRole::ADMINISTRATOR);

        $csv = "matricula,codigo_interno,nome,numero_chamada\n"
            ."A001,,Joao Silva,1\n"
            ."A002,,Maria Souza,2\n";

        $response = $this->withToken($this->bearerToken($admin))
            ->withHeader('Idempotency-Key', 'import-001')
            ->post('/api/v2/turmas/importar', [
                'turma_id' => $turma->id,
                'arquivo' => $this->csv($csv),
            ], ['Accept' => 'application/json']);

        $response
            ->assertStatus(202)
            ->assertJsonPath('data.status', 'concluida')
            ->assertJsonPath('data.resumo.inclusoes', 2);

        $this->assertDatabaseHas('alunos', ['escola_id' => $escola->id, 'matricula' => 'A001']);
        $this->assertDatabaseCount('matriculas_turmas', 2);
    }

    public function test_import_is_idempotent(): void
    {
        $escola = Escola::factory()->create();
        $turma = Turma::factory()->for($escola)->create();
        $admin = $this->userWithRole(UserRole::ADMINISTRATOR);
        $token = $this->bearerToken($admin);
        $csv = "matricula,codigo_interno,nome,numero_chamada\nB001,,Ana Lima,1\n";

        $this->withToken($token)->withHeader('Idempotency-Key', 'dup')
            ->post('/api/v2/turmas/importar', ['turma_id' => $turma->id, 'arquivo' => $this->csv($csv)], ['Accept' => 'application/json'])
            ->assertStatus(202);

        $this->withToken($token)->withHeader('Idempotency-Key', 'dup')
            ->post('/api/v2/turmas/importar', ['turma_id' => $turma->id, 'arquivo' => $this->csv($csv)], ['Accept' => 'application/json'])
            ->assertStatus(202);

        $this->assertDatabaseCount('alunos', 1);
        $this->assertDatabaseCount('matriculas_turmas', 1);
    }

    public function test_invalid_header_returns_validation_error(): void
    {
        $escola = Escola::factory()->create();
        $turma = Turma::factory()->for($escola)->create();
        $admin = $this->userWithRole(UserRole::ADMINISTRATOR);

        $this->withToken($this->bearerToken($admin))
            ->withHeader('Idempotency-Key', 'import-bad')
            ->post('/api/v2/turmas/importar', [
                'turma_id' => $turma->id,
                'arquivo' => $this->csv("nome,matricula\nJoao,A001\n"),
            ], ['Accept' => 'application/json'])
            ->assertStatus(422);

        $this->assertDatabaseCount('alunos', 0);
    }

    public function test_viewer_cannot_import(): void
    {
        $escola = Escola::factory()->create();
        $turma = Turma::factory()->for($escola)->create();
        $viewer = $this->userWithRole(UserRole::VIEWER, escolaId: $escola->id);

        $this->withToken($this->bearerToken($viewer))
            ->withHeader('Idempotency-Key', 'import-viewer')
            ->post('/api/v2/turmas/importar', [
                'turma_id' => $turma->id,
                'arquivo' => $this->csv("matricula,codigo_interno,nome,numero_chamada\nC001,,X,1\n"),
            ], ['Accept' => 'application/json'])
            ->assertForbidden();
    }
}
