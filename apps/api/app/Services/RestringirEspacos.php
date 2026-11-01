<?php

namespace App\Services;

use App\Support\Espacos;
use App\Support\EstruturaEspacos;
use App\Support\PlanoIntegridadeEspacos;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/** DDL operacional explícito. Não é chamado por migrate nem ativa o adaptador. */
class RestringirEspacos
{
    public function executar(bool $aplicar = false, ?callable $progresso = null): array
    {
        $versao = DB::selectOne('SELECT VERSION() as versao')->versao;
        if (!str_contains($versao, 'MariaDB') || version_compare(explode('-', $versao)[0], '10.11', '<')) {
            throw new RuntimeException('O corte exige MariaDB 10.11 ou posterior, ensaiado previamente.');
        }
        if (DB::transactionLevel() !== 0) throw new RuntimeException('DDL não pode ser executado dentro de transação de dados.');
        if ($aplicar && (!Espacos::ativo() || (!app()->environment('testing') && !app()->isDownForMaintenance()))) {
            throw new RuntimeException('Corte exige adaptador ativo e manutenção.');
        }
        // Lock de conexão não expira enquanto houver DDL em andamento.
        $lock = 'g360:t2:corte:'.substr(hash('sha256', DB::connection()->getDatabaseName()), 0, 32);
        if (!(int) DB::selectOne('SELECT GET_LOCK(?, 0) as adquirido', [$lock])->adquirido) throw new RuntimeException('Corte já em execução.');
        $espera = DB::selectOne('SELECT @@SESSION.lock_wait_timeout as valor')->valor;
        try {
            DB::statement('SET SESSION lock_wait_timeout = 15');
            // O inventário original conserva o gate do manifesto aprovado (inclusive SEGES vazio).
            app(PreencherEspacosLegados::class)->executar();
            $this->conferirManifesto();
            $antes = app(AuditarIntegridadeEspacos::class)->executar();
            if (!$antes['apto_dados']) throw new RuntimeException('Auditoria com inconsistências; nenhum DDL iniciado.');
            $passos = $this->passos();
            $pendentes = [];
            // Valida TODOS os objetos existentes antes do primeiro DDL.
            foreach ($passos as $nome => $passo) if (!$passo['pronto']()) $pendentes[] = $nome;
            $tempos = [];
            if ($aplicar) {
                foreach ($pendentes as $nome) {
                    $progresso && $progresso(['passo' => $nome, 'estado' => 'iniciando']);
                    $inicio = hrtime(true);
                    DB::statement($passos[$nome]['sql']);
                    if (!$passos[$nome]['pronto']()) throw new RuntimeException('Definição não confirmada após DDL.');
                    $tempos[$nome] = round((hrtime(true) - $inicio) / 1e6, 2);
                    $progresso && $progresso(['passo' => $nome, 'estado' => 'concluido', 'ms' => $tempos[$nome]]);
                }
                $depois = app(AuditarIntegridadeEspacos::class)->executar();
                if (!$depois['apto_dados'] || $antes['contagens'] !== $depois['contagens'] || $antes['cobertura'] != $depois['cobertura']) {
                    throw new RuntimeException('Auditoria ou contagens divergentes após DDL; manter manutenção.');
                }
            }
            return ['plano' => PlanoIntegridadeEspacos::VERSAO, 'servidor' => $versao, 'aplicado' => $aplicar,
                'passos_pendentes_antes' => $pendentes, 'tempos_ms' => $tempos,
                'contagens' => $antes['contagens'], 'esquema_concluido' => $aplicar || !$pendentes];
        } finally {
            DB::statement('SET SESSION lock_wait_timeout = '.(int) $espera);
            DB::selectOne('SELECT RELEASE_LOCK(?)', [$lock]);
        }
    }

