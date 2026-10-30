<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Arquivo: apps/api/app/Console/Commands/ImportarAlunos.php
 * Função: Importa alunos de um CSV (CLASS;NAME;MATRICULA), criando as turmas
 *         que não existirem na escola. Idempotente: matrícula existente é atualizada.
 *  Uso: php artisan g360:importar-alunos {escola_id} {arquivo.csv} --serie="3ª série" --turno=tarde --ano=2026
 */
class ImportarAlunos extends Command
{
    protected $signature = 'g360:importar-alunos {escola_id} {arquivo}
        {--serie= : Série das turmas criadas} {--turno=manha : manha|tarde|noite|integral} {--ano= : Ano letivo}';

    protected $description = 'Importa alunos (e turmas) de um CSV CLASS;NAME;MATRICULA';

    public function handle(): int
    {
        $escolaId = (int) $this->argument('escola_id');
        if (!DB::table('escolas')->where('id', $escolaId)->exists()) {
            $this->error("Escola {$escolaId} não encontrada.");
            return self::FAILURE;
        }

        $linhas = file($this->argument('arquivo'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $linhas[0] = preg_replace('/^\xEF\xBB\xBF/', '', $linhas[0]);
        $sep = str_contains($linhas[0], ';') ? ';' : ',';
        $cab = array_map(fn ($c) => strtoupper(trim($c)), str_getcsv($linhas[0], $sep));
        $iT = array_search('CLASS', $cab);
        $iN = array_search('NAME', $cab);
        $iM = array_search('MATRICULA', $cab);
        if ($iT === false || $iN === false || $iM === false) {
            $this->error('Cabeçalho precisa ter CLASS, NAME e MATRICULA.');
            return self::FAILURE;
        }

        $ano = $this->option('ano') ?: date('Y');
        $turmas = [];
        $novos = $atualizados = 0;

        DB::transaction(function () use ($linhas, $sep, $iT, $iN, $iM, $escolaId, $ano, &$turmas, &$novos, &$atualizados) {
            foreach (array_slice($linhas, 1) as $l) {
                $c = str_getcsv($l, $sep);
                $nomeTurma = trim($c[$iT] ?? '');
                $nome = trim($c[$iN] ?? '');
                $mat = trim($c[$iM] ?? '');
                if ($nomeTurma === '' || $nome === '' || $mat === '') {
                    continue;
                }

                if (!isset($turmas[$nomeTurma])) {
                    $turma = DB::table('turmas')->where('escola_id', $escolaId)
                        ->where('nome', $nomeTurma)->where('ano_letivo', $ano)->first();
                    $turmas[$nomeTurma] = $turma->id ?? DB::table('turmas')->insertGetId([
                        'escola_id'  => $escolaId,
                        'nome'       => $nomeTurma,
                        'serie'      => $this->option('serie') ?: $nomeTurma,
                        'turno'      => $this->option('turno'),
                        'ano_letivo' => $ano,
                        'ativo'      => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $existe = DB::table('alunos')->where('matricula', $mat)->first();
                $dados = ['turma_id' => $turmas[$nomeTurma], 'nome' => $nome, 'ativo' => true, 'updated_at' => now()];
                if ($existe) {
                    DB::table('alunos')->where('id', $existe->id)->update($dados);
                    $atualizados++;
                } else {
                    DB::table('alunos')->insert($dados + ['matricula' => $mat, 'created_at' => now()]);
                    $novos++;
                }
            }
        });

        foreach ($turmas as $nome => $id) {
            $this->line("Turma {$nome}: id {$id}");
        }
        $this->info("Alunos: {$novos} novos, {$atualizados} atualizados.");

        return self::SUCCESS;
    }
}
