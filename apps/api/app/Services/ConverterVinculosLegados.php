<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

/** Preparação e conversão V1-B. Não ativa o novo motor de autorização. */
class ConverterVinculosLegados
{
    public const VERSAO = 'v1-b-1';

    /** Impressão das fontes de autorização, sem identidade pessoal nem dados pedagógicos. */
    public function fonte(bool $bloquear = false): string
    {
        $fontes = [
            'usuarios' => ['id', 'perfil', 'ativo'],
            'usuario_escopos' => ['id', 'usuario_id', 'escopo_tipo', 'escopo_id'],
            'usuario_permissoes' => ['id', 'usuario_id', 'permissao', 'efeito', 'valida_ate', 'definida_por'],
            'perfil_permissoes' => ['id', 'perfil', 'permissao', 'tipo'],
            'permissoes' => ['id', 'chave'],
            'clientes' => ['id', 'situacao'],
            'espacos' => ['id', 'cliente_id', 'tipo', 'situacao', 'rede_id', 'nucleo_id', 'escola_id', 'usuario_proprietario_id'],
            'redes' => ['id'], 'nucleos' => ['id', 'rede_id'], 'escolas' => ['id', 'nucleo_id'],
            'turmas' => ['id', 'escola_id', 'espaco_id'], 'alunos' => ['id', 'turma_id', 'espaco_id'],
        ];
        $hash = hash_init('sha256');
        foreach ($fontes as $tabela => $campos) {
            hash_update($hash, $tabela."\n");
            $q = DB::table($tabela)->orderBy('id');
            if ($bloquear) $q->lockForUpdate();
            foreach ($q->cursor($campos) as $linha) {
                hash_update($hash, json_encode($linha, JSON_THROW_ON_ERROR)."\n");
            }
        }
        return hash_final($hash);
    }

    /** Rascunho: todas as contas começam pendentes, sem aprovação inferida. */
    public function preparar(): array
    {
        return DB::transaction(function () {
            $inventario = app(InventariarVinculosLegados::class)->executar();
            return ['versao' => self::VERSAO, 'fonte_sha256' => $this->fonte(),
                'inicio_em' => now()->utc()->format('Y-m-d H:i:s'),
                'usuarios' => array_map(fn ($p) => [
                    'usuario_id' => $p['usuario_id'], 'acao' => 'pendente',
                    'motivo' => 'aguardando_conciliacao', 'vinculos' => [],
                ], $inventario['pessoas'])];
        });
    }

