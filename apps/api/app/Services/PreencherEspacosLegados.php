<?php

namespace App\Services;

use App\Support\EstruturaEspacos;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/** Preenchimento restrito ao manifesto aprovado; nenhum acesso de usuário é alterado. */
class PreencherEspacosLegados
{
    public function executar(bool $aplicar = false): array
    {
        if ($aplicar && !app()->environment('testing') && !app()->isDownForMaintenance()) {
            throw new RuntimeException('Ative manutenção e drene a fila pelo procedimento aprovado antes de aplicar.');
        }
        return DB::transaction(function () use ($aplicar) {
            $inventario = app(InventarioEspacos::class)->gerar();
            $this->validarManifesto($inventario);
            if ($inventario['ocorrencias'] || $inventario['pendencias']['sincronizacoes_sem_destino_estruturado']) {
                throw new RuntimeException('Inventário com inconsistências ou sincronizações sem destino; conciliação necessária.');
            }
            $resultado = ['manifesto' => EstruturaEspacos::VERSAO, 'aplicado' => $aplicar,
                'clientes_previstos' => 2, 'espacos_previstos' => 13, 'contagens' => $inventario['contagens']];
            if (!$aplicar) {
                return $resultado;
            }
            foreach (EstruturaEspacos::TABELAS as $tabela) {
                foreach (['espaco_id', 'criado_por', 'origem_autoria'] as $campo) {
                    if (!Schema::hasColumn($tabela, $campo)) {
                        throw new RuntimeException('Expansão de estrutura incompleta; execute a migração revisada primeiro.');
                    }
                }
            }
            $escolas = [];
            foreach (EstruturaEspacos::MANIFESTO as $rede => $nucleos) {
                $cliente = $this->cliente($rede);
                $this->espaco($cliente, 'rede', $rede);
                foreach ($nucleos as $nucleo => $idsEscolas) {
                    $this->espaco($cliente, 'nucleo', $nucleo);
                    foreach ($idsEscolas as $escola) {
                        $escolas[$escola] = $this->espaco($cliente, 'escola', $escola);
                    }
                }
            }
            // Apenas IDs técnicos e autoria; nenhuma leitura/descriptografia de conteúdo escolar.
            foreach ($this->fontes() as $tabela => [$sql, $chaves]) {
                $q = DB::query()->fromSub($sql, 'fonte')->orderBy($chaves[0]);
                if (count($chaves) > 1) {
                    $q->orderBy($chaves[1]);
                }
                $q->chunk(200, function ($linhas) use ($tabela, $chaves, $escolas) {
                    foreach ($linhas as $linha) {
                        $espaco = $escolas[$linha->escola_id] ?? null;
                        if (!$espaco) {
                            throw new RuntimeException('Registro sem espaço previsto; preenchimento cancelado.');
                        }
                        $alvo = DB::table($tabela);
                        foreach ($chaves as $chave) {
                            $alvo->where($chave, $linha->$chave);
                        }
                        $atual = (clone $alvo)->lockForUpdate()->first(['espaco_id', 'criado_por', 'origem_autoria']);
                        if (!$atual || ($atual->espaco_id !== null && (int) $atual->espaco_id !== $espaco)) {
                            throw new RuntimeException('Espaço divergente já preenchido; nenhuma transferência automática é permitida.');
                        }
                        $dados = [];
                        if ($atual->espaco_id === null) $dados['espaco_id'] = $espaco;
                        if ($atual->origem_autoria === null) {
                            $dados['origem_autoria'] = $atual->criado_por === null ? 'legado_desconhecido' : 'registrada';
                        }
                        if ($dados) $alvo->update($dados);
                    }
                });
                if (DB::table($tabela)->whereNull('espaco_id')->exists()
                    || DB::table($tabela)->count() !== $inventario['contagens'][$tabela]) {
                    throw new RuntimeException('Contagens ou cobertura divergentes; preenchimento cancelado.');
                }
            }
            return $resultado;
        });
    }

