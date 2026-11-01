<?php

namespace App\Console\Commands;

use App\Support\Censo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Arquivo: apps/api/app/Console/Commands/ImportarCenso.php
 * Função: Importa a Tabela_Escola do Censo Escolar (INEP) para `censo_escolas`, só das UFs pedidas,
 *         e atualiza as escolas cadastradas a partir do Censo que não foram editadas à mão.
 *         O CSV é o mesmo do ECOAR-MQA (`;`, ISO-8859-1). Idempotente.
 *  Uso: php artisan g360:censo-importar /caminho/Tabela_Escola_2025.csv --uf=BA [--dry-run]
 */
class ImportarCenso extends Command
{
    protected $signature = 'g360:censo-importar {arquivo : Tabela_Escola_AAAA.csv do Censo Escolar}
        {--uf=BA : UFs a importar, separadas por vírgula} {--dry-run : Só conta, não grava} {--atualizar-escolas} {--usuario=} {--espaco=}';

    protected $description = 'Importa a base de escolas do Censo Escolar (INEP) para consulta pelo código INEP';

    public function handle(): int
    {
        $escolaAlvo = null;
        if (\App\Support\Espacos::ativo() && $this->option('atualizar-escolas')) {
            try {
                $escolaAlvo = (int) \App\Support\Espacos::escolas()->where('ee.id', (int) $this->option('espaco'))->value('e.id');
                \App\Support\ContextoImportacao::resolver($escolaAlvo, (int) $this->option('usuario'), (int) $this->option('espaco'), ['escola.editar']);
            } catch (\Throwable $e) { $this->error('Destino institucional não autorizado.'); return self::FAILURE; }
        }
        $arquivo = $this->argument('arquivo');
        $fh = @fopen($arquivo, 'r');
        if (!$fh) {
            $this->error("Não foi possível abrir {$arquivo}.");
            return self::FAILURE;
        }
        $ufs = array_filter(array_map(fn ($u) => strtoupper(trim($u)), explode(',', (string) $this->option('uf'))));
        $utf8 = fn (string $s) => mb_check_encoding($s, 'UTF-8') ? $s : mb_convert_encoding($s, 'UTF-8', 'ISO-8859-1');

        $cab = array_map(fn ($c) => trim($utf8($c), "\xEF\xBB\xBF \t\r\n\""), fgetcsv($fh, 0, ';') ?: []);
        $faltam = array_diff(array_values(Censo::COLUNAS), $cab);
        if ($faltam) {
            $this->error('Colunas ausentes no CSV: ' . implode(', ', $faltam));
            return self::FAILURE;
        }

        $lote = [];
        $lidas = $importadas = 0;
        $agora = now();
        $gravar = function () use (&$lote) {
            if ($lote && !$this->option('dry-run')) {
                DB::table('censo_escolas')->upsert($lote, ['codigo_inep'], array_diff(array_keys($lote[0]), ['codigo_inep', 'created_at']));
            }
            $lote = [];
        };

        while (($campos = fgetcsv($fh, 0, ';')) !== false) {
            $lidas++;
            if (count($campos) !== count($cab)) {
                continue;
            }
            $linha = array_combine($cab, array_map($utf8, $campos));
            if ($ufs && !in_array($linha['SG_UF'] ?? '', $ufs, true)) {
                continue;
            }
            if (!$r = Censo::linhaParaBase($linha)) {
                continue;
            }
            $lote[] = $r + ['created_at' => $agora, 'updated_at' => $agora];
            $importadas++;
            if (count($lote) >= 1000) {
                $gravar();
            }
        }
        $gravar();
        fclose($fh);

        // Escolas vindas do Censo e não editadas à mão acompanham a base nova
        $atualizadas = 0;
        if (!$this->option('dry-run') && (!\App\Support\Espacos::ativo() || $this->option('atualizar-escolas'))) {
            $escolas = DB::table('escolas')->join('censo_escolas', 'censo_escolas.codigo_inep', '=', 'escolas.inep')
                ->when(\App\Support\Espacos::ativo(), fn ($q) => $q->where('escolas.id', $escolaAlvo))
                ->where('escolas.origem', 'censo')->where('escolas.edicao_manual', false)
                ->get(['escolas.id', 'censo_escolas.*']);
            foreach ($escolas as $e) {
                $dados = collect(Censo::paraEscola($e))->except('inep', 'nome')->all();
                if (\App\Support\Espacos::ativo()) {
                    try {
                        DB::transaction(function () use ($escolaAlvo, $dados) {
                            $escola = \App\Models\Escola::whereKey($escolaAlvo)->lockForUpdate()->firstOrFail();
                            \App\Support\ContextoImportacao::resolver($escolaAlvo, (int) $this->option('usuario'), (int) $this->option('espaco'), ['escola.editar']);
                            abort_unless($escola->origem === 'censo' && !$escola->edicao_manual, 409);
                            $escola->fill($dados)->save();
                        });
                    } catch (\Throwable $e) {
                        $this->error('Catálogo importado; atualização institucional recusada após revalidar o destino.');
                        return self::FAILURE;
                    }
                } else {
                    $escola = \App\Models\Escola::findOrFail($e->id);
                    $escola->fill($dados)->save();
                }
                $atualizadas++;
            }
        }

        $this->info(sprintf('%s linhas lidas; %s escolas de %s %s; %s escolas cadastradas atualizadas.',
            number_format($lidas, 0, ',', '.'), number_format($importadas, 0, ',', '.'), implode(', ', $ufs) ?: 'todas as UFs',
            $this->option('dry-run') ? 'encontradas (dry-run, nada gravado)' : 'importadas', $atualizadas));

        return self::SUCCESS;
    }
}
