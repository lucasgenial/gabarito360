<?php

namespace Tests\Feature\Api\V2\Alunos;

use App\Enums\UserRole;
use App\Models\Aluno;
use App\Models\Escola;
use App\Models\MatriculaTurma;
use App\Models\Turma;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\InteractsWithIdentity;
use Tests\TestCase;

class AlunoFotoFichaAcademicTest extends TestCase
{
    use InteractsWithIdentity, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccessControlSeeder::class);
        Storage::fake('private');
    }

    public function test_upload_and_fetch_student_photo(): void
    {
        $escola = Escola::factory()->create();
        $aluno = Aluno::factory()->for($escola)->create();
        $admin = $this->userWithRole(UserRole::ADMINISTRATOR);
        $token = $this->bearerToken($admin);

        $this->withToken($token)
            ->post("/api/v2/alunos/{$aluno->id}/foto", [
                'foto' => UploadedFile::fake()->create('foto.png', 100, 'image/png'),
            ], ['Accept' => 'application/json'])
            ->assertOk();

        $this->assertNotNull($aluno->fresh()->foto_arquivo_id);
        $this->assertDatabaseHas('arquivos', [
            'proprietario_tipo' => 'aluno',
            'proprietario_id' => $aluno->id,
        ]);

        $this->withToken($token)->get("/api/v2/alunos/{$aluno->id}/foto")->assertOk();
    }

    public function test_photo_must_be_an_image(): void
    {
        $escola = Escola::factory()->create();
        $aluno = Aluno::factory()->for($escola)->create();
        $admin = $this->userWithRole(UserRole::ADMINISTRATOR);

        $this->withToken($this->bearerToken($admin))
            ->post("/api/v2/alunos/{$aluno->id}/foto", [
                'foto' => UploadedFile::fake()->create('arquivo.pdf', 100, 'application/pdf'),
            ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['foto']);
    }

    public function test_fetch_photo_returns_404_without_photo(): void
    {
        $escola = Escola::factory()->create();
        $aluno = Aluno::factory()->for($escola)->create();
        $admin = $this->userWithRole(UserRole::ADMINISTRATOR);

        $this->withToken($this->bearerToken($admin))
            ->get("/api/v2/alunos/{$aluno->id}/foto")
            ->assertNotFound();
    }

    public function test_ficha_pdf_is_generated(): void
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

        $response = $this->withToken($this->bearerToken($admin))
            ->get("/api/v2/alunos/{$aluno->id}/ficha.pdf");

        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }
}