    private function validarManifesto(array $i): void
    {
        $esperadas = array_keys(EstruturaEspacos::MANIFESTO);
        $redes = array_map(fn ($r) => (int) $r->id, $i['instituicoes']['redes']);
        if ($redes !== $esperadas) throw new RuntimeException('Redes divergentes do manifesto aprovado.');
        foreach ($i['instituicoes']['redes'] as $rede) {
            if ($rede->modalidade !== 'institucional' || $rede->secretaria_id !== null || $rede->tem_titular) {
                throw new RuntimeException('Modalidade, secretaria ou titular da rede mudou; revise o manifesto.');
            }
        }
        $nucleos = []; $escolas = [];
        foreach (EstruturaEspacos::MANIFESTO as $rede => $grupos) {
            foreach ($grupos as $nucleo => $ids) {
                $nucleos[$nucleo] = $rede;
                foreach ($ids as $escola) $escolas[$escola] = $nucleo;
            }
        }
        foreach (['nucleos' => [$nucleos, 'rede_id'], 'escolas' => [$escolas, 'nucleo_id']] as $tipo => [$mapa, $campo]) {
            if (count($i['instituicoes'][$tipo]) !== count($mapa)) throw new RuntimeException('Estrutura divergente do manifesto aprovado.');
            foreach ($i['instituicoes'][$tipo] as $r) {
                if (($mapa[$r->id] ?? null) !== (int) $r->$campo) throw new RuntimeException('Hierarquia divergente do manifesto aprovado.');
            }
        }
    }

    private function cliente(int $rede): int
    {
        $chave = 'LEGADO-REDE-'.$rede;
        $r = DB::table('clientes')->where('chave_origem', $chave)->lockForUpdate()->first();
        if ($r) {
            if ($r->tipo !== 'rede' || $r->modalidade !== 'interno' || $r->situacao !== 'ativo') {
                throw new RuntimeException('Cliente existente diverge do manifesto; não sobrescrever.');
            }
            return (int) $r->id;
        }
        return DB::table('clientes')->insertGetId(['chave_origem' => $chave, 'tipo' => 'rede',
            'nome' => 'Cliente interno provisório — rede '.$rede, 'modalidade' => 'interno', 'situacao' => 'ativo',
            'created_at' => now(), 'updated_at' => now()]);
    }

    private function espaco(int $cliente, string $tipo, int $origem): int
    {
        $campo = $tipo.'_id';
        $r = DB::table('espacos')->where($campo, $origem)->lockForUpdate()->first();
        if ($r) {
            if ((int) $r->cliente_id !== $cliente || $r->tipo !== $tipo || $r->situacao !== 'ativo') {
                throw new RuntimeException('Espaço existente diverge do manifesto; não sobrescrever.');
            }
            foreach (['rede_id', 'nucleo_id', 'escola_id', 'usuario_proprietario_id'] as $outro) {
                if ($outro !== $campo && $r->$outro !== null) throw new RuntimeException('Espaço com referências incompatíveis.');
            }
            return (int) $r->id;
        }
        return DB::table('espacos')->insertGetId(['cliente_id' => $cliente, 'tipo' => $tipo,
            'nome' => ucfirst($tipo).' '.$origem, 'situacao' => 'ativo', $campo => $origem,
            'created_at' => now(), 'updated_at' => now()]);
    }

    private function fontes(): array
    {
        $fontes = [];
        foreach (['turmas', 'provas', 'visitas'] as $t) {
            $fontes[$t] = [DB::table($t)->select('id', 'escola_id'), ['id']];
        }
        $fontes['alunos'] = [DB::table('alunos as a')->join('turmas as t', 't.id', '=', 'a.turma_id')->select('a.id', 't.escola_id'), ['id']];
        foreach (['gabaritos', 'cartoes', 'notas', 'cartao_envios', 'prova_turmas'] as $t) {
            $campos = $t === 'prova_turmas' ? ['o.prova_id', 'o.turma_id', 'p.escola_id'] : ['o.id', 'p.escola_id'];
            $fontes[$t] = [DB::table($t.' as o')->join('provas as p', 'p.id', '=', 'o.prova_id')->select($campos), $t === 'prova_turmas' ? ['prova_id', 'turma_id'] : ['id']];
        }
        $fontes['gabarito_questoes'] = [DB::table('gabarito_questoes as o')->join('gabaritos as g', 'g.id', '=', 'o.gabarito_id')
            ->join('provas as p', 'p.id', '=', 'g.prova_id')->select('o.id', 'p.escola_id'), ['id']];
        foreach (['cartao_respostas', 'ambiguidade_logs'] as $t) {
            $fontes[$t] = [DB::table($t.' as o')->join('cartoes as c', 'c.id', '=', 'o.cartao_id')
                ->join('provas as p', 'p.id', '=', 'c.prova_id')->select('o.id', 'p.escola_id'), ['id']];
        }
        return $fontes;
    }
}
