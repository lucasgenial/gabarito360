<?php

namespace App\Services;

use App\Support\EstruturaEspacos;
use App\Support\PlanoIntegridadeEspacos;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Somente leitura; saída contém contagens técnicas, nunca dados pessoais ou hashes. */
class AuditarIntegridadeEspacos
{
    public function executar(): array
    {
        foreach (EstruturaEspacos::TABELAS as $t) {
            if (!Schema::hasColumns($t, ['espaco_id', 'criado_por', 'origem_autoria'])) {
                throw new \RuntimeException('Expansão T2 incompleta.');
            }
        }
        return DB::transaction(function () {
            $problemas = []; $contagens = []; $cobertura = [];
            foreach (EstruturaEspacos::TABELAS as $t) {
                $contagens[$t] = DB::table($t)->count();
                $problemas[$t.'_espaco_nulo'] = DB::table($t)->whereNull('espaco_id')->count();
                $problemas[$t.'_autoria_nula'] = DB::table($t)->whereNull('origem_autoria')->count();
                $cobertura[$t] = DB::table($t)->select('espaco_id', 'origem_autoria')->selectRaw('COUNT(*) as quantidade')
                    ->groupBy('espaco_id', 'origem_autoria')->orderBy('espaco_id')->orderBy('origem_autoria')->get()->all();
            }
            foreach (PlanoIntegridadeEspacos::checks() as $nome => [$t, $expressao]) {
                $problemas[$nome] = DB::table($t)->whereRaw('NOT ('.$expressao.')')->count();
            }
            foreach (PlanoIntegridadeEspacos::relacoes() as $nome => [$t, $cols, $pai, $refs]) {
                $q = DB::table($t.' as f');
                // MATCH SIMPLE: referências opcionais não exigem pai até serem preenchidas.
                foreach ($cols as $c) $q->whereNotNull('f.'.$c);
                $problemas[$nome] = $q->whereNotExists(function ($q) use ($pai, $cols, $refs) {
                    $q->selectRaw('1')->from($pai.' as p');
                    foreach ($cols as $i => $c) $q->whereColumn('f.'.$c, 'p.'.$refs[$i]);
                })->count();
            }
            foreach (PlanoIntegridadeEspacos::indices() as $nome => [$t, $cols, $unico]) {
                if (!$unico) continue;
                $q = DB::table($t)->select($cols)->groupBy($cols)->havingRaw('COUNT(*) > 1');
                foreach ($cols as $c) $q->whereNotNull($c);
                $problemas[$nome.'_duplicados'] = DB::query()->fromSub($q, 'duplicados')->count();
            }
            $problemas['espacos_cliente_inexistente'] = DB::table('espacos as e')->whereNotExists(fn ($q) => $q->selectRaw('1')->from('clientes as c')->whereColumn('c.id', 'e.cliente_id'))->count();
            foreach (['rede' => 'redes', 'nucleo' => 'nucleos', 'escola' => 'escolas', 'pessoal' => 'usuarios'] as $tipo => $t) {
                $campo = $tipo === 'pessoal' ? 'usuario_proprietario_id' : $tipo.'_id';
                $problemas['espacos_'.$tipo.'_orfao'] = DB::table('espacos as e')->where('e.tipo', $tipo)
                    ->whereNotExists(fn ($q) => $q->selectRaw('1')->from($t.' as p')->whereColumn('p.id', 'e.'.$campo))->count();
            }
            foreach (['escola' => ['escolas', 'nucleo_id'], 'nucleo' => ['nucleos', 'rede_id']] as $tipo => [$t, $campo]) {
                $problemas['hierarquia_'.$tipo] = DB::table('espacos as e')->where('e.tipo', $tipo)
                    ->whereNotExists(fn ($q) => $q->selectRaw('1')->from($t.' as i')->join('espacos as p', 'p.'.$campo, '=', 'i.'.$campo)
                        ->whereColumn('i.id', 'e.'.$tipo.'_id')->whereColumn('p.cliente_id', 'e.cliente_id')
                        ->where('p.tipo', str_replace('_id', '', $campo)))->count();
            }
            $problemas['cartoes_aluno_fora_da_prova'] = DB::table('cartoes as c')->whereNotNull('c.aluno_id')
                ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('alunos as a')
                    ->join('prova_turmas as pt', fn ($j) => $j->whereRaw('pt.turma_id = COALESCE(c.turma_aplicacao_id, a.turma_id)'))->whereColumn('a.id', 'c.aluno_id')
                    ->whereColumn('pt.prova_id', 'c.prova_id')->whereColumn('pt.espaco_id', 'c.espaco_id')->whereColumn('a.espaco_id', 'c.espaco_id'))->count();
            $problemas['notas_turma_fora_da_prova'] = DB::table('notas as n')->whereNotExists(fn ($q) => $q->selectRaw('1')->from('prova_turmas as pt')
                ->whereColumn('pt.prova_id', 'n.prova_id')->whereColumn('pt.turma_id', 'n.turma_id')->whereColumn('pt.espaco_id', 'n.espaco_id'))->count();
            $problemas['notas_divergentes_cartao'] = DB::table('notas as n')->join('cartoes as c', 'c.id', '=', 'n.cartao_id')
                ->where(fn ($q) => $q->whereColumn('n.prova_id', '<>', 'c.prova_id')->orWhereColumn('n.aluno_id', '<>', 'c.aluno_id')->orWhereNull('c.aluno_id'))->count();
            $problemas['alunos_cpf_sem_indice'] = DB::table('alunos')->whereNotNull('cpf')->whereNull('cpf_hash')->count();
            $problemas['alunos_indice_sem_cpf'] = DB::table('alunos')->whereNull('cpf')->whereNotNull('cpf_hash')->count();
            foreach (['turmas', 'provas', 'alunos'] as $t) {
                $problemas[$t.'_tipo_espaco'] = DB::table($t.' as t')->whereNotNull('t.espaco_id')->whereNotExists(fn ($q) =>
                    $q->selectRaw('1')->from('espacos as e')->whereColumn('e.id','t.espaco_id')->whereColumn('e.tipo','t.espaco_tipo'))->count();
            }
            $problemas['alunos_pessoais_excesso'] = DB::table('alunos')->where('espaco_tipo','pessoal')->where(function ($q) {
                foreach (['cpf','cpf_hash','data_nascimento','genero','nome_responsavel','foto_path'] as $c) $q->orWhereNotNull($c);
            })->count();
            $problemas['notas_turma_historica'] = DB::table('notas as n')->join('cartoes as c','c.id','=','n.cartao_id')
                ->whereNotNull('c.turma_aplicacao_id')->whereColumn('c.turma_aplicacao_id','<>','n.turma_id')->count();
            return ['plano' => PlanoIntegridadeEspacos::VERSAO, 'somente_leitura' => true,
                'contagens' => $contagens, 'cobertura' => $cobertura, 'inconsistencias' => $problemas,
                'ocorrencias' => array_sum($problemas), 'apto_dados' => array_sum($problemas) === 0];
        });
    }
}