    private function conferirManifesto(): void
    {
        $controles = DB::selectOne('SELECT @@foreign_key_checks as fks, @@check_constraint_checks as checks');
        if (!(int) $controles->fks || !(int) $controles->checks) throw new RuntimeException('Verificações do banco desativadas.');
        if (DB::table('clientes')->count() !== 2 || DB::table('espacos')->count() !== 13) throw new RuntimeException('Clientes/espaços divergem do manifesto.');
        foreach (EstruturaEspacos::MANIFESTO as $rede => $nucleos) {
            $cliente = DB::table('clientes')->where('chave_origem', 'LEGADO-REDE-'.$rede)->where('tipo', 'rede')
                ->where('modalidade', 'interno')->where('situacao', 'ativo')->value('id');
            if (!$cliente) throw new RuntimeException('Cliente divergente do manifesto.');
            $referencias = ['rede' => [$rede], 'nucleo' => array_keys($nucleos), 'escola' => array_merge(...array_values($nucleos))];
            foreach ($referencias as $tipo => $ids) {
                if (DB::table('espacos')->where('cliente_id', $cliente)->where('tipo', $tipo)->where('situacao', 'ativo')
                    ->whereIn($tipo.'_id', $ids)->count() !== count($ids)) throw new RuntimeException('Espaço divergente do manifesto.');
            }
        }
        $admins = DB::table('usuarios')->where('perfil', 'admin_rede')->get(['id', 'ativo']);
        if ($admins->count() !== 1 || !$admins[0]->ativo) throw new RuntimeException('Administrador exige conciliação.');
        $escopos = DB::table('usuario_escopos')->where('usuario_id', $admins[0]->id)->get(['escopo_tipo', 'escopo_id']);
        if ($escopos->count() !== 2 || $escopos->where('escopo_tipo', 'rede')->pluck('escopo_id')->map(fn ($id) => (int) $id)->sort()->values()->all() !== [1, 2]) {
            throw new RuntimeException('Atribuições do administrador exigem conciliação.');
        }
    }

