<?php

namespace Tests\Feature\Api\StudentImports;

use App\Enums\MatriculaTurmaStatus;
use App\Enums\StudentImportStatus;
use App\Enums\UserRole;
use App\Jobs\ImportStudentsJob;
use App\Models\Aluno;
use App\Models\Auditoria;
use App\Models\Escola;
use App\Models\ImportacaoAluno;
use App\Models\MatriculaTurma;
use App\Models\Perfil;
use App\Models\Turma;
use App\Models\User;
use App\Models\UsuarioPerfil;
use App\Services\Audit\AuditAction;
use App\Services\Import\StudentCsvImporter;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

class StudentImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccessControlSeeder::class);
        Storage::fake('private');
    }

    public function test_valid_csv_is_previewed_then_confirmed_and_processed_without_partial_writes(): void
    {
        Queue::fake();
        $school = Escola::factory()->create();
        $class = Turma::factory()->create([
            'escola_id' => $school->id,
            'ano_letivo' => 2026,
        ]);
        $manager = $this->actingAsRole(UserRole::SCHOOL_MANAGER, $school);

        $response = $this->upload($school, $class, <<<'CSV'
matricula,codigo_interno,nome,numero_chamada
 mat-001 , INT-01 , Aluno Um , 1
MAT-002,,Aluno Dois,2
CSV);
        $importId = $response->json('data.id');

        $response
            ->assertCreated()
            ->assertJsonPath('data.status', StudentImportStatus::VALIDATED->value)
            ->assertJsonPath('data.resumo.linhas', 2)
            ->assertJsonPath('data.resumo.inclusoes', 2)
            ->assertJsonPath('data.resumo.matriculas_novas', 2)
            ->assertJsonPath('data.resumo.erros', 0)
            ->assertJsonCount(0, 'data.erros')
            ->assertJsonMissingPath('data.arquivo_caminho')
            ->assertJsonMissingPath('data.arquivo_checksum_sha256');
        $this->assertDatabaseCount('alunos', 0);
        $this->assertDatabaseCount('matriculas_turmas', 0);

        $this->postJson('/api/v1/alunos/importacoes/'.$importId.'/confirmar')
            ->assertAccepted()
            ->assertJsonPath('data.status', StudentImportStatus::PROCESSING->value);
        $this->postJson('/api/v1/alunos/importacoes/'.$importId.'/confirmar')
            ->assertAccepted()
            ->assertJsonPath('data.status', StudentImportStatus::PROCESSING->value);

        Queue::assertPushed(ImportStudentsJob::class, 1);
        app(StudentCsvImporter::class)->process($importId);
        app(StudentCsvImporter::class)->process($importId);

        $this->assertDatabaseCount('alunos', 2);
        $this->assertDatabaseCount('matriculas_turmas', 2);
        $this->assertDatabaseHas('alunos', [
            'escola_id' => $school->id,
            'matricula' => 'MAT-001',
            'codigo_interno' => 'INT-01',
            'nome' => 'Aluno Um',
        ]);
        $this->assertDatabaseHas('matriculas_turmas', [
            'turma_id' => $class->id,
            'ano_letivo' => 2026,
            'numero_chamada' => '1',
            'status' => MatriculaTurmaStatus::ACTIVE->value,
        ]);
        $this->assertDatabaseHas('importacoes_alunos', [
            'id' => $importId,
            'status' => StudentImportStatus::COMPLETED->value,
            'confirmado_por' => $manager->id,
        ]);
        $this->assertDatabaseHas('auditorias', [
            'acao' => AuditAction::STUDENT_IMPORT_CREATED->value,
            'entidade_id' => $importId,
        ]);
        $this->assertDatabaseHas('auditorias', [
            'acao' => AuditAction::STUDENT_IMPORT_CONFIRMED->value,
            'entidade_id' => $importId,
        ]);
        $this->assertDatabaseHas('auditorias', [
            'acao' => AuditAction::STUDENT_IMPORT_COMPLETED->value,
            'entidade_id' => $importId,
        ]);
        $auditPayload = Auditoria::query()
            ->where('acao', AuditAction::STUDENT_IMPORT_COMPLETED->value)
            ->where('entidade_id', $importId)
            ->firstOrFail()
            ->only(['dados_anteriores', 'dados_novos', 'metadados']);
        $this->assertStringNotContainsString('Aluno Um', json_encode($auditPayload, JSON_THROW_ON_ERROR));
        $this->assertStringNotContainsString('MAT-001', json_encode($auditPayload, JSON_THROW_ON_ERROR));
    }

    public function test_invalid_rows_are_reported_before_confirmation_and_nothing_is_persisted(): void
    {
        Queue::fake();
        $school = Escola::factory()->create();
        $targetClass = Turma::factory()->create([
            'escola_id' => $school->id,
            'ano_letivo' => 2026,
        ]);
        $otherClass = Turma::factory()->create([
            'escola_id' => $school->id,
            'ano_letivo' => 2026,
        ]);
        $student = Aluno::factory()->create([
            'escola_id' => $school->id,
            'matricula' => 'MAT-CONFLITO',
        ]);
        MatriculaTurma::factory()->create([
            'aluno_id' => $student->id,
            'turma_id' => $otherClass->id,
            'ano_letivo' => 2026,
        ]);
        $this->actingAsRole(UserRole::SCHOOL_MANAGER, $school);

        $response = $this->upload($school, $targetClass, <<<'CSV'
matricula,codigo_interno,nome,numero_chamada
MAT-CONFLITO,,Aluno Conflito,1
MAT-DUP,,Aluno Duplicado,2
MAT-DUP,,Aluno Duplicado,3
MAT-SEM-NOME,,,4
CSV);
        $importId = $response->json('data.id');

        $response
            ->assertCreated()
            ->assertJsonPath('data.status', StudentImportStatus::HAS_ERRORS->value)
            ->assertJsonPath('data.resumo.erros', 3)
            ->assertJsonFragment(['linha' => 2, 'campo' => 'matricula'])
            ->assertJsonFragment(['linha' => 4, 'campo' => 'matricula'])
            ->assertJsonFragment(['linha' => 5, 'campo' => 'nome']);

        $this->postJson('/api/v1/alunos/importacoes/'.$importId.'/confirmar')->assertConflict();
        $this->assertDatabaseCount('alunos', 1);
        $this->assertDatabaseCount('matriculas_turmas', 1);
        Queue::assertNothingPushed();
    }

    public function test_reupload_and_reconfirmation_do_not_duplicate_students_or_enrollments(): void
    {
        Queue::fake();
        $school = Escola::factory()->create();
        $class = Turma::factory()->create(['escola_id' => $school->id]);
        $this->actingAsRole(UserRole::SCHOOL_MANAGER, $school);
        $csv = <<<'CSV'
matricula,codigo_interno,nome,numero_chamada
MAT-IDEMP,INT-IDEMP,Aluno Idempotente,10
CSV;

        $firstId = $this->upload($school, $class, $csv)->json('data.id');
        $this->postJson('/api/v1/alunos/importacoes/'.$firstId.'/confirmar')->assertAccepted();
        app(StudentCsvImporter::class)->process($firstId);

        $second = $this->upload($school, $class, <<<'CSV'
matricula,codigo_interno,nome,numero_chamada
MAT-IDEMP,INT-NOVO,Aluno Atualizado,11
CSV);
        $secondId = $second->json('data.id');

        $second
            ->assertCreated()
            ->assertJsonPath('data.status', StudentImportStatus::VALIDATED->value)
            ->assertJsonPath('data.resumo.inclusoes', 0)
            ->assertJsonPath('data.resumo.atualizacoes', 1)
            ->assertJsonPath('data.resumo.matriculas_existentes', 1);

        $this->postJson('/api/v1/alunos/importacoes/'.$secondId.'/confirmar')->assertAccepted();
        app(StudentCsvImporter::class)->process($secondId);

        $this->assertDatabaseCount('alunos', 1);
        $this->assertDatabaseCount('matriculas_turmas', 1);
        $this->assertDatabaseHas('alunos', [
            'matricula' => 'MAT-IDEMP',
            'codigo_interno' => 'INT-NOVO',
            'nome' => 'Aluno Atualizado',
        ]);
        $this->assertDatabaseHas('matriculas_turmas', [
            'turma_id' => $class->id,
            'numero_chamada' => '11',
        ]);
    }

    public function test_import_is_restricted_to_authorized_school_and_hidden_outside_scope(): void
    {
        $ownSchool = Escola::factory()->create();
        $otherSchool = Escola::factory()->create();
        $ownClass = Turma::factory()->create(['escola_id' => $ownSchool->id]);
        $otherClass = Turma::factory()->create(['escola_id' => $otherSchool->id]);
        $foreignImport = ImportacaoAluno::factory()->create([
            'escola_id' => $otherSchool->id,
            'turma_id' => $otherClass->id,
        ]);
        $this->actingAsRole(UserRole::SCHOOL_MANAGER, $ownSchool);

        $this->getJson('/api/v1/alunos/importacoes/'.$foreignImport->id)->assertNotFound();
        $this->postJson('/api/v1/alunos/importacoes/'.$foreignImport->id.'/confirmar')->assertNotFound();
        $this->upload($otherSchool, $otherClass, $this->validCsv())->assertForbidden();

        $this->actingAsRole(UserRole::TEACHER, $ownSchool);
        $this->upload($ownSchool, $ownClass, $this->validCsv())->assertForbidden();
    }

    public function test_file_format_and_header_are_validated_before_confirmation(): void
    {
        $school = Escola::factory()->create();
        $class = Turma::factory()->create(['escola_id' => $school->id]);
        $this->actingAsRole(UserRole::SCHOOL_MANAGER, $school);

        $this->post('/api/v1/alunos/importacoes', [
            'escola_id' => $school->id,
            'turma_id' => $class->id,
            'arquivo' => UploadedFile::fake()->createWithContent('alunos.txt', $this->validCsv()),
        ], [
            'Accept' => 'application/json',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('arquivo', 'error.details');

        $this->upload($school, $class, "nome,matricula\nAluno,MAT-1")
            ->assertCreated()
            ->assertJsonPath('data.status', StudentImportStatus::HAS_ERRORS->value)
            ->assertJsonPath('data.erros.0.linha', 1)
            ->assertJsonPath('data.erros.0.campo', 'cabecalho');

        $this->assertDatabaseCount('alunos', 0);
        $this->assertDatabaseCount('matriculas_turmas', 0);
    }

    public function test_changed_file_is_not_processed_after_preview(): void
    {
        Queue::fake();
        $school = Escola::factory()->create();
        $class = Turma::factory()->create(['escola_id' => $school->id]);
        $this->actingAsRole(UserRole::SCHOOL_MANAGER, $school);
        $importId = $this->upload($school, $class, $this->validCsv())->json('data.id');
        $this->postJson('/api/v1/alunos/importacoes/'.$importId.'/confirmar')->assertAccepted();
        $import = ImportacaoAluno::query()->findOrFail($importId);

        Storage::disk('private')->put($import->arquivo_caminho, $this->validCsv()."\nMAT-TAMPER,,Alterado,2");

        try {
            app(StudentCsvImporter::class)->process($importId);
            $this->fail('A importacao deveria rejeitar o arquivo alterado.');
        } catch (RuntimeException) {
            //
        }

        $this->assertDatabaseCount('alunos', 0);
    }

    private function upload(Escola $school, Turma $class, string $csv)
    {
        return $this->post('/api/v1/alunos/importacoes', [
            'escola_id' => $school->id,
            'turma_id' => $class->id,
            'arquivo' => UploadedFile::fake()->createWithContent('importacao-alunos.csv', $csv),
        ], [
            'Accept' => 'application/json',
        ]);
    }

    private function validCsv(): string
    {
        return <<<'CSV'
matricula,codigo_interno,nome,numero_chamada
MAT-VALIDA,,Aluno Valido,1
CSV;
    }

    private function actingAsRole(UserRole $role, Escola $school): User
    {
        $user = User::factory()->create();
        $profile = Perfil::query()->where('codigo', $role->value)->firstOrFail();

        UsuarioPerfil::factory()->create([
            'usuario_id' => $user->id,
            'perfil_id' => $profile->id,
            'escola_id' => $school->id,
            'inicio_at' => now()->subMinute(),
        ]);

        Sanctum::actingAs($user, ['api']);

        return $user;
    }
}
