<?php

namespace App\Services\Omr;

use App\Models\Prova;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use RuntimeException;

/**
 * Arquivo: apps/api/app/Services/Omr/GeradorCartoes.php
 * Função: Gera o PDF dos cartões-resposta nativos de uma prova (um por aluno
 *         das turmas vinculadas, mais cartões em branco), via omr/gerar_cartoes.py.
 */
class GeradorCartoes
{
    /** @return string caminho absoluto do PDF gerado (arquivo temporário) */
    public function gerar(Prova $prova, ?int $turmaId = null, int $brancos = 0): string
    {
        $turmaIds = DB::table('prova_turmas')->where('prova_id', $prova->id)->pluck('turma_id');
        if ($turmaId) {
            $turmaIds = $turmaIds->filter(fn ($id) => (int) $id === $turmaId);
        }

        $alunos = DB::table('alunos')
            ->join('turmas', 'alunos.turma_id', '=', 'turmas.id')
            ->whereIn('alunos.turma_id', $turmaIds)
            ->where('alunos.ativo', true)
            ->orderBy('turmas.nome')
            ->orderBy('alunos.nome')
            ->get(['alunos.id', 'alunos.nome', 'alunos.matricula', 'turmas.nome as turma'])
            ->map(fn ($a) => (array) $a)
            ->all();

        $escola = DB::table('escolas')->where('id', $prova->escola_id)->first();
        $rede   = $escola ? DB::table('nucleos')
            ->join('redes', 'nucleos.rede_id', '=', 'redes.id')
            ->where('nucleos.id', $escola->nucleo_id)
            ->value('redes.nome') : null;

        $saida = tempnam(sys_get_temp_dir(), 'g360_cartoes_') . '.pdf';

        $dados = [
            'saida'            => $saida,
            'rede'             => $rede,
            'escola'           => $escola->nome ?? '',
            'titulo'           => $prova->titulo,
            'disciplina'       => $prova->disciplina,
            'data'             => $prova->data_aplicacao?->format('d/m/Y'),
            'prova_id'         => $prova->id,
            'num_questoes'     => $prova->num_questoes,
            'num_alternativas' => $prova->num_alternativas,
            'opcao_lingua'     => (bool) $prova->opcao_lingua,
            'brancos'          => $brancos,
            'alunos'           => $alunos,
        ];

        $r = Process::path(config('omr.scripts'))
            ->timeout(max(120, config('omr.timeout')))
            ->input(json_encode($dados, JSON_UNESCAPED_UNICODE))
            ->run([config('omr.python'), 'gerar_cartoes.py']);

        $json = json_decode(trim($r->output()), true);
        if (!is_array($json) || empty($json['ok']) || !is_file($saida)) {
            throw new RuntimeException('Falha ao gerar cartões: ' . ($json['erro'] ?? trim($r->errorOutput())));
        }

        return $saida;
    }
}
