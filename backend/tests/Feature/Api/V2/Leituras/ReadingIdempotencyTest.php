<?php

namespace Tests\Feature\Api\V2\Leituras;

use App\Models\LeituraCartao;
use App\Models\Resultado;
use Database\Seeders\AcademicCatalogSeeder;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\InteractsWithIdentity;
use Tests\Concerns\InteractsWithOperations;
use Tests\TestCase;

class ReadingIdempotencyTest extends TestCase
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
     * @param  list<array<string, mixed>>  $respostas
     * @return array<string, mixed>
     */
    private function payload(string $alunoId, string $operacao, array $respostas): array
    {
        return [
            'imagem' => UploadedFile::fake()->create('cartao.jpg', 120, 'image/jpeg'),
            'aplicacao_aluno_id' => $alunoId,
            'operacao_id' => $operacao,
            'respostas' => $respostas,
        ];
    }

    public function test_capture_is_idempotent_by_operacao_id(): void
    {
        $ctx = $this->bootstrapOperations(['questoes' => 2]);
        $application = $this->operations()->startedApplication($ctx, $ctx['gestor'], [$ctx['aplicador']]);
        $aluno = $application->alunos()->firstOrFail();
        $token = $this->bearerToken($ctx['aplicador']);
        $respostas = [
            ['numero' => 1, 'alternativa_detectada' => 'A', 'tipo_deteccao' => 'marcada', 'confianca' => 0.95],
            ['numero' => 2, 'alternativa_detectada' => 'B', 'tipo_deteccao' => 'marcada', 'confianca' => 0.9],
        ];

        $first = $this->withToken($token)->post(
            "/api/v2/aplicacoes/{$application->id}/leituras",
            $this->payload($aluno->id, 'op-dup', $respostas),
            ['Accept' => 'application/json'],
        )->assertCreated()->json('data.id');

        $this->withToken($token)->post(
            "/api/v2/aplicacoes/{$application->id}/leituras",
            $this->payload($aluno->id, 'op-dup', $respostas),
            ['Accept' => 'application/json'],
        )->assertOk()->assertJsonPath('data.id', $first);

        $this->assertSame(1, LeituraCartao::query()->where('aplicacao_id', $application->id)->count());
    }

    public function test_capture_reused_operacao_with_different_payload_conflicts(): void
    {
        $ctx = $this->bootstrapOperations(['questoes' => 2]);
        $application = $this->operations()->startedApplication($ctx, $ctx['gestor'], [$ctx['aplicador']]);
        $aluno = $application->alunos()->firstOrFail();
        $token = $this->bearerToken($ctx['aplicador']);

        $this->withToken($token)->post(
            "/api/v2/aplicacoes/{$application->id}/leituras",
            $this->payload($aluno->id, 'op-conflict', [
                ['numero' => 1, 'alternativa_detectada' => 'A', 'tipo_deteccao' => 'marcada', 'confianca' => 0.95],
                ['numero' => 2, 'alternativa_detectada' => 'B', 'tipo_deteccao' => 'marcada', 'confianca' => 0.9],
            ]),
            ['Accept' => 'application/json'],
        )->assertCreated();

        $this->withToken($token)->post(
            "/api/v2/aplicacoes/{$application->id}/leituras",
            $this->payload($aluno->id, 'op-conflict', [
                ['numero' => 1, 'alternativa_detectada' => 'D', 'tipo_deteccao' => 'marcada', 'confianca' => 0.95],
                ['numero' => 2, 'alternativa_detectada' => 'E', 'tipo_deteccao' => 'marcada', 'confianca' => 0.9],
            ]),
            ['Accept' => 'application/json'],
        )->assertStatus(409);
    }

    public function test_confirm_is_idempotent_with_same_key(): void
    {
        $ctx = $this->bootstrapOperations(['questoes' => 3]);
        $application = $this->operations()->startedApplication($ctx, $ctx['gestor'], [$ctx['aplicador']]);
        $aluno = $application->alunos()->firstOrFail();
        $reading = $this->operations()->captureReading($application, $aluno, $ctx['aplicador']);
        $token = $this->bearerToken($ctx['aplicador']);

        $this->withToken($token)
            ->postJson("/api/v2/leituras/{$reading->id}/confirmar", [], ['Idempotency-Key' => 'confirm-key'])
            ->assertOk();

        $this->withToken($token)
            ->postJson("/api/v2/leituras/{$reading->id}/confirmar", [], ['Idempotency-Key' => 'confirm-key'])
            ->assertOk();

        $this->assertSame(
            1,
            Resultado::query()->where('aplicacao_aluno_id', $aluno->id)->where('status', 'vigente')->count(),
        );
    }
}
