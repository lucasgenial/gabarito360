<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/** Diagnóstico somente leitura; IDs são operacionais e exigem guarda restrita. */
class InventariarVinculosLegados
{
    public function executar(): array
    {
        return DB::transaction(function () {
            $pessoas = [];
            foreach (DB::table('usuarios')->orderBy('id')->get(['id', 'perfil', 'ativo']) as $u) {
                $pessoas[] = $this->pessoa($u);
            }
            $orfaos = DB::table('usuario_escopos as ue')->leftJoin('usuarios as u', 'u.id', '=', 'ue.usuario_id')
                ->whereNull('u.id')->count();

            return [
                'versao' => 'v1-a', 'somente_leitura' => true,
                'usuarios' => count($pessoas),
                'candidatos' => array_sum(array_map(fn ($p) => count($p['vinculos_candidatos']), $pessoas)),
                'usuarios_com_pendencias' => count(array_filter($pessoas, fn ($p) => $p['pendencias'] !== [])),
                'escopos_sem_usuario' => $orfaos,
                // Não é autorização de conversão: comparação de acesso e conciliação são V1-B.
                'conversao_liberada' => false,
                'pessoas' => $pessoas,
            ];
        });
    }

    private function pessoa(object $u): array
    {
        $pendencias = [];
        $candidatos = [];
        $permitidos = match ($u->perfil) {
            'admin_rede' => ['rede'],
            'dir_nucleo' => ['nucleo'],
            'dir_escolar', 'coordenador', 'aplicador' => ['escola'],
            'professor' => ['escola', 'turma'],
            'aluno' => ['aluno'],
            default => [],
        };
        if (!$permitidos) $pendencias[] = 'perfil_exige_conciliacao';
        foreach (DB::table('usuario_escopos')->where('usuario_id', $u->id)->orderBy('id')->get() as $escopo) {
            $tipo = $escopo->escopo_tipo;
            if (!in_array($tipo, $permitidos, true)) {
                $pendencias[] = 'escopo_incompativel_com_perfil';
                continue;
            }
            $ref = (int) $escopo->escopo_id;
            $instituicao = $tipo;
            $restricao = null;
            if ($tipo === 'turma' || $tipo === 'aluno') {
                $origem = DB::table($tipo === 'turma' ? 'turmas' : 'alunos')->find($ref);
                $turma = $tipo === 'turma' ? $origem : ($origem ? DB::table('turmas')->find($origem->turma_id) : null);
                if (!$origem || !$turma || !$origem->espaco_id || (int) $origem->espaco_id !== (int) $turma->espaco_id) {
                    $pendencias[] = 'restricao_sem_espaco_coerente';
                    continue;
                }
                $restricao = [$tipo, $ref, (int) $origem->espaco_id];
                $ref = (int) $turma->escola_id;
                $instituicao = 'escola';
            }
            $espaco = DB::table('espacos')->where('tipo', $instituicao)->where($instituicao.'_id', $ref)->first();
            if (!$espaco || !$this->coerente($espaco) || ($restricao && $restricao[2] !== (int) $espaco->id)) {
                $pendencias[] = 'instituicao_sem_espaco_coerente';
                continue;
            }
            $id = (int) $espaco->id;
            $candidatos[$id] ??= ['espaco_id' => $id, 'perfil' => $u->perfil, 'turmas' => [], 'alunos' => []];
            if ($restricao) $candidatos[$id][$tipo === 'turma' ? 'turmas' : 'alunos'][] = $restricao[1];
            if (!$this->hierarquiaAtiva($espaco)) $pendencias[] = 'espaco_ou_hierarquia_inativa';
        }
        if (!$candidatos) $pendencias[] = 'sem_destino_inequivoco';
        if (!$u->ativo) $pendencias[] = 'conta_inativa_nao_ativar';
        $ajustes = DB::table('usuario_permissoes')->where('usuario_id', $u->id)->orderBy('id')
            ->get(['id', 'permissao', 'efeito', 'valida_ate'])->map(fn ($p) => (array) $p)->all();
        if ($ajustes) $pendencias[] = 'permissoes_exigem_destino_aprovado';
        if (DB::table('usuario_vinculos')->where('usuario_id', $u->id)->exists()) $pendencias[] = 'vinculos_existentes_exigem_comparacao';
        ksort($candidatos);
        foreach ($candidatos as &$candidato) {
            foreach (['turmas', 'alunos'] as $campo) {
                $candidato[$campo] = array_values(array_unique($candidato[$campo]));
                sort($candidato[$campo]);
            }
        }
        unset($candidato);
        return ['usuario_id' => (int) $u->id, 'perfil_legado' => $u->perfil,
            'vinculos_candidatos' => array_values($candidatos), 'ajustes_a_conciliar' => $ajustes,
            'pendencias' => array_values(array_unique($pendencias))];
    }

    private function coerente(object $e): bool
    {
        $campo = $e->tipo.'_id';
        foreach (['rede_id', 'nucleo_id', 'escola_id', 'usuario_proprietario_id'] as $ref) {
            if (($ref === $campo) !== ($e->$ref !== null)) return false;
        }
        return DB::table(match ($e->tipo) { 'rede' => 'redes', 'nucleo' => 'nucleos', 'escola' => 'escolas' })
            ->where('id', $e->$campo)->exists();
    }

    private function hierarquiaAtiva(object $e): bool
    {
        if ($e->situacao !== 'ativo' || !DB::table('clientes')->where('id', $e->cliente_id)->where('situacao', 'ativo')->exists()) return false;
        if ($e->tipo === 'rede') return true;
        $tipo = $e->tipo === 'escola' ? 'nucleo' : 'rede';
        $paiId = DB::table($e->tipo === 'escola' ? 'escolas' : 'nucleos')->where('id', $e->{$e->tipo.'_id'})->value($tipo.'_id');
        $pai = DB::table('espacos')->where('tipo', $tipo)->where($tipo.'_id', $paiId)->first();
        return $pai && $this->coerente($pai) && (int) $pai->cliente_id === (int) $e->cliente_id && $this->hierarquiaAtiva($pai);
    }
}
