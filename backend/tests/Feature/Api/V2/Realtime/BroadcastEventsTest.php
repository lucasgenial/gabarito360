<?php

namespace Tests\Feature\Api\V2\Realtime;

use App\Events\ReadingConfirmed;
use App\Events\ReadingReviewRequired;
use App\Events\ResultCalculated;
use Database\Seeders\AcademicCatalogSeeder;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Concerns\InteractsWithIdentity;
use Tests\Concerns\InteractsWithOperations;
use Tests\TestCase;

class BroadcastEventsTest extends TestCase
{
    use InteractsWithIdentity, InteractsWithOperations, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccessControlSeeder::class);
        $this->seed(AcademicCatalogSeeder::class);
    }

    public function test_leitura_ambigua_dispara_reading_review_required(): void
    {
        Event::fake([ReadingReviewRequired::class]);

        $ctx = $this->bootstrapOperations(['questoes' => 3, 'alunos' => 1]);
        $application = $this->operations()->startedApplication($ctx, $ctx['gestor'], [$ctx['aplicador']]);
        $aluno = $application->alunos()->firstOrFail();

        $reading = $this->operations()->captureReading($application, $aluno, $ctx['aplicador'], ['ambigua_numero' => 2]);

        Event::assertDispatched(
            ReadingReviewRequired::class,
            fn (ReadingReviewRequired $event): bool => $event->reading->id === $reading->id,
        );
    }

    public function test_confirmar_leitura_dispara_reading_confirmed_e_result_calculated(): void
    {
        Event::fake([ReadingConfirmed::class, ResultCalculated::class]);

        $ctx = $this->bootstrapOperations(['questoes' => 3, 'alunos' => 1]);
        $application = $this->operations()->startedApplication($ctx, $ctx['gestor'], [$ctx['aplicador']]);
        $aluno = $application->alunos()->firstOrFail();
        $reading = $this->operations()->captureReading($application, $aluno, $ctx['aplicador']);

        $this->actingAsToken($ctx['aplicador'])
            ->postJson("/api/v2/leituras/{$reading->id}/confirmar", [], ['Idempotency-Key' => 'bc-'.$reading->id])
            ->assertOk();

        Event::assertDispatched(ReadingConfirmed::class, fn (ReadingConfirmed $e): bool => $e->reading->id === $reading->id);
        Event::assertDispatched(ResultCalculated::class, fn (ResultCalculated $e): bool => $e->resultado->aplicacao_id === $application->id);
    }

    public function test_eventos_de_leitura_usam_canal_privado_da_aplicacao(): void
    {
        $ctx = $this->bootstrapOperations(['questoes' => 2, 'alunos' => 1]);
        $application = $this->operations()->startedApplication($ctx, $ctx['gestor'], [$ctx['aplicador']]);
        $aluno = $application->alunos()->firstOrFail();
        $reading = $this->operations()->captureReading($application, $aluno, $ctx['aplicador']);

        $event = new ReadingConfirmed($reading);
        $channels = $event->broadcastOn();

        $this->assertSame('private-applications.'.$application->id, $channels[0]->name);
        $this->assertSame('reading.confirmed', $event->broadcastAs());
    }
}
