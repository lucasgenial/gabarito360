<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/** Um registro legado recebe uma referência provisória; nunca deduplica por CPF/nome. */
final class PrepararIdentidadesProvisorias
{
    private const FONTES = [
        'usuarios' => ['pessoa_usuarios', 'usuario_id'],
        'alunos' => ['pessoa_alunos', 'aluno_id'],
    ];

    public function executar(bool $aplicar = false, int $lote = 100): array
    {
        if ($lote < 1 || $lote > 1000) throw new RuntimeException('Lote de identidade inválido.');
        if ($aplicar && !config('identidade.provisorios_ativos')) throw new RuntimeException('Preparação de identidades não habilitada.');
        $r = ['modo' => $aplicar ? 'aplicacao_explicita' : 'simulacao_sem_escrita',
            'criados' => 0, 'reutilizados' => 0, 'origens_removidas' => 0, 'pendentes' => []];
        $execucao = (string) Str::uuid();
        $restantes = $lote;
        foreach (self::FONTES as $fonte => [$mapa, $coluna]) {
            $pendentes = fn () => DB::table($fonte.' as o')->whereNotExists(function ($q) use ($mapa, $coluna) {
                $q->selectRaw('1')->from($mapa.' as m')->whereColumn('m.'.$coluna, 'o.id');
            });
            if ($aplicar && $restantes > 0) {
                $ids = $pendentes()->orderBy('o.id')->limit($restantes)->pluck('o.id');
                foreach ($ids as $id) {
                    $estado = DB::transaction(function () use ($fonte, $mapa, $coluna, $id, $execucao) {
                        // A linha de origem serializa executores antes da consulta ao mapa.
                        if (!DB::table($fonte)->where('id', $id)->lockForUpdate()->first(['id'])) return 'origens_removidas';
                        if (DB::table($mapa)->where($coluna, $id)->lockForUpdate()->first(['id'])) return 'reutilizados';
                        $pessoa = DB::table('pessoas')->insertGetId(['uuid' => (string) Str::uuid(),
                            'situacao' => 'provisoria', 'created_at' => now(), 'updated_at' => now()]);
                        DB::table($mapa)->insert(['pessoa_id' => $pessoa, $coluna => $id,
                            'execucao_referencia' => $execucao, 'registrado_em' => now()]);
                        DB::table('pessoa_identidade_eventos')->insert(['referencia' => (string) Str::uuid(),
                            'pessoa_id' => $pessoa, 'acao' => 'provisoria_criada', 'ocorrido_em' => now()]);
                        return 'criados';
                    }, 3);
                    $r[$estado]++;
                    $restantes--;
                }
            }
            $r['pendentes'][$fonte] = $pendentes()->count();
        }
        return $r;
    }
}
