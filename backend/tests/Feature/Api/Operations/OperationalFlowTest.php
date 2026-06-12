<?php

namespace Tests\Feature\Api\Operations;

use App\Enums\GabaritoOficialStatus;
use App\Enums\MatriculaTurmaStatus;
use App\Enums\ModeloCartaoStatus;
use App\Enums\ProvaStatus;
use App\Enums\UserRole;
use App\Events\ApplicationProgressUpdated;
use App\Models\Aluno;
use App\Models\AplicadorTurma;
use App\Models\Arquivo;
use App\Models\Escola;
use App\Models\GabaritoOficial;
use App\Models\GabaritoResposta;
use App\Models\MatriculaTurma;
use App\Models\ModeloCartao;
use App\Models\Nucleo;
use App\Models\Perfil;
use App\Models\Prova;
use App\Models\ProvaTurma;
use App\Models\Questao;
use App\Models\Relatorio;
use App\Models\Turma;
use App\Models\User;
use App\Models\UsuarioPerfil;
use App\Services\Audit\AuditAction;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OperationalFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AccessControlSeeder::class);
        Event::fake([ApplicationProgressUpdated::class]);
        Storage::fake('private');
    }

    public function test_complete_application_reading_result_and_report_flow_is_idempotent_and_audited(): void
    {
        [$nucleus, $school, $class, $exam, $answerKey, $question, $file] = $this->foundation();
        $manager = $this->userWithRole(UserRole::EDUCATION_MANAGER, $nucleus);
        Sanctum::actingAs($manager, ['api']);

        $applicationId = $this->postJson('/api/v1/aplicacoes', [
            'prova_id' => $exam->id,
            'turma_id' => $class->id,
            'gabarito_oficial_id' => $answerKey->id,
            'titulo' => 'Aplicacao R6',
        ])->assertCreated()->json('data.id');

        $teacher = $this->userWithRole(UserRole::TEACHER);
        AplicadorTurma::factory()->create([
            'turma_id' => $class->id,
            'usuario_id' => $teacher->id,
            'fim_em' => null,
        ]);
        Sanctum::actingAs($teacher, ['api']);

        $this->postJson("/api/v1/aplicacoes/{$applicationId}/iniciar")
            ->assertOk()
            ->assertJsonPath('data.status', 'em_andamento');
        $studentsResponse = $this->getJson("/api/v1/aplicacoes/{$applicationId}/alunos")
            ->assertOk()
            ->assertJsonMissingPath('data.0.documento')
            ->assertJsonMissingPath('data.0.data_nascimento');
        $applicationStudentId = $studentsResponse->json('data.0.id');

        $readingId = $this->postJson("/api/v1/aplicacoes/{$applicationId}/leituras", [
            'operacao_id' => 'capture-r6-001',
            'aplicacao_aluno_id' => $applicationStudentId,
            'arquivo_original_id' => $file->id,
            'codigo_impresso_detectado' => ' Cartao Externo 001 ',
            'omr_versao' => '0.1.0-pre-homologation',
            'omr_configuracao_checksum' => str_repeat('a', 64),
            'confianca_geral' => 0.99,
            'requer_revisao' => true,
            'respostas' => [[
                'questao_id' => $question->id,
                'alternativa_detectada' => 'A',
                'tipo_deteccao' => 'ambigua',
                'confianca' => 0.60,
            ]],
        ])->assertCreated()->json('data.id');

        $this->withHeader('Idempotency-Key', 'confirm-r6-blocked')
            ->postJson("/api/v1/leituras/{$readingId}/confirmar")
            ->assertConflict();
        $this->patchJson("/api/v1/leituras/{$readingId}/revisar", [
            'motivo' => 'Conferencia manual da marcacao ambigua.',
            'respostas' => [[
                'questao_id' => $question->id,
                'alternativa_final' => 'A',
                'tipo_deteccao' => 'marcada',
            ]],
        ])->assertOk()->assertJsonPath('data.requer_revisao', false);

        $first = $this->withHeader('Idempotency-Key', 'confirm-r6-001')
            ->postJson("/api/v1/leituras/{$readingId}/confirmar")
            ->assertOk()
            ->assertJsonPath('data.resultado.nota_percentual', '100.0000');
        $resultId = $first->json('data.resultado.id');
        $this->withHeader('Idempotency-Key', 'confirm-r6-001')
            ->postJson("/api/v1/leituras/{$readingId}/confirmar")
            ->assertOk()
            ->assertJsonPath('data.resultado.id', $resultId);

        $this->postJson("/api/v1/aplicacoes/{$applicationId}/relatorios")
            ->assertCreated()
            ->assertJsonPath('data.status', 'concluido');
        $reportFile = Relatorio::query()->latest('solicitado_at')->firstOrFail()->arquivo()->firstOrFail();
        $this->assertStringContainsString("'=SUM(A1:A2)", Storage::disk('private')->get($reportFile->caminho));
        $this->postJson("/api/v1/aplicacoes/{$applicationId}/finalizar")
            ->assertOk()
            ->assertJsonPath('data.status', 'finalizada');

        $this->assertDatabaseCount('resultados', 1);
        $this->assertDatabaseHas('cartoes_resposta', [
            'codigo_impresso' => 'Cartao Externo 001',
            'codigo_impresso_normalizado' => 'CARTAOEXTERNO001',
        ]);
        $this->assertDatabaseHas('auditorias', ['acao' => AuditAction::READING_CONFIRMED->value, 'entidade_id' => $readingId]);
        $this->assertDatabaseHas('auditorias', ['acao' => AuditAction::READING_REVIEWED->value, 'entidade_id' => $readingId]);
        $this->assertDatabaseHas('auditorias', ['acao' => AuditAction::RESULT_CALCULATED->value, 'entidade_id' => $resultId]);
        $this->assertDatabaseHas('auditorias', ['acao' => AuditAction::REPORT_COMPLETED->value]);
    }

    /** @return array{Nucleo, Escola, Turma, Prova, GabaritoOficial, Questao, Arquivo} */
    private function foundation(): array
    {
        $nucleus = Nucleo::factory()->create();
        $school = Escola::factory()->create(['nucleo_id' => $nucleus->id]);
        $class = Turma::factory()->create(['escola_id' => $school->id]);
        $student = Aluno::factory()->create(['escola_id' => $school->id, 'nome' => '=SUM(A1:A2)']);
        MatriculaTurma::factory()->create([
            'turma_id' => $class->id,
            'aluno_id' => $student->id,
            'ano_letivo' => $class->ano_letivo,
            'status' => MatriculaTurmaStatus::ACTIVE,
        ]);
        $model = ModeloCartao::factory()->create(['status' => ModeloCartaoStatus::APPROVED]);
        $exam = Prova::factory()->create([
            'nucleo_id' => $nucleus->id,
            'escola_id' => null,
            'modelo_cartao_id' => $model->id,
            'quantidade_questoes' => 1,
            'status' => ProvaStatus::PUBLISHED,
            'publicada_at' => now(),
        ]);
        ProvaTurma::query()->create(['prova_id' => $exam->id, 'turma_id' => $class->id]);
        $question = Questao::factory()->create(['prova_id' => $exam->id, 'numero' => 1]);
        $answerKey = GabaritoOficial::factory()->create([
            'prova_id' => $exam->id,
            'status' => GabaritoOficialStatus::CURRENT,
            'publicado_por' => User::factory(),
            'publicado_at' => now(),
        ]);
        GabaritoResposta::query()->create([
            'prova_id' => $exam->id,
            'gabarito_oficial_id' => $answerKey->id,
            'questao_id' => $question->id,
            'alternativa_correta' => 'A',
            'anulada' => false,
            'peso' => 1,
        ]);
        $file = Arquivo::query()->create([
            'disco' => 'private',
            'caminho' => 'tests/card.jpg',
            'nome_original' => 'card.jpg',
            'mime' => 'image/jpeg',
            'tamanho_bytes' => 10,
            'checksum' => hash('sha256', 'card'),
            'classificacao' => 'restrito',
            'proprietario_tipo' => 'leitura_cartao',
        ]);

        return [$nucleus, $school, $class, $exam, $answerKey, $question, $file];
    }

    private function userWithRole(UserRole $role, ?Nucleo $nucleus = null): User
    {
        $user = User::factory()->create();
        UsuarioPerfil::factory()->create([
            'usuario_id' => $user->id,
            'perfil_id' => Perfil::query()->where('codigo', $role->value)->firstOrFail()->id,
            'nucleo_id' => $role === UserRole::EDUCATION_MANAGER ? $nucleus?->id : null,
            'escola_id' => null,
            'inicio_at' => now()->subMinute(),
        ]);

        return $user;
    }
}