    public function executar(array $manifesto, bool $aplicar = false, ?int $ator = null): array
    {
        if (\App\Support\RegimeVinculos::obrigatorio()) throw new RuntimeException('Conversão legada indisponível após o corte por vínculo.');
        $this->validarFormato($manifesto);
        if ($aplicar && !app()->environment('testing') && !app()->isDownForMaintenance()) {
            throw new RuntimeException('Aplicação exige manutenção e escritores pausados pelo procedimento operacional.');
        }
        if ($aplicar && (!$ator || !DB::table('usuarios')->where('id', $ator)->where('ativo', true)->where('perfil', 'admin_rede')->exists())) {
            throw new RuntimeException('Informe o administrador executor ativo para o histórico.');
        }
        return DB::transaction(function () use ($manifesto, $aplicar, $ator) {
            // Serializa conversores inclusive quando o serviço é chamado sem a CLI.
            if ($aplicar) DB::table('usuarios')->orderBy('id')->lockForUpdate()->get(['id']);
            if (!hash_equals($manifesto['fonte_sha256'], $this->fonte($aplicar))) throw new RuntimeException('Fontes alteradas; refaça o inventário e a conciliação.');
            $inventario = app(InventariarVinculosLegados::class)->executar();
            if ($inventario['escopos_sem_usuario']) throw new RuntimeException('Escopos órfãos exigem conciliação.');
            $pessoas = collect($inventario['pessoas'])->keyBy('usuario_id');
            $ids = array_column($manifesto['usuarios'], 'usuario_id');
            if ($this->ordenar($ids) !== $this->ordenar($pessoas->keys()->all())) throw new RuntimeException('O manifesto deve decidir todas as contas, sem duplicação.');
            $chave = hash('sha256', json_encode($manifesto, JSON_THROW_ON_ERROR));
            $resultado = ['versao' => self::VERSAO, 'manifesto_sha256' => $chave, 'aplicado' => $aplicar,
                'autorizacao_por_vinculo_ativada' => false, 'permissoes_em' => now()->toDateString(), 'adiados' => [], 'comparacoes' => []];
            foreach ($manifesto['usuarios'] as $decisao) {
                $p = $pessoas[$decisao['usuario_id']];
                if ($decisao['acao'] === 'adiar') {
                    if ($decisao['vinculos']) throw new RuntimeException('Conta adiada não pode conter conversão.');
                    $resultado['adiados'][] = ['usuario_id' => $p['usuario_id'], 'motivo' => $decisao['motivo']];
                    continue;
                }
                $bloqueios = array_diff($p['pendencias'], ['permissoes_exigem_destino_aprovado', 'vinculos_existentes_exigem_comparacao']);
                if ($bloqueios) throw new RuntimeException('Conta '.$p['usuario_id'].' com pendências de origem.');
                $candidatos = collect($p['vinculos_candidatos'])->keyBy('espaco_id');
                $destinos = array_column($decisao['vinculos'], 'espaco_id');
                if ($this->ordenar($destinos) !== $this->ordenar($candidatos->keys()->all())) throw new RuntimeException('Destinos devem corresponder exatamente aos escopos conciliáveis.');
                $ajustes = DB::table('usuario_permissoes')->where('usuario_id', $p['usuario_id'])->orderBy('id')->get();
                $usados = [];
                $existentes = DB::table('usuario_vinculos')->where('usuario_id', $p['usuario_id'])->get();
                foreach ($existentes as $v) {
                    if (!in_array((int) $v->espaco_id, $destinos, true) || $v->perfil !== $p['perfil_legado']) throw new RuntimeException('Vínculo existente fora do manifesto.');
                }
                foreach ($decisao['vinculos'] as $destino) {
                    $c = $candidatos[$destino['espaco_id']];
                    foreach (['turmas', 'alunos'] as $restricao) {
                        if ($this->ordenar($destino[$restricao]) !== $c[$restricao]) throw new RuntimeException('Restrições devem preservar exatamente a origem.');
                    }
                    $selecionados = $ajustes->whereIn('id', $destino['ajustes']);
                    if ($this->ordenar($selecionados->pluck('id')->all()) !== $this->ordenar($destino['ajustes'])) throw new RuntimeException('Ajuste inválido, duplicado ou de outra pessoa.');
                    foreach ($selecionados as $a) {
                        $tipo = DB::table('perfil_permissoes')->where('perfil', $p['perfil_legado'])->where('permissao', $a->permissao)->value('tipo');
                        if (($a->efeito === 'conceder' && $tipo !== 'extra') || ($a->efeito === 'retirar' && $tipo !== 'padrao')) throw new RuntimeException('Ajuste incompatível com o catálogo; concilie a origem.');
                    }
                    $usados = array_merge($usados, $destino['ajustes']);
                    $antes = $this->permissoes($p['perfil_legado'], $ajustes);
                    $depois = $this->permissoes($p['perfil_legado'], $selecionados);
                    if (array_diff($depois, $antes)) throw new RuntimeException('Conversão ampliaria permissões; preserve as retiradas em todos os destinos.');
                    $reducoes = array_values(array_diff($antes, $depois));
                    if ($this->ordenar($destino['reducoes_aceitas']) !== $reducoes) throw new RuntimeException('Redução de permissões precisa constar explicitamente no manifesto.');
                    $id = $this->gravarOuConferir($p, $c, $selecionados, $manifesto['inicio_em'], $chave, $aplicar, $ator);
                    $resultado['comparacoes'][] = ['usuario_id' => $p['usuario_id'], 'espaco_id' => $c['espaco_id'],
                        'vinculo_id' => $id, 'antes' => $antes, 'depois' => $depois, 'reducoes_aceitas' => $reducoes];
                }
                if ($this->ordenar(array_unique($usados)) !== $this->ordenar($ajustes->pluck('id')->all())) throw new RuntimeException('Todo ajuste, mesmo vencido, precisa de destino explícito.');
            }
            // Proteção adicional contra fontes que mudaram durante a execução.
            if (!hash_equals($manifesto['fonte_sha256'], $this->fonte($aplicar))) throw new RuntimeException('Fontes alteradas durante a conversão.');
            return $resultado;
        });
    }

