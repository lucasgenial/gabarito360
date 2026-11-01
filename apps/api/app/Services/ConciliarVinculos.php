<?php

namespace App\Services;

use App\Models\Usuario;
use App\Support\ContextoVinculo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

/** Conferência final somente leitura. Nunca reaplica origem sobre vínculo revogado. */
class ConciliarVinculos
{
    public const VERSAO = 'v1-c3-1';

    public function preparar(): array
    {
        return DB::transaction(function () {
            $fonte = app(ConverterVinculosLegados::class)->fonte();
            $destino = $this->destino();
            $origem = collect(app(InventariarVinculosLegados::class)->executar()['pessoas'])->keyBy('usuario_id');
            $usuarios = [];
            foreach (Usuario::orderBy('id')->get(['id', 'perfil', 'ativo']) as $u) {
                $vinculos = [];
                foreach (DB::table('usuario_vinculos')->where('usuario_id', $u->id)->orderBy('id')->get() as $v) {
                    $valido = false;
                    try { $u->noVinculo($v->id); $valido = true; }
                    catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) { /* Relatar, sem fallback. */ }
                    $vinculos[] = ['vinculo_id' => (int) $v->id, 'espaco_id' => (int) $v->espaco_id, 'perfil' => $v->perfil,
                        'situacao' => $v->situacao, 'inicio_em' => $v->inicio_em, 'fim_em' => $v->fim_em,
                        'versao_autorizacao' => (int) $v->versao_autorizacao, 'acesso_valido' => $valido,
                        'turmas' => DB::table('vinculo_turmas')->where('vinculo_id', $v->id)->orderBy('turma_id')->pluck('turma_id')->all(),
                        'alunos' => DB::table('vinculo_alunos')->where('vinculo_id', $v->id)->orderBy('aluno_id')->pluck('aluno_id')->all(),
                        'permissoes_efetivas' => $valido ? ContextoVinculo::permissoes($u->noVinculo($v->id)) : [],
                        'ajustes' => DB::table('vinculo_permissoes')->where('vinculo_id', $v->id)->orderBy('permissao')->get(['permissao', 'efeito', 'valida_ate', 'definida_por'])->all()];
                }
                $padrao = DB::table('perfil_permissoes')->where('perfil', $u->perfil)->where('tipo', 'padrao')->pluck('permissao')->all();
                $ajustes = DB::table('usuario_permissoes')->where('usuario_id', $u->id)->where(fn ($q) => $q->whereNull('valida_ate')->orWhere('valida_ate', '>=', now()->toDateString()))->get();
                $legadas = array_values(array_unique(array_diff(array_merge($padrao, $ajustes->where('efeito', 'conceder')->pluck('permissao')->all()), $ajustes->where('efeito', 'retirar')->pluck('permissao')->all())));
                sort($legadas);
                $revisao = ['permissoes_legadas' => $legadas, 'ativo' => (bool) $u->ativo, 'origem' => $origem[$u->id], 'vinculos' => $vinculos];
                $usuarios[] = ['usuario_id' => (int) $u->id, 'acao' => 'adiar', 'revisao_sha256' => hash('sha256', json_encode($revisao, JSON_THROW_ON_ERROR)), 'revisao' => $revisao];
            }
            if (!hash_equals($fonte, app(ConverterVinculosLegados::class)->fonte()) || !hash_equals($destino, $this->destino())) {
                throw new RuntimeException('Fontes mudaram durante a leitura; repita com escritores pausados.');
            }
            return ['versao' => self::VERSAO, 'fonte_sha256' => $fonte, 'destino_sha256' => $destino, 'usuarios' => $usuarios];
        });
    }

    public function conferir(array $manifesto): array
    {
        Validator::make($manifesto, ['versao' => 'required|in:'.self::VERSAO, 'fonte_sha256' => 'required|string|size:64',
            'destino_sha256' => 'required|string|size:64', 'usuarios' => 'required|array|min:1',
            'usuarios.*.usuario_id' => 'required|integer|distinct:strict', 'usuarios.*.acao' => 'required|in:preservar,bloqueado,adiar',
            'usuarios.*.revisao' => 'required|array', 'usuarios.*.revisao_sha256' => 'required|string|size:64'])->validate();
        $atual = $this->preparar();
        foreach (['fonte_sha256', 'destino_sha256'] as $campo) {
            if (!hash_equals($atual[$campo], $manifesto[$campo])) throw new RuntimeException('Origem ou vínculos alterados; gere nova conciliação, sem sobrescrever o destino.');
        }
        $decisoes = collect($manifesto['usuarios'])->keyBy('usuario_id');
        if ($decisoes->keys()->sort()->values()->all() !== collect($atual['usuarios'])->pluck('usuario_id')->sort()->values()->all()) {
            throw new RuntimeException('Decida todas as contas, inclusive as adiadas e inativas.');
        }
        $pendencias = [];
        $bloqueados = [];
        foreach ($atual['usuarios'] as $u) {
            $d = $decisoes[$u['usuario_id']];
            if (!is_int($d['usuario_id']) || !hash_equals(hash('sha256', json_encode($d['revisao'], JSON_THROW_ON_ERROR)), $d['revisao_sha256']) || !hash_equals($u['revisao_sha256'], $d['revisao_sha256'])) throw new RuntimeException('Revisão da conta está desatualizada.');
            $validos = collect($u['revisao']['vinculos'])->where('acesso_valido', true)->count();
            if ($d['acao'] === 'preservar' && !$validos) $pendencias[] = ['usuario_id' => $u['usuario_id'], 'motivo' => 'sem_vinculo_valido'];
            if ($d['acao'] === 'adiar') $pendencias[] = ['usuario_id' => $u['usuario_id'], 'motivo' => 'conta_adiada'];
            if ($d['acao'] === 'bloqueado') {
                if ($validos) throw new RuntimeException('Conta indicada como bloqueada ainda possui acesso válido.');
                $bloqueados[] = $u['usuario_id'];
            }
            // Um vínculo marcado ativo e vigente, mas estruturalmente inválido, não é aceito como histórico.
            if ($u['revisao']['ativo']) foreach ($u['revisao']['vinculos'] as $v) {
                if ($v['situacao'] === 'ativo' && $v['inicio_em'] <= now() && (!$v['fim_em'] || $v['fim_em'] > now()) && !$v['acesso_valido']) {
                    $pendencias[] = ['usuario_id' => $u['usuario_id'], 'vinculo_id' => $v['vinculo_id'], 'motivo' => 'vinculo_ativo_invalido'];
                }
            }
        }
        $orfaos = DB::table('usuario_escopos as ue')->leftJoin('usuarios as u', 'u.id', '=', 'ue.usuario_id')->whereNull('u.id')->count();
        $envios = DB::table('cartao_envios')->whereNull('vinculo_id')->whereIn('status', ['na_fila', 'processando', 'erro'])->count();
        $convites = DB::table('password_reset_tokens')->whereNull('vinculo_id')->where('expira_em', '>', now())->count();
        return ['versao' => self::VERSAO, 'somente_leitura' => true, 'fonte_sha256' => $atual['fonte_sha256'], 'destino_sha256' => $atual['destino_sha256'],
            'pendencias' => $pendencias, 'contas_bloqueadas_revisadas' => $bloqueados, 'envios_legados_pendentes' => $envios,
            'convites_legados_pendentes' => $convites, 'escopos_orfaos' => $orfaos, 'conciliacao_aprovavel' => !$pendencias && !$envios && !$convites && !$orfaos,
            'publicacao_autorizada' => false, 'autorizacao_por_vinculo_ativada' => false,
            'requisitos_operacionais' => ['V2_selecao_e_interface', 'ensaio_completo', 'backup_e_escritores_pausados', 'filas_Redis_e_jobs_revisados', 'aprovacao_do_responsavel']];
    }

    private function destino(): string
    {
        $fontes = ['usuario_vinculos' => ['id', 'usuario_id', 'espaco_id', 'perfil', 'situacao', 'inicio_em', 'fim_em', 'versao_autorizacao'],
            'vinculo_turmas' => ['vinculo_id', 'espaco_id', 'turma_id'], 'vinculo_alunos' => ['vinculo_id', 'espaco_id', 'aluno_id'],
            'vinculo_permissoes' => ['id', 'vinculo_id', 'permissao', 'efeito', 'valida_ate', 'definida_por'],
            'vinculo_eventos' => ['id', 'vinculo_id', 'acao', 'ator_id', 'ocorrido_em', 'motivo_tecnico', 'valida_ate'],
            'vinculo_convites' => ['id', 'vinculo_id', 'convidado_por', 'convidado_por_vinculo_id', 'expira_em', 'aceito_em', 'recusado_em']];
        $hash = hash_init('sha256');
        foreach ($fontes as $t => $campos) {
            hash_update($hash, $t."\n");
            $q = DB::table($t);
            foreach ($campos as $c) $q->orderBy($c);
            foreach ($q->cursor($campos) as $r) hash_update($hash, json_encode($r, JSON_THROW_ON_ERROR)."\n");
        }
        return hash_final($hash);
    }
}
