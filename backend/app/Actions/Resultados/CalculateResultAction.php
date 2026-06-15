<?php

namespace App\Actions\Resultados;

use App\Events\ResultCalculated;
use App\Models\AplicacaoAluno;
use App\Models\LeituraCartao;
use App\Models\Resultado;
use App\Models\ResultadoQuestao;
use App\Models\User;
use App\Services\Atividades\ActivityService;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

class CalculateResultAction
{
    public function __construct(
        private AuditService $audit,
        private ActivityService $activity,
    ) {}

    public function execute(LeituraCartao $reading, User $actor): Resultado
    {
        return DB::transaction(function () use ($reading, $actor): Resultado {
            $reading = LeituraCartao::query()
                ->with(['aplicacao.gabaritoOficial.respostas', 'aplicacao.escola', 'respostasDetectadas'])
                ->lockForUpdate()
                ->findOrFail($reading->id);
            $student = AplicacaoAluno::query()->lockForUpdate()->findOrFail($reading->aplicacao_aluno_id);
            $current = $student->resultados()->where('status', 'vigente')->lockForUpdate()->first();
            $version = ((int) $student->resultados()->max('versao')) + 1;

            $current?->update(['status' => 'substituido']);

            $totals = ['acertos' => 0, 'erros' => 0, 'brancos' => 0, 'duplas' => 0, 'anuladas' => 0];
            $score = 0.0;
            $possible = 0.0;
            $detectedByQuestion = $reading->respostasDetectadas->keyBy('questao_id');
            $rows = [];

            foreach ($reading->aplicacao->gabaritoOficial->respostas as $official) {
                $detected = $detectedByQuestion->get($official->questao_id);
                $answer = $detected?->alternativa_final ?? $detected?->alternativa_detectada;
                $weight = (float) $official->peso;
                $possible += $weight;

                if ($official->anulada) {
                    $situation = 'anulada';
                    $totals['anuladas']++;
                    $points = $weight;
                } elseif ($detected?->tipo_deteccao === 'dupla') {
                    $situation = 'dupla';
                    $totals['duplas']++;
                    $points = 0.0;
                } elseif ($answer === null || $answer === '') {
                    $situation = 'branco';
                    $totals['brancos']++;
                    $points = 0.0;
                } elseif ($answer === $official->alternativa_correta) {
                    $situation = 'correta';
                    $totals['acertos']++;
                    $points = $weight;
                } else {
                    $situation = 'incorreta';
                    $totals['erros']++;
                    $points = 0.0;
                }

                $score += $points;
                $rows[] = [
                    'questao_id' => $official->questao_id,
                    'resposta_final' => $answer,
                    'situacao' => $situation,
                    'pontuacao' => $points,
                ];
            }

            $result = Resultado::query()->create([
                'aplicacao_id' => $reading->aplicacao_id,
                'aplicacao_aluno_id' => $student->id,
                'aluno_id' => $student->aluno_id,
                'prova_id' => $reading->aplicacao->prova_id,
                'gabarito_oficial_id' => $reading->aplicacao->gabarito_oficial_id,
                'leitura_cartao_id' => $reading->id,
                'versao' => $version,
                'status' => 'vigente',
                ...$totals,
                'pontuacao' => $score,
                'nota_percentual' => $possible > 0 ? round(($score / $possible) * 100, 4) : 0,
                'calculado_at' => now(),
            ]);

            foreach ($rows as $row) {
                ResultadoQuestao::query()->create(['resultado_id' => $result->id, ...$row]);
            }

            $student->update([
                'resultado_vigente_id' => $result->id,
                'status' => 'confirmado',
                'confirmado_at' => now(),
            ]);

            $this->audit->record(
                AuditAction::RESULT_CALCULATED,
                'resultado',
                $result->id,
                $actor->id,
                after: $result->only(['aplicacao_id', 'aplicacao_aluno_id', 'versao', 'nota_percentual', 'status']),
                metadata: ['leitura_cartao_id' => $reading->id],
                nucleoId: $reading->aplicacao->escola->nucleo_id,
                escolaId: $reading->aplicacao->escola_id,
            );
            ResultCalculated::dispatch($result);
            $this->activity->record('resultado.calculado', 'Resultado calculado e disponível.', [
                'ator_id' => $actor->id,
                'nucleo_id' => $reading->aplicacao->escola->nucleo_id,
                'escola_id' => $reading->aplicacao->escola_id,
                'sujeito_tipo' => 'resultado',
                'sujeito_id' => $result->id,
                'dados' => ['aplicacao_id' => $result->aplicacao_id, 'nota_percentual' => (float) $result->nota_percentual],
            ]);

            return $result->refresh();
        });
    }
}
