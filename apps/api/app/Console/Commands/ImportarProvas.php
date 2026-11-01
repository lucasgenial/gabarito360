<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Support\ContextoImportacao;
use App\Support\Espacos;
use App\Support\ConsultasPedagogicas;
use App\Models\Usuario;
use Illuminate\Support\Facades\Validator;
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
    protected $signature = 'g360:importar-provas {arquivo} {--escola=} {--usuario=} {--vinculo=} {--turmas=} {--ano=} {--espaco=} {--aplicar}';

    protected $description = 'Cria provas e gabaritos publicados a partir de um JSON';

    public function handle(): int
    {
        try {
            $spec = json_decode(file_get_contents($this->argument('arquivo')), true);
            $escolaId = (int) $this->option('escola');
            $usuarioId = (int) $this->option('usuario');
            if (!$spec || !$escolaId || !$usuarioId) {
                $this->error('Informe o JSON, --escola e --usuario.');
                return self::FAILURE;
            }

            $contexto = fn () => ContextoImportacao::resolver($escolaId, $usuarioId, (int) $this->option('espaco'), ['prova.criar', 'gabarito.editar'], $this->option('vinculo') !== null ? (int) $this->option('vinculo') : null);
            $meta = $contexto();
            Validator::make($spec, ['titulo' => 'required|string|max:255', 'serie' => 'required|string|max:100', 'provas' => 'required|array|min:1', 'provas.*.disciplina' => 'required|string|max:100', 'provas.*.gabarito' => 'required|string|regex:/^[ABCDE]{1,100}$/', 'provas.*.gabarito_espanhol' => 'nullable|string|regex:/^[ABCDE-]{1,100}$/', 'num_alternativas' => 'sometimes|integer|min:2|max:5', 'nota_maxima' => 'sometimes|numeric|min:0.1|max:100', 'provas.*.data_aplicacao' => 'nullable|date'])->validate();
            foreach ($spec['provas'] as $p) {
                abort_if(!empty($p['gabarito_espanhol']) && strlen($p['gabarito_espanhol']) !== strlen($p['gabarito']), 422);
                $letras = substr('ABCDE', 0, $spec['num_alternativas'] ?? 5);
                abort_unless(strspn($p['gabarito'], $letras) === strlen($p['gabarito']), 422);
            }
            $nomes = array_filter(array_map('trim', explode(',', (string) $this->option('turmas'))));
            $ator = Usuario::findOrFail($usuarioId);
            if ($this->option('vinculo') !== null) $ator = $ator->noVinculo((int) $this->option('vinculo'));
            $turmas = $ator->vinculoContextoId() !== null ? \App\Support\Escopo::turmas($ator)
                : (Espacos::ativo() ? (new ConsultasPedagogicas($ator))->tabela('turmas') : DB::table('turmas'));
            $turmaIds = $turmas->where('escola_id', $escolaId)
                ->when($this->option('ano'), fn ($q, $a) => $q->where('ano_letivo', $a))
                ->whereIn('nome', $nomes)->pluck('id')->all();
            if (!$nomes || count($turmaIds) !== count($nomes)) {
                $this->error('Turmas não encontradas na escola: ' . implode(',', $nomes));
                return self::FAILURE;
            }

            if (Espacos::ativo() && !$this->option('aplicar')) {
                $this->info('Simulação: '.count($spec['provas']).' provas válidas; nada gravado.');
                return self::SUCCESS;
            }
            DB::transaction(function () use ($spec, $escolaId, $usuarioId, $turmaIds, $contexto, $meta) {
                DB::table('escolas')->where('id', $escolaId)->lockForUpdate()->firstOrFail();
                $contexto();
                if (Espacos::ativo()) {
                    $turmasAtuais = (new ConsultasPedagogicas(Usuario::findOrFail($usuarioId)))->tabela('turmas')->where('escola_id', $escolaId)->whereIn('id', $turmaIds)->lockForUpdate()->get();
                    abort_unless($turmasAtuais->count() === count($turmaIds), 409);
                }
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
                    ] + $meta);
                    DB::table('prova_turmas')->insert(array_map(fn ($t) => ['prova_id' => $provaId, 'turma_id' => $t] + $meta, $turmaIds));

                    $gabId = DB::table('gabaritos')->insertGetId([
                        'prova_id' => $provaId, 'publicado_por' => $usuarioId, 'publicado_em' => now(),
                        'created_at' => now(), 'updated_at' => now(),
                    ] + $meta);
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
                        ] + $meta;
                    }
                    DB::table('gabarito_questoes')->insert($rows);
                    $this->line("Prova {$provaId}: {$p['disciplina']} ({$n} questões)");
                }
            });

            $this->info('Provas criadas e publicadas.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Importação recusada. Confira contexto, permissões e formato; nenhuma prova foi aplicada.');
            return self::FAILURE;
        }
    }
}
