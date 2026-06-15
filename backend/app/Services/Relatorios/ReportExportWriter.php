<?php

namespace App\Services\Relatorios;

use App\Models\Prova;
use App\Support\Export\XlsxWriter;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Serializa o relatório de prova (array vindo de {@see ProvaReportService}) nos
 * formatos csv, xlsx e pdf. Mantém um único conjunto de colunas para que os três
 * formatos representem os mesmos dados.
 */
class ReportExportWriter
{
    private const HEADER = ['Matrícula', 'Aluno', 'Turma', 'Nota (%)', 'Acertos', 'Situação'];

    /**
     * @param  array<string, mixed>  $report
     * @return array{conteudo: string, mime: string, extensao: string}
     */
    public function write(string $formato, array $report, Prova $prova): array
    {
        return match ($formato) {
            'csv' => [
                'conteudo' => $this->csv($report),
                'mime' => 'text/csv',
                'extensao' => 'csv',
            ],
            'xlsx' => [
                'conteudo' => (new XlsxWriter)->build(self::HEADER, $this->rows($report), 'Relatório'),
                'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'extensao' => 'xlsx',
            ],
            'pdf' => [
                'conteudo' => Pdf::loadHTML($this->html($report, $prova))->output(),
                'mime' => 'application/pdf',
                'extensao' => 'pdf',
            ],
        };
    }

    /**
     * Linhas de "resultado por aluno" como matriz tipada (strings + números).
     *
     * @param  array<string, mixed>  $report
     * @return list<array<int, scalar>>
     */
    private function rows(array $report): array
    {
        $rows = [];

        foreach ($report['resultado_por_aluno'] as $aluno) {
            $rows[] = [
                (string) ($aluno['matricula'] ?? ''),
                (string) ($aluno['aluno_nome'] ?? ''),
                (string) ($aluno['turma_nome'] ?? ''),
                (float) $aluno['nota_percentual'],
                (int) $aluno['acertos'],
                $aluno['status'] === 'aprovado' ? 'Aprovado' : 'Recuperação',
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function csv(array $report): string
    {
        $stream = fopen('php://temp', 'w+');
        fputcsv($stream, self::HEADER);

        foreach ($this->rows($report) as $row) {
            fputcsv($stream, array_map(fn ($cell) => $this->safeCell((string) $cell), $row));
        }

        rewind($stream);
        $content = stream_get_contents($stream);
        fclose($stream);

        return is_string($content) ? $content : '';
    }

    /**
     * Neutraliza fórmulas em CSV (CSV injection) prefixando célula com aspa.
     */
    private function safeCell(string $value): string
    {
        return preg_match('/^[=+\-@]/', $value) === 1 ? "'".$value : $value;
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function html(array $report, Prova $prova): string
    {
        $kpis = $report['kpis'];
        $titulo = htmlspecialchars((string) $prova->titulo, ENT_QUOTES, 'UTF-8');

        $temaRows = '';
        foreach ($report['acertos_por_tema'] as $tema) {
            $temaRows .= '<tr><td>'.htmlspecialchars((string) $tema['tema_nome'], ENT_QUOTES, 'UTF-8').'</td>'
                .'<td>'.$tema['acertos'].'/'.$tema['total'].'</td>'
                .'<td>'.$tema['percentual'].'%</td></tr>';
        }

        $alunoRows = '';
        foreach ($this->rows($report) as $row) {
            $alunoRows .= '<tr>'
                .'<td>'.htmlspecialchars((string) $row[0], ENT_QUOTES, 'UTF-8').'</td>'
                .'<td>'.htmlspecialchars((string) $row[1], ENT_QUOTES, 'UTF-8').'</td>'
                .'<td>'.htmlspecialchars((string) $row[2], ENT_QUOTES, 'UTF-8').'</td>'
                .'<td>'.$row[3].'</td>'
                .'<td>'.$row[4].'</td>'
                .'<td>'.htmlspecialchars((string) $row[5], ENT_QUOTES, 'UTF-8').'</td>'
                .'</tr>';
        }

        return '<!DOCTYPE html><html><head><meta charset="utf-8"><style>'
            .'body{font-family:DejaVu Sans,sans-serif;font-size:11px;color:#1a1a1a}'
            .'h1{font-size:16px}h2{font-size:13px;margin-top:16px}'
            .'table{width:100%;border-collapse:collapse;margin-top:6px}'
            .'th,td{border:1px solid #ccc;padding:4px 6px;text-align:left}'
            .'th{background:#f0f0f0}'
            .'</style></head><body>'
            .'<h1>Relatório da prova — '.$titulo.'</h1>'
            .'<p>Cartões corrigidos: <strong>'.$kpis['cartoes_corrigidos'].'/'.$kpis['total_alunos'].'</strong> · '
            .'Média: <strong>'.($kpis['media_nota'] ?? '—').'</strong> · '
            .'Aprovação: <strong>'.$kpis['aprovacao_percentual'].'%</strong> · '
            .'Pendências de leitura: <strong>'.$kpis['pendencias_leitura'].'</strong></p>'
            .'<h2>Acertos por tema</h2>'
            .'<table><thead><tr><th>Tema/Habilidade</th><th>Acertos</th><th>%</th></tr></thead>'
            .'<tbody>'.($temaRows !== '' ? $temaRows : '<tr><td colspan="3">Sem temas vinculados.</td></tr>').'</tbody></table>'
            .'<h2>Resultado por aluno</h2>'
            .'<table><thead><tr><th>Matrícula</th><th>Aluno</th><th>Turma</th><th>Nota (%)</th><th>Acertos</th><th>Situação</th></tr></thead>'
            .'<tbody>'.($alunoRows !== '' ? $alunoRows : '<tr><td colspan="6">Sem resultados.</td></tr>').'</tbody></table>'
            .'</body></html>';
    }
}
