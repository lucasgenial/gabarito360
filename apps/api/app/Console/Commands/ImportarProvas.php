<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Arquivo: apps/api/app/Console/Commands/ImportarProvas.php
 * Função: Cria provas com gabarito publicado a partir de um JSON (ex.:
 *         database/data/simulado-enem-2026.json), vinculadas às turmas indicadas.
 *  Uso: php artisan g360:importar-provas database/data/simulado-enem-2026.json
 *          --escola=1 --usuario=1 --turmas=3A,3B --ano=2026
 */
class ImportarProvas extends Command
{
    protected $signature = 'g360:importar-provas {arquivo} {--escola=} {--usuario=} {--turmas=} {--ano=}';

    protected $description = 'Cria provas e gabaritos publicados a partir de um JSON';

    public function handle(): int
    {
        $spec = json_decode(file_get_contents($this->argument('arquivo')), true);
        $escolaId = (int) $this->option('escola');
        $usuarioId = (int) $this->option('usuario');
        if (!$spec || !$escolaId || !$usuarioId) {
            $this->error('Informe o JSON, --escola e --usuario.');
            return self::FAILURE;
        }

        $nomes = array_filter(array_map('trim', explode(',', (string) $this->option('turmas'))));
        $turmaIds = DB::table('turmas')->where('escola_id', $escolaId)
            ->when($this->option('ano'), fn ($q, $a) => $q->where('ano_letivo', $a))
            ->whereIn('nome', $nomes)->pluck('id')->all();
        if (count($turmaIds) !== count($nomes)) {
            $this->error('Turmas não encontradas na escola: ' . implode(',', $nomes));
            return self::FAILURE;
        }

        DB::transaction(function () use ($spec, $escolaId, $usuarioId, $turmaIds) {
            foreach ($spec['provas'] as $p) {
                $gab = $p['gabarito'];
                $n = strlen($gab);
                $provaId = DB::table('provas')->insertGetId([
                    'escola_id'        => $escolaId,
                    'criado_por'       => $usuarioId,
                    'titulo'           => $spec['titulo'],
                    'disciplina'       => $p['disciplina'],
                    'serie'            => $spec['serie'],
                    'num_questoes'     => $n,
                    'num_alternativas' => $spec['num_alternativas'] ?? 5,
                    'nota_maxima'      => $spec['nota_maxima'] ?? 10,
                    'tipo_pontuacao'   => 'igual',
                    'anular_se_todas'  => $spec['anular_se_todas'] ?? true,
                    'opcao_lingua'     => !empty($p['gabarito_espanhol']),
                    'gerar_cartao_pdf' => true,
                    'status'           => 'publicada',
                    'data_aplicacao'   => $p['data_aplicacao'] ?? null,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);
                DB::table('prova_turmas')->insert(array_map(fn ($t) => ['prova_id' => $provaId, 'turma_id' => $t], $turmaIds));

                $gabId = DB::table('gabaritos')->insertGetId([
                    'prova_id' => $provaId, 'publicado_por' => $usuarioId, 'publicado_em' => now(),
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                $esp = $p['gabarito_espanhol'] ?? '';
                $rows = [];
                for ($i = 0; $i < $n; $i++) {
                    $rows[] = [
                        'gabarito_id'          => $gabId,
                        'numero_questao'       => $i + 1,
                        'alternativa'          => $gab[$i],
                        'alternativa_espanhol' => isset($esp[$i]) && $esp[$i] !== '-' ? $esp[$i] : null,
                        'peso'                 => 1,
                        'anulada'              => false,
                    ];
                }
                DB::table('gabarito_questoes')->insert($rows);
                $this->line("Prova {$provaId}: {$p['disciplina']} ({$n} questões)");
            }
        });

        $this->info('Provas criadas e publicadas.');

        return self::SUCCESS;
    }
}