    private function gravarOuConferir(array $p, array $c, $ajustes, string $inicio, string $chave, bool $aplicar, ?int $ator): ?int
    {
        $v = DB::table('usuario_vinculos')->where('usuario_id', $p['usuario_id'])->where('espaco_id', $c['espaco_id'])->where('perfil', $c['perfil'])->first();
        $permissoes = $ajustes->map(fn ($a) => ['permissao' => $a->permissao, 'efeito' => $a->efeito,
            'valida_ate' => $a->valida_ate, 'definida_por' => $a->definida_por])->sortBy('permissao')->values()->all();
        if ($v) {
            if ($v->situacao !== 'ativo' || $v->inicio_em !== $inicio || $v->fim_em !== null || (int) $v->versao_autorizacao !== 1
                || DB::table('vinculo_eventos')->where('vinculo_id', $v->id)->count() !== 1
                || !DB::table('vinculo_eventos')->where('vinculo_id', $v->id)->where('acao', 'conversao_legada')->where('motivo_tecnico', 'v1b:'.$chave)->exists()) {
                throw new RuntimeException('Vínculo existente não pertence a esta conversão ou foi alterado.');
            }
            foreach (['turmas' => 'turma_id', 'alunos' => 'aluno_id'] as $t => $campo) {
                $ids = DB::table('vinculo_'.$t)->where('vinculo_id', $v->id)->orderBy($campo)->pluck($campo)->map(fn ($id) => (int) $id)->all();
                if ($ids !== $c[$t]) throw new RuntimeException('Restrições convertidas foram alteradas.');
            }
            $atuais = DB::table('vinculo_permissoes')->where('vinculo_id', $v->id)->orderBy('permissao')
                ->get(['permissao', 'efeito', 'valida_ate', 'definida_por'])->map(fn ($a) => (array) $a)->all();
            if ($atuais !== $permissoes) throw new RuntimeException('Permissões convertidas foram alteradas.');
            return (int) $v->id;
        }
        if (!$aplicar) return null;
        $id = DB::table('usuario_vinculos')->insertGetId(['usuario_id' => $p['usuario_id'], 'espaco_id' => $c['espaco_id'],
            'perfil' => $c['perfil'], 'situacao' => 'ativo', 'inicio_em' => $inicio, 'fim_em' => null,
            'versao_autorizacao' => 1, 'criado_por' => $ator, 'created_at' => now(), 'updated_at' => now()]);
        foreach (['turmas' => 'turma_id', 'alunos' => 'aluno_id'] as $t => $campo) {
            foreach ($c[$t] as $ref) DB::table('vinculo_'.$t)->insert(['vinculo_id' => $id, 'espaco_id' => $c['espaco_id'], $campo => $ref]);
        }
        foreach ($permissoes as $a) DB::table('vinculo_permissoes')->insert($a + ['vinculo_id' => $id, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('vinculo_eventos')->insert(['vinculo_id' => $id, 'acao' => 'conversao_legada', 'ator_id' => $ator,
            'ocorrido_em' => now(), 'motivo_tecnico' => 'v1b:'.$chave]);
        return $id;
    }

    private function permissoes(string $perfil, $ajustes): array
    {
        $padrao = DB::table('perfil_permissoes')->where('perfil', $perfil)->where('tipo', 'padrao')->pluck('permissao')->all();
        $validos = $ajustes->filter(fn ($a) => $a->valida_ate === null || $a->valida_ate >= now()->toDateString());
        return $this->ordenar(array_unique(array_diff(array_merge($padrao, $validos->where('efeito', 'conceder')->pluck('permissao')->all()),
            $validos->where('efeito', 'retirar')->pluck('permissao')->all())));
    }

    private function ordenar(array $valores): array
    {
        sort($valores);
        return array_values($valores);
    }

    private function validarFormato(array $m): void
    {
        Validator::make($m, [
            'versao' => 'required|in:'.self::VERSAO, 'fonte_sha256' => 'required|regex:/^[a-f0-9]{64}$/',
            'inicio_em' => 'required|date_format:Y-m-d H:i:s|before_or_equal:now', 'usuarios' => 'required|array|min:1',
            'usuarios.*' => 'required|array:usuario_id,acao,motivo,vinculos',
            'usuarios.*.usuario_id' => 'required|integer|min:1|distinct:strict',
            'usuarios.*.acao' => 'required|in:converter,adiar',
            'usuarios.*.motivo' => 'required|in:conversao_revisada,sem_destino,origem_inconsistente,conta_inativa,aguardando_conciliacao',
            'usuarios.*.vinculos' => 'present|array',
            'usuarios.*.vinculos.*' => 'required|array:espaco_id,turmas,alunos,ajustes,reducoes_aceitas',
            'usuarios.*.vinculos.*.espaco_id' => 'required|integer|min:1',
            'usuarios.*.vinculos.*.turmas' => 'present|array', 'usuarios.*.vinculos.*.turmas.*' => 'integer|min:1',
            'usuarios.*.vinculos.*.alunos' => 'present|array', 'usuarios.*.vinculos.*.alunos.*' => 'integer|min:1',
            'usuarios.*.vinculos.*.ajustes' => 'present|array', 'usuarios.*.vinculos.*.ajustes.*' => 'integer|min:1',
            'usuarios.*.vinculos.*.reducoes_aceitas' => 'present|array', 'usuarios.*.vinculos.*.reducoes_aceitas.*' => 'string|max:60',
        ])->validate();
        // JSON precisa de IDs numéricos, evitando comparações frouxas entre destinos.
        foreach ($m['usuarios'] as $u) {
            if (!is_int($u['usuario_id'])) throw new RuntimeException('IDs devem ser inteiros JSON.');
            if ($u['acao'] === 'converter' && $u['motivo'] !== 'conversao_revisada') throw new RuntimeException('Conversão exige motivo conversao_revisada.');
            foreach ($u['vinculos'] as $v) {
                foreach (array_merge([$v['espaco_id']], $v['turmas'], $v['alunos'], $v['ajustes']) as $id) {
                    if (!is_int($id)) throw new RuntimeException('IDs devem ser inteiros JSON.');
                }
            }
        }
    }
}
