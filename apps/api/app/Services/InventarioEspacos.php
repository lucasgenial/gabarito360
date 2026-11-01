<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/** Diagnóstico T2-A: somente SELECTs; não lê nomes, documentos, notas ou payloads. */
class InventarioEspacos
{
    public function gerar(): array
    {
        return DB::transaction(function () {
            $contagens = [];
            $porEscola = [];
            $problemas = [];
            foreach ($this->caminhos() as $tabela => $caminho) {
                $q = DB::table($tabela.' as origem');
                foreach ($caminho as [$t, $alias, $esquerda]) {
                    $q->leftJoin($t.' as '.$alias, $esquerda, '=', $alias.'.id');
                }
                $contagens[$tabela] = DB::table($tabela)->count();
                $problemas[$tabela.'_sem_escola_resolvida'] = (clone $q)->whereNull('e.id')->count();
                $porEscola[$tabela] = (clone $q)->whereNotNull('e.id')
                    ->select('e.id as escola_id')->selectRaw('COUNT(*) as quantidade')
                    ->groupBy('e.id')->orderBy('e.id')->get()->map(fn ($r) => [
                        'escola_id' => (int) $r->escola_id, 'quantidade' => (int) $r->quantidade,
                    ])->all();
            }

            $problemas['escolas_sem_nucleo'] = $this->orfaos('escolas', 'nucleo_id', 'nucleos');
            $problemas['nucleos_sem_rede'] = $this->orfaos('nucleos', 'rede_id', 'redes');
            $problemas['redes_com_secretaria_inexistente'] = $this->orfaos('redes', 'secretaria_id', 'secretarias', true);
            $problemas['prova_turmas_entre_escolas'] = DB::table('prova_turmas as pt')
                ->join('provas as p', 'p.id', '=', 'pt.prova_id')->join('turmas as t', 't.id', '=', 'pt.turma_id')
                ->whereColumn('p.escola_id', '<>', 't.escola_id')->count();
            $problemas['prova_turmas_sem_turma'] = $this->orfaos('prova_turmas', 'turma_id', 'turmas');
            $problemas['cartoes_com_aluno_inexistente'] = $this->orfaos('cartoes', 'aluno_id', 'alunos', true);
            $problemas['cartoes_com_aluno_de_outra_escola'] = DB::table('cartoes as c')
                ->join('provas as p', 'p.id', '=', 'c.prova_id')->join('alunos as a', 'a.id', '=', 'c.aluno_id')
                ->join('turmas as t', 't.id', '=', 'a.turma_id')->whereColumn('p.escola_id', '<>', 't.escola_id')->count();
            foreach (['aluno_id' => 'alunos', 'turma_id' => 'turmas', 'cartao_id' => 'cartoes'] as $campo => $pai) {
                $problemas['notas_'.$campo.'_inexistente'] = $this->orfaos('notas', $campo, $pai);
            }
            $problemas['notas_com_turma_de_outra_escola'] = DB::table('notas as n')
                ->join('provas as p', 'p.id', '=', 'n.prova_id')->join('turmas as t', 't.id', '=', 'n.turma_id')
                ->whereColumn('p.escola_id', '<>', 't.escola_id')->count();
            $problemas['notas_com_aluno_de_outra_escola'] = DB::table('notas as n')
                ->join('provas as p', 'p.id', '=', 'n.prova_id')->join('alunos as a', 'a.id', '=', 'n.aluno_id')
                ->join('turmas as t', 't.id', '=', 'a.turma_id')->whereColumn('p.escola_id', '<>', 't.escola_id')->count();
            $problemas['notas_divergentes_do_cartao'] = DB::table('notas as n')
                ->join('cartoes as c', 'c.id', '=', 'n.cartao_id')->where(function (Builder $q) {
                    $q->whereColumn('n.prova_id', '<>', 'c.prova_id')->orWhereColumn('n.aluno_id', '<>', 'c.aluno_id')->orWhereNull('c.aluno_id');
                })->count();
            $problemas['envios_com_cartao_inexistente'] = $this->orfaos('cartao_envios', 'cartao_id', 'cartoes', true);
            // Recibo conserva a prova de entrada; QR pode corrigir a prova dentro da mesma escola.
            $problemas['envios_com_cartao_de_outra_escola'] = DB::table('cartao_envios as e')
                ->join('cartoes as c', 'c.id', '=', 'e.cartao_id')->join('provas as entrada', 'entrada.id', '=', 'e.prova_id')
                ->join('provas as destino', 'destino.id', '=', 'c.prova_id')->whereColumn('entrada.escola_id', '<>', 'destino.escola_id')->count();
            $problemas['ambiguidade_com_resposta_inexistente'] = $this->orfaos('ambiguidade_logs', 'cartao_resposta_id', 'cartao_respostas');
            $problemas['ambiguidade_com_resposta_de_outro_cartao'] = DB::table('ambiguidade_logs as l')
                ->join('cartao_respostas as r', 'r.id', '=', 'l.cartao_resposta_id')->whereColumn('l.cartao_id', '<>', 'r.cartao_id')->count();
            $problemas['visitas_com_nucleo_divergente'] = DB::table('visitas as v')
                ->join('escolas as e', 'e.id', '=', 'v.escola_id')->whereColumn('v.nucleo_id', '<>', 'e.nucleo_id')->count();
            $problemas['provas_sem_autor_existente'] = $this->orfaos('provas', 'criado_por', 'usuarios');
            $problemas['escopos_sem_usuario'] = $this->orfaos('usuario_escopos', 'usuario_id', 'usuarios');
            foreach (['rede' => 'redes', 'nucleo' => 'nucleos', 'escola' => 'escolas', 'turma' => 'turmas', 'aluno' => 'alunos'] as $tipo => $tabela) {
                $problemas['escopos_'.$tipo.'_inexistentes'] = DB::table('usuario_escopos as u')
                    ->leftJoin($tabela.' as alvo', 'u.escopo_id', '=', 'alvo.id')
                    ->where('u.escopo_tipo', $tipo)->whereNull('alvo.id')->count();
            }
            $problemas['escopos_tipo_desconhecido'] = DB::table('usuario_escopos')
                ->whereNotIn('escopo_tipo', ['rede', 'nucleo', 'escola', 'turma', 'aluno'])->count();

            return [
                'versao' => 1,
                'somente_leitura' => true,
                'migracao_autorizada' => false,
                'instituicoes' => [
                    'redes' => DB::table('redes')->orderBy('id')->select('id', 'secretaria_id', 'modalidade')
                        ->selectRaw('CASE WHEN usuario_titular_id IS NULL THEN 0 ELSE 1 END as tem_titular')->get()->all(),
                    'nucleos' => DB::table('nucleos')->orderBy('id')->get(['id', 'rede_id'])->all(),
                    'escolas' => DB::table('escolas')->orderBy('id')->get(['id', 'nucleo_id'])->all(),
                ],
                'contagens' => $contagens,
                'por_escola' => $porEscola,
                'inconsistencias' => $problemas,
                'ocorrencias' => array_sum($problemas),
                'pendencias' => [
                    'sincronizacoes_sem_destino_estruturado' => DB::table('sincronizacoes_seges')->count(),
                    'usuarios_sem_escopo' => DB::table('usuarios as u')->whereNotExists(function (Builder $q) {
                        $q->selectRaw('1')->from('usuario_escopos as e')->whereColumn('e.usuario_id', 'u.id');
                    })->count(),
                    'administradores_rede_a_conciliar' => DB::table('usuarios')->where('perfil', 'admin_rede')->count(),
                    'perfis_sem_escopo' => DB::table('usuarios as u')->whereNotExists(function (Builder $q) {
                        $q->selectRaw('1')->from('usuario_escopos as e')->whereColumn('e.usuario_id', 'u.id');
                    })->select('u.perfil')->selectRaw('COUNT(*) as quantidade')->groupBy('u.perfil')->orderBy('u.perfil')->get()->all(),
                    'concessoes_a_conciliar' => DB::table('usuario_permissoes')->count(),
                    'clientes_contratos_e_modalidades' => 'Exigem manifesto aprovado; não inferidos por este diagnóstico.',
                    'autoria_legada' => 'Campos inexistentes permanecem desconhecidos; não atribuir ao executor.',
                ],
            ];
        });
    }

