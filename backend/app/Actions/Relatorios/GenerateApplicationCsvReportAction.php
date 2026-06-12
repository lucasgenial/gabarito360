<?php

namespace App\Actions\Relatorios;

use App\Models\Aplicacao;
use App\Models\Arquivo;
use App\Models\Relatorio;
use App\Models\User;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class GenerateApplicationCsvReportAction
{
    public function __construct(private AuditService $audit) {}

    public function execute(Aplicacao $application, User $actor): Relatorio
    {
        $report = Relatorio::query()->create([
            'tipo' => 'resultados_aplicacao',
            'formato' => 'csv',
            'solicitante_id' => $actor->id,
            'filtros' => ['aplicacao_id' => $application->id],
            'escopo' => [
                'nucleo_id' => $application->escola->nucleo_id,
                'escola_id' => $application->escola_id,
                'aplicacao_id' => $application->id,
            ],
            'status' => 'processando',
            'solicitado_at' => now(),
        ]);

        $this->audit->record(
            AuditAction::REPORT_REQUESTED,
            'relatorio',
            $report->id,
            $actor->id,
            after: $report->only(['tipo', 'formato', 'status', 'escopo']),
            nucleoId: $application->escola->nucleo_id,
            escolaId: $application->escola_id,
        );

        return DB::transaction(function () use ($application, $actor, $report): Relatorio {
            $csv = $this->csv($application);
            $path = 'reports/'.$report->id.'.csv';
            Storage::disk(config('filesystems.private'))->put($path, $csv);

            $file = Arquivo::query()->create([
                'disco' => config('filesystems.private'),
                'caminho' => $path,
                'nome_original' => 'resultados-'.$application->id.'.csv',
                'mime' => 'text/csv',
                'tamanho_bytes' => strlen($csv),
                'checksum' => hash('sha256', $csv),
                'classificacao' => 'restrito',
                'proprietario_tipo' => 'relatorio',
                'proprietario_id' => $report->id,
                'criado_por_id' => $actor->id,
                'reter_ate' => now()->addDays(30),
            ]);

            $report->update([
                'arquivo_id' => $file->id,
                'status' => 'concluido',
                'concluido_at' => now(),
                'expira_at' => now()->addDays(30),
            ]);

            $this->audit->record(
                AuditAction::REPORT_COMPLETED,
                'relatorio',
                $report->id,
                $actor->id,
                after: $report->only(['arquivo_id', 'status', 'concluido_at', 'expira_at']),
                metadata: ['checksum' => $file->checksum, 'linhas' => $application->resultados()->where('status', 'vigente')->count()],
                nucleoId: $application->escola->nucleo_id,
                escolaId: $application->escola_id,
            );

            return $report->refresh();
        });
    }

    private function csv(Aplicacao $application): string
    {
        $stream = fopen('php://temp', 'w+');
        fputcsv($stream, ['matricula', 'aluno', 'acertos', 'erros', 'brancos', 'duplas', 'nota_percentual']);

        $application->resultados()
            ->where('status', 'vigente')
            ->with('aluno:id,matricula,nome,nome_social')
            ->orderBy('aluno_id')
            ->each(function ($result) use ($stream): void {
                fputcsv($stream, [
                    $this->safeCell($result->aluno->matricula),
                    $this->safeCell($result->aluno->nome_social ?: $result->aluno->nome),
                    $result->acertos,
                    $result->erros,
                    $result->brancos,
                    $result->duplas,
                    $result->nota_percentual,
                ]);
            });

        rewind($stream);
        $content = stream_get_contents($stream);
        fclose($stream);

        return is_string($content) ? $content : '';
    }

    private function safeCell(?string $value): string
    {
        $value ??= '';

        return preg_match('/^[=+\-@]/', $value) === 1 ? "'".$value : $value;
    }
}
