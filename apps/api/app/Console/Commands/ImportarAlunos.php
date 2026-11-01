<?php

namespace App\Console\Commands;

use App\Support\ContextoImportacao;
use App\Support\Espacos;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/** CSV escolar; matrícula nunca transfere aluno de outra instituição. */
class ImportarAlunos extends Command
{
    protected $signature = 'g360:importar-alunos {escola_id} {arquivo}
        {--serie=} {--turno=manha} {--ano=} {--usuario=} {--vinculo=} {--espaco=} {--aplicar}';
    protected $description = 'Importa alunos e turmas de CSV CLASS;NAME;MATRICULA no destino autorizado';

    public function handle(): int
    {
        try {
            $escola = (int) $this->argument('escola_id');
            $contexto = fn () => ContextoImportacao::resolver($escola, (int) $this->option('usuario'), (int) $this->option('espaco'), ['aluno.importar', 'aluno.gerir', 'turma.gerir'], $this->option('vinculo') !== null ? (int) $this->option('vinculo') : null);
            $meta = $contexto();
            abort_unless(DB::table('escolas')->where('id', $escola)->exists(), 422);
            $linhas = @file($this->argument('arquivo'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            abort_unless($linhas && count($linhas) > 1, 422);
            $linhas[0] = preg_replace('/^\xEF\xBB\xBF/', '', $linhas[0]);
            $sep = str_contains($linhas[0], ';') ? ';' : ',';
            $cab = array_map(fn ($c) => strtoupper(trim($c)), str_getcsv(array_shift($linhas), $sep));
            abort_unless(count(array_intersect(['CLASS', 'NAME', 'MATRICULA'], $cab)) === 3, 422);
            $ano = (int) ($this->option('ano') ?: date('Y'));
            Validator::make(['ano' => $ano, 'turno' => $this->option('turno')], ['ano' => 'integer|min:2000|max:2100', 'turno' => 'in:manha,tarde,noite,integral'])->validate();
            $dados = []; $matriculas = [];
            foreach ($linhas as $linha) {
                $valores = str_getcsv($linha, $sep);
                abort_unless(count($valores) === count($cab), 422);
                $r = array_combine($cab, array_map('trim', $valores));
                Validator::make($r, ['CLASS' => 'required|string|max:50', 'NAME' => 'required|string|max:200', 'MATRICULA' => 'required|string|max:20'])->validate();
                abort_if(isset($matriculas[$r['MATRICULA']]), 422);
                $matriculas[$r['MATRICULA']] = true;
                $dados[] = $r;
            }
            $validar = function () use ($dados, $escola, $meta, $ano) {
                foreach ($dados as $r) {
                    // No corte, coincidência em outro espaço não identifica nem transfere a pessoa.
                    $existentes = DB::table('alunos')->where('matricula', $r['MATRICULA']);
                    if (Espacos::ativo()) $existentes->where('espaco_id', $meta['espaco_id']);
                    foreach ($existentes->get() as $aluno) {
                        $turma = DB::table('turmas')->where('id', $aluno->turma_id)->first();
                        abort_unless($turma && (int) $turma->escola_id === $escola, 409);
                        if (Espacos::ativo()) abort_unless((int) $aluno->espaco_id === $meta['espaco_id'] && (int) $turma->espaco_id === $meta['espaco_id'], 409);
                    }
                    $turmas = DB::table('turmas')->where('escola_id', $escola)->where('nome', $r['CLASS'])->where('ano_letivo', $ano)->get();
                    abort_if($turmas->count() > 1, 409);
                    if (Espacos::ativo() && $turmas->isNotEmpty()) {
                        abort_unless((int) $turmas->first()->espaco_id === $meta['espaco_id'], 409);
                        $ator = \App\Models\Usuario::findOrFail((int) $this->option('usuario'));
                        abort_unless(in_array((int) $turmas->first()->id, \App\Support\Escopo::turmaIds($ator) ?? [], true), 404);
                    }
                }
            };
            $validar();
            if (Espacos::ativo() && !$this->option('aplicar')) {
                $this->info('Simulação: '.count($dados).' linhas válidas; nada gravado.');
                return self::SUCCESS;
            }
            DB::transaction(function () use ($escola, $dados, $ano, $meta, $contexto, $validar) {
                DB::table('escolas')->where('id', $escola)->lockForUpdate()->firstOrFail();
                $contexto(); $validar();
                foreach ($dados as $r) {
                    $turma = DB::table('turmas')->where('escola_id', $escola)->where('nome', $r['CLASS'])->where('ano_letivo', $ano)->first();
                    $turmaId = $turma->id ?? DB::table('turmas')->insertGetId(['escola_id' => $escola, 'nome' => $r['CLASS'],
                        'serie' => $this->option('serie') ?: $r['CLASS'], 'turno' => $this->option('turno'), 'ano_letivo' => $ano,
                        'ativo' => true, 'created_at' => now(), 'updated_at' => now()] + $meta);
                    $q = DB::table('alunos')->where('matricula', $r['MATRICULA']);
                    if (Espacos::ativo()) $q->where('espaco_id', $meta['espaco_id']);
                    $existente = $q->first();
                    $atualizacao = ['turma_id' => $turmaId, 'nome' => $r['NAME'], 'ativo' => true, 'updated_at' => now()];
                    if ($existente) $q->update($atualizacao);
                    else DB::table('alunos')->insert($atualizacao + ['matricula' => $r['MATRICULA'], 'created_at' => now()] + $meta);
                }
            });
            $this->info(count($dados).' linhas importadas no destino.');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Importação recusada. Confira contexto, permissões, formato e conflitos; nenhuma linha foi aplicada.');
            return self::FAILURE;
        }
    }
}