    private function orfaos(string $filha, string $campo, string $pai, bool $opcional = false): int
    {
        $q = DB::table($filha.' as f')->leftJoin($pai.' as p', 'f.'.$campo, '=', 'p.id')->whereNull('p.id');
        if ($opcional) {
            $q->whereNotNull('f.'.$campo);
        }
        return $q->count();
    }

    private function caminhos(): array
    {
        $escola = [['escolas', 'e', 'origem.escola_id']];
        $turma = [['turmas', 't', 'origem.turma_id'], ['escolas', 'e', 't.escola_id']];
        $prova = [['provas', 'p', 'origem.prova_id'], ['escolas', 'e', 'p.escola_id']];
        $cartao = [['cartoes', 'c', 'origem.cartao_id'], ['provas', 'p', 'c.prova_id'], ['escolas', 'e', 'p.escola_id']];
        return [
            'turmas' => $escola, 'alunos' => $turma, 'provas' => $escola,
            'prova_turmas' => $prova, 'gabaritos' => $prova,
            'gabarito_questoes' => [['gabaritos', 'g', 'origem.gabarito_id'], ['provas', 'p', 'g.prova_id'], ['escolas', 'e', 'p.escola_id']],
            'cartoes' => $prova, 'cartao_respostas' => $cartao, 'notas' => $prova,
            'ambiguidade_logs' => $cartao, 'cartao_envios' => $prova, 'visitas' => $escola,
        ];
    }
}