    private function passos(): array
    {
        $r = [];
        foreach (EstruturaEspacos::TABELAS as $t) {
            foreach (['espaco_id' => 'bigint unsigned', 'origem_autoria' => 'varchar(24)'] as $c => $tipo) {
                $r[$t.'_'.$c.'_obrigatorio'] = ['sql' => "ALTER TABLE `$t` MODIFY `$c` $tipo NOT NULL", 'pronto' => function () use ($t, $c, $tipo) {
                    $col = DB::table('information_schema.COLUMNS')->where('TABLE_SCHEMA', DB::connection()->getDatabaseName())
                        ->where('TABLE_NAME', $t)->where('COLUMN_NAME', $c)->first();
                    if (!$col || preg_replace('/bigint\(20\)/', 'bigint', strtolower($col->COLUMN_TYPE)) !== $tipo || !in_array($col->COLUMN_DEFAULT, [null, 'NULL'], true)) {
                        throw new RuntimeException("Coluna divergente do plano: $t.$c.");
                    }
                    return $col->IS_NULLABLE === 'NO';
                }];
            }
        }
        foreach (PlanoIntegridadeEspacos::indices() as $nome => [$t, $cols, $unico]) {
            $nome = 't2_'.$nome;
            $r[$nome] = ['sql' => "ALTER TABLE `$t` ADD ".($unico ? 'UNIQUE ' : '')."INDEX `$nome` (".$this->colunas($cols).')',
                'pronto' => function () use ($t, $nome, $cols, $unico) {
                    $indice = collect(Schema::getIndexes($t))->firstWhere('name', $nome);
                    if (!$indice) return false;
                    if ($indice['columns'] !== $cols || $indice['unique'] !== $unico || $indice['type'] !== 'btree') throw new RuntimeException("Índice divergente do plano: $nome.");
                    return true;
                }];
        }
        foreach (PlanoIntegridadeEspacos::checks() as $nome => [$t, $expressao]) {
            $nome = 't2_'.$nome;
            $r[$nome] = ['sql' => "ALTER TABLE `$t` ADD CONSTRAINT `$nome` CHECK ($expressao)", 'pronto' => function () use ($t, $nome, $expressao) {
                $check = DB::table('information_schema.CHECK_CONSTRAINTS')->where('CONSTRAINT_SCHEMA', DB::connection()->getDatabaseName())
                    ->where('TABLE_NAME', $t)->where('CONSTRAINT_NAME', $nome)->first();
                if (!$check) return false;
                $normalizar = fn ($s) => strtolower(preg_replace('/[`()\s]+/', '', $s));
                if ($normalizar($check->CHECK_CLAUSE) !== $normalizar($expressao)) throw new RuntimeException("CHECK divergente do plano: $nome.");
                return true;
            }];
        }
        foreach (PlanoIntegridadeEspacos::relacoes() as $nome => [$t, $cols, $pai, $refs, $acao]) {
            $nome = 't2_'.$nome;
            $r[$nome] = ['sql' => "ALTER TABLE `$t` ADD CONSTRAINT `$nome` FOREIGN KEY (".$this->colunas($cols).
                ") REFERENCES `$pai` (".$this->colunas($refs).") ON DELETE $acao ON UPDATE RESTRICT", 'pronto' => function () use ($t, $nome, $cols, $pai, $refs, $acao) {
                    $fk = collect(Schema::getForeignKeys($t))->firstWhere('name', $nome);
                    if (!$fk) return false;
                    if ($fk['columns'] !== $cols || $fk['foreign_table'] !== $pai || $fk['foreign_columns'] !== $refs
                        || strtoupper($fk['on_delete']) !== $acao || strtoupper($fk['on_update']) !== 'RESTRICT') throw new RuntimeException("FK divergente do plano: $nome.");
                    return true;
                }];
        }
        // Substitui SET NULL de cartão por RESTRICT; o consumidor desvincula o recibo na transação.
        // Instituição/usuário não podem ser gatilhos de exclusão do conteúdo pedagógico.
        foreach ([['cartoes', 'aluno_id', 'alunos'], ['cartao_envios', 'cartao_id', 'cartoes'], ['cartao_envios', 'usuario_id', 'usuarios'],
            ['turmas', 'escola_id', 'escolas'], ['provas', 'escola_id', 'escolas'], ['visitas', 'escola_id', 'escolas'], ['visitas', 'nucleo_id', 'nucleos']] as [$t, $c, $pai]) {
            $nome = $t.'_'.$c.'_foreign';
            $novo = 't2_'.$t.'_'.$c.'_restrito';
            $r[$novo] = ['sql' => "ALTER TABLE `$t` DROP FOREIGN KEY `$nome`, ADD CONSTRAINT `$novo` FOREIGN KEY (`$c`) REFERENCES `$pai` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT",
                'pronto' => function () use ($t, $nome, $novo, $c, $pai) {
                    $fks = collect(Schema::getForeignKeys($t));
                    $nova = $fks->firstWhere('name', $novo);
                    $antiga = $fks->firstWhere('name', $nome);
                    if ($nova) {
                        if ($antiga || $nova['columns'] !== [$c] || $nova['foreign_table'] !== $pai || $nova['foreign_columns'] !== ['id']
                            || $nova['on_delete'] !== 'restrict' || $nova['on_update'] !== 'restrict') throw new RuntimeException('FK substituída divergente.');
                        return true;
                    }
                    if (!$antiga || $antiga['columns'] !== [$c] || $antiga['foreign_table'] !== $pai || $antiga['foreign_columns'] !== ['id']) throw new RuntimeException('FK legada divergente.');
                    return false;
                }];
        }
        // As unicidades por espaço já estão presentes antes de remover as globais.
        foreach (['alunos' => ['matricula', 'cpf_hash'], 'cartao_envios' => ['usuario_id_chave']] as $t => $campos) {
            foreach ($campos as $campo) {
                $nome = $t.'_'.$campo.'_unique';
                $r['remover_'.$nome] = ['sql' => "ALTER TABLE `$t` DROP INDEX `$nome`", 'pronto' => function () use ($t, $nome, $campo) {
                    $i = collect(Schema::getIndexes($t))->firstWhere('name', $nome);
                    if (!$i) return true;
                    $cols = $campo === 'usuario_id_chave' ? ['usuario_id', 'chave'] : [$campo];
                    if ($i['columns'] !== $cols || !$i['unique']) throw new RuntimeException('Unicidade legada divergente.');
                    return false;
                }];
            }
        }
        return $r;
    }

    private function colunas(array $colunas): string
    {
        return implode(', ', array_map(fn ($c) => '`'.$c.'`', $colunas));
    }
}
