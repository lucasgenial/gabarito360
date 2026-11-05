<?php

namespace App\Services\Omr;

/** Projeção somente de leitura: nunca altera os snapshots preservados. */
class QuadroLeituras
{
    public function montar(array $original, array $eventos): array
    {
        $raw = $original['leituras_omr'] ?? $original['leituras_propostas'] ?? null;
        // Registro anterior pode já incluir intervenção; não inventar a leitura inicial.
        if ($raw === null && ($eventos[0]['acao'] ?? null) === 'vigente' && empty($original['revisoes'])) {
            $raw = $original['respostas'] ?? [];
        }
        $registradas = $original['respostas'] ?? [];
        $referenciasLeitura = array_column($original['referencia_gabarito']['questoes'] ?? [], null, 'numero_questao');
        $referencias = $referenciasLeitura;
        $ids = []; $revisoes = []; $numeros = [];
        foreach (array_merge([$original], array_column($eventos, 'snapshot')) as $snap) {
            foreach ($snap['respostas'] ?? [] as $r) {
                $numeros[(int) $r['numero_questao']] = true;
                if (isset($r['id'])) $ids[$r['id']] = (int) $r['numero_questao'];
            }
            if (!empty($snap['respostas'])) {
                $registradas = $snap['respostas'];
                if (isset($snap['referencia_gabarito'])) $referencias = array_column($snap['referencia_gabarito']['questoes'] ?? [], null, 'numero_questao');
            }
        }
        foreach ($original['revisoes'] ?? [] as $r) $revisoes[$r['id']] = [$r, null];
        foreach ($eventos as $e) {
            foreach ($e['snapshot']['revisoes'] ?? [] as $r) {
                // A primeira aparição preserva autor/data/motivo daquela intervenção.
                if (!isset($revisoes[$r['id']])) $revisoes[$r['id']] = [$r, $e];
            }
        }
        $automaticas = []; $finais = []; $obs = []; $rawPorQuestao = []; $finalPorQuestao = [];
        foreach ($raw ?? [] as $r) { $n = (int) $r['numero_questao']; $automaticas[$n] = $this->resposta($r); $rawPorQuestao[$n] = $r; $numeros[$n] = true; }
        foreach ($registradas as $r) { $n = (int) $r['numero_questao']; $finais[$n] = $this->resposta($r); $finalPorQuestao[$n] = $r; }
        foreach ($revisoes as [$r, $e]) {
            $n = $ids[$r['cartao_resposta_id']] ?? null;
            if (!$n) continue;
            $ator = $e['snapshot']['ator_registrado'] ?? null;
            $nome = $ator && (int) ($ator['id'] ?? 0) === (int) $r['usuario_id'] ? $ator['nome'] : 'Usuário #'.$r['usuario_id'].' (nome histórico não preservado)';
            $data = $r['created_at'] ?? $e['ocorrido_em'] ?? null;
            $quando = $data ? \Carbon\CarbonImmutable::parse($data, 'UTC')->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i:s P') : null;
            $obs[$n][] = 'Revisão humana por '.$nome.($quando ? ' em '.$quando : '').(!empty($e['motivo']) ? '. '.$e['motivo'] : '');
        }
        ksort($numeros);
        return array_map(fn ($n) => [
            'numero_questao' => $n,
            'leitura' => $automaticas[$n] ?? ($raw === null ? 'Leitura automática original não preservada' : 'Sem leitura automática'),
            'resposta' => $finais[$n] ?? 'Ainda não aplicada',
            'observacao' => (isset($obs[$n]) ? implode(' | ', $obs[$n]) : 'Sem registro preservado de intervenção')
                .(isset($referenciasLeitura[$n], $referencias[$n]) && $referenciasLeitura[$n] !== $referencias[$n]
                    ? '. O gabarito mudou entre os registros: leitura '.($referenciasLeitura[$n]['anulada'] ? 'anulada' : $referenciasLeitura[$n]['esperada']).'; resposta registrada '.($referencias[$n]['anulada'] ? 'anulada' : $referencias[$n]['esperada']).'.' : ''),
            'situacao' => $this->situacao($finalPorQuestao[$n] ?? null, $referencias[$n] ?? null),
            'leitura_situacao' => $this->situacao($rawPorQuestao[$n] ?? null, $referenciasLeitura[$n] ?? null),
            'referencia_preservada' => isset($referencias[$n]),
            'gabarito' => $referencias[$n]['esperada'] ?? null,
            'modificada' => isset($rawPorQuestao[$n], $finalPorQuestao[$n]) &&
                (($rawPorQuestao[$n]['alternativa'] ?? null) !== ($finalPorQuestao[$n]['alternativa'] ?? null)
                || (bool) ($rawPorQuestao[$n]['ambigua'] ?? false) !== (bool) ($finalPorQuestao[$n]['ambigua'] ?? false)),
            'intervencao_humana' => isset($obs[$n]),
            'resposta_registrada' => isset($finais[$n]),
        ], array_keys($numeros));
    }

    private function situacao(?array $r, ?array $referencia): string
    {
        if (!$r || !$referencia) return 'nao_preservada';
        if ($referencia['anulada']) return 'anulada';
        if (($r['alternativa'] ?? null) === null || !empty($r['ambigua'])) return 'branco';
        return $r['alternativa'] === $referencia['esperada'] ? 'correta' : 'incorreta';
    }

    private function resposta(array $r): string
    {
        $a = $r['alternativa'] ?? null;
        return ($a ?: 'Em branco').(!empty($r['ambigua']) ? ' (ambígua)' : '');
    }
}
