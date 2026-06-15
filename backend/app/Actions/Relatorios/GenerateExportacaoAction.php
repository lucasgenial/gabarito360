<?php

namespace App\Actions\Relatorios;

use App\Models\Arquivo;
use App\Models\Exportacao;
use App\Models\Prova;
use App\Models\User;
use App\Services\Audit\AuditAction;
use App\Services\Audit\AuditService;
use App\Services\Relatorios\ReportExportWriter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Gera uma exportação (csv/pdf/xlsx) do relatório de uma prova: cria o registro,
 * audita a solicitação, escreve o arquivo no disco privado e conclui o registro.
 *
 * O relatório já vem montado e autorizado pelo controller (mesmos números da
 * tela), evitando recomputo e garantindo o escopo do ator.
 */
class GenerateExportacaoAction
{
    public function __construct(
        private AuditService $audit,
        private ReportExportWriter $writer,
    ) {}

    /**
     * @param  array<string, mixed>  $report
     */
    public function execute(
        Prova $prova,
        string $formato,
        array $report,
        User $actor,
        ?string $turmaId = null,
    ): Exportacao {
        $nucleoId = $prova->ownerNucleoId();

        $exportacao = Exportacao::query()->create([
            'tipo' => $turmaId !== null ? 'relatorio_turma_prova' : 'relatorio_prova',
            'formato' => $formato,
            'status' => 'processando',
            'solicitante_id' => $actor->id,
            'prova_id' => $prova->id,
            'turma_id' => $turmaId,
            'filtros' => array_filter(['prova_id' => $prova->id, 'turma_id' => $turmaId]),
            'escopo' => ['nucleo_id' => $nucleoId, 'prova_id' => $prova->id, 'turma_id' => $turmaId],
            'solicitado_at' => now(),
        ]);

        $this->audit->record(
            AuditAction::EXPORT_REQUESTED,
            'exportacao',
            $exportacao->id,
            $actor->id,
            after: $exportacao->only(['tipo', 'formato', 'status', 'escopo']),
            nucleoId: $nucleoId,
        );

        return DB::transaction(function () use ($exportacao, $prova, $formato, $report, $actor): Exportacao {
            $rendered = $this->writer->write($formato, $report, $prova);
            $path = 'exports/'.$exportacao->id.'.'.$rendered['extensao'];

            Storage::disk(config('filesystems.private'))->put($path, $rendered['conteudo']);

            $arquivo = Arquivo::query()->create([
                'disco' => config('filesystems.private'),
                'caminho' => $path,
                'nome_original' => 'relatorio-prova-'.$prova->id.'.'.$rendered['extensao'],
                'mime' => $rendered['mime'],
                'tamanho_bytes' => strlen($rendered['conteudo']),
                'checksum' => hash('sha256', $rendered['conteudo']),
                'classificacao' => 'restrito',
                'proprietario_tipo' => 'exportacao',
                'proprietario_id' => $exportacao->id,
                'criado_por_id' => $actor->id,
                'reter_ate' => now()->addDays(30),
            ]);

            $exportacao->update([
                'arquivo_id' => $arquivo->id,
                'status' => 'concluido',
                'linhas' => count($report['resultado_por_aluno']),
                'concluido_at' => now(),
                'expira_at' => now()->addDays(30),
            ]);

            return $exportacao->refresh();
        });
    }
}
