<?php

namespace Tests\Feature\Api\V2\Leituras;

use App\Models\Prova;
use Database\Seeders\AcademicCatalogSeeder;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\InteractsWithIdentity;
use Tests\Concerns\InteractsWithOperations;
use Tests\TestCase;

class ReadingCaptureTest extends TestCase
{
    use InteractsWithIdentity, InteractsWithOperations, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccessControlSeeder::class);
        $this->seed(AcademicCatalogSeeder::class);
        Storage::fake((string) config('filesystems.private'));
    }

    /**
     * @param  array<string, mixed>  $override
     * @return array<string, mixed>
     */
    private function payload(string $alunoId, string $operacao, array $override = []): array
    {
        return array_merge([
            'imagem' => UploadedFile::fake()->create('cartao.jpg', 120, 'image/jpeg'),
            'aplicacao_aluno_id' => $alunoId,
            'operacao_id' => $operacao,
            'respostas' => [
                ['numero' => 1, 'alternativa_detectada' => 'A', 'tipo_deteccao' => 'marcada', 'confianca' => 0.95],
                ['numero' => 2, 'alternativa_detectada' => 'B', 'tipo_deteccao' => 'marcada', 'confianca' => 0.93],
            ],
        ], $override);
    }

    public function test_capture_creates_reading_with_detected_answers(): void
    {
        $ctx = $this->bootstrapOperations(['questoes' => 2]);
        $application = $this->operations()->startedApplication($ctx, $ctx['gestor'], [$ctx['aplicador']]);
        $aluno = $application->alunos()->firstOrFail();

        $response = $this->withToken($this->bearerToken($ctx['aplicador']))->post(
            "/api/v2/aplicacoes/{$application->id}/leituras",
            $this->payload($aluno->id, 'op-capture-1'),
            ['Accept' => 'application/json'],
        );

        $response->assertCreated()
            ->assertJsonPath('data.requer_revisao', false)
            ->assertJsonCount(2, 'data.respostas');

        $this->assertDatabaseHas('leituras_cartao', [
            'aplicacao_id' => $application->id,
            'operacao_id' => 'op-capture-1',
            'requer_revisao' => false,
        ]);
    }

    public function test_capture_with_ambiguous_answer_requires_review(): void
    {
        $ctx = $this->bootstrapOperations(['questoes' => 2]);
        $application = $this->operations()->startedApplication($ctx, $ctx['gestor'], [$ctx['aplicador']]);
        $aluno = $application->alunos()->firstOrFail();

        $this->withToken($this->bearerToken($ctx['aplicador']))->post(
            "/api/v2/aplicacoes/{$application->id}/leituras",
            $this->payload($aluno->id, 'op-ambig', [
                'respostas' => [
                    ['numero' => 1, 'alternativa_detectada' => 'A', 'tipo_deteccao' => 'marcada', 'confianca' => 0.95],
                    ['numero' => 2, 'tipo_deteccao' => 'ambigua', 'confianca' => 0.3],
                ],
            ]),
            ['Accept' => 'application/json'],
        )->assertCreated()->assertJsonPath('data.requer_revisao', true);
    }

    public function test_capture_requires_application_in_progress(): void
    {
        $ctx = $this->bootstrapOperations(['questoes' => 2]);
        $application = $this->operations()->createApplication($ctx, $ctx['gestor'], [$ctx['aplicador']]);
        $aluno = $application->alunos()->firstOrFail();

        $this->withToken($this->bearerToken($ctx['aplicador']))->post(
            "/api/v2/aplicacoes/{$application->id}/leituras",
            $this->payload($aluno->id, 'op-not-started'),
            ['Accept' => 'application/json'],
        )->assertStatus(409);
    }

    public function test_capture_without_card_model_is_rejected(): void
    {
        $ctx = $this->bootstrapOperations(['questoes' => 2]);
        $application = $this->operations()->startedApplication($ctx, $ctx['gestor'], [$ctx['aplicador']]);
        $aluno = $application->alunos()->firstOrFail();
        Prova::query()->whereKey($ctx['prova']->id)->update(['modelo_cartao_id' => null]);

        $this->withToken($this->bearerToken($ctx['aplicador']))->post(
            "/api/v2/aplicacoes/{$application->id}/leituras",
            $this->payload($aluno->id, 'op-no-model'),
            ['Accept' => 'application/json'],
        )->assertUnprocessable()->assertJsonValidationErrors(['prova']);
    }
}
