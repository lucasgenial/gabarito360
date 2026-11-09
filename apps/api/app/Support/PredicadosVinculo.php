<?php

namespace App\Support;

use App\Models\Usuario;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/** Folha SQL: não resolve perfil/accessors nem materializa concessões ao compor consultas. */
final class PredicadosVinculo
{
    public static function contexto(Usuario $ator, bool $conferirGrupos = true, ?string $tipoEsperado = null, ?string $perfilEsperado = null): Builder
    {
        $q = DB::table('usuario_vinculos as cv')->join('usuarios as cu', 'cu.id', '=', 'cv.usuario_id')
            ->join('espacos as ce', 'ce.id', '=', 'cv.espaco_id')
            ->where('cv.id', $ator->vinculoContextoId())->where('cv.usuario_id', $ator->id)
            ->where('cu.ativo', true)->where('cv.situacao', 'ativo')
            ->where('cv.inicio_em', '<=', RelogioConsulta::agora())
            ->where(fn ($f) => $f->whereNull('cv.fim_em')->orWhere('cv.fim_em', '>', RelogioConsulta::agora()));
        self::espaco($q, 'ce', $tipoEsperado);
        if (!config('espacos.ativo', false)) $q->whereRaw('1 = 0');
        $q->where(function (Builder $tipos) use ($tipoEsperado) {
            $habilitados = 0;
            foreach ($tipoEsperado === null ? PerfisEspaco::POR_TIPO : [$tipoEsperado => PerfisEspaco::POR_TIPO[$tipoEsperado]] as $tipo => $perfis) {
                if ($tipo === 'pessoal' && !config('espacos.pessoal', false)) continue;
                $habilitados++;
                $tipos->orWhere(function (Builder $ramo) use ($tipo, $perfis) {
                    $ramo->where('ce.tipo', $tipo)->whereIn('cv.perfil', $perfis);
                    if ($tipo === 'pessoal') $ramo->whereColumn('ce.usuario_proprietario_id', 'cv.usuario_id');
                });
            }
            if ($habilitados === 0) $tipos->whereRaw('1 = 0');
        });
        if ($perfilEsperado !== null) $q->where('cv.perfil', $perfilEsperado);
        if ($conferirGrupos && ($perfilEsperado === null || in_array($perfilEsperado, array_merge(GruposFuncionais::ADMINISTRATIVOS, GruposFuncionais::PEDAGOGICOS), true))) $q->whereNotExists(self::conflito());
        return $q;
    }

    /** Mesma regra de intervalos/grupos da escrita; nenhum Schema::hasTable no caminho ativo. */
    public static function conflito(): Builder
    {
        return DB::table('usuario_vinculos as fg')->selectRaw('1')
            ->where('ce.tipo', 'escola')->whereColumn('fg.usuario_id', 'cv.usuario_id')
            ->whereColumn('fg.espaco_id', 'cv.espaco_id')->whereColumn('fg.id', '!=', 'cv.id')
            ->where(fn ($q) => $q
                ->where(fn ($a) => $a->whereIn('cv.perfil', GruposFuncionais::ADMINISTRATIVOS)->whereIn('fg.perfil', GruposFuncionais::PEDAGOGICOS))
                ->orWhere(fn ($p) => $p->whereIn('cv.perfil', GruposFuncionais::PEDAGOGICOS)->whereIn('fg.perfil', GruposFuncionais::ADMINISTRATIVOS)))
            ->whereIn('fg.situacao', ['convidado', 'ativo', 'suspenso'])
            ->where(fn ($f) => $f->whereNull('fg.fim_em')->orWhere('fg.fim_em', '>', RelogioConsulta::agora()))
            ->where(fn ($f) => $f->whereNull('cv.fim_em')->orWhereColumn('fg.inicio_em', '<', 'cv.fim_em'))
            ->whereNotExists(DB::table('usuario_funcoes_excecoes as fx')->selectRaw('1')
                ->whereColumn('fx.usuario_id', 'cv.usuario_id')->whereColumn('fx.espaco_id', 'cv.espaco_id')->where('fx.permitir', true));
    }

    /** Validade estrutural corrente de um espaço, inclusive pais e cliente. Alias só de código. */
    public static function espaco(Builder $q, string $alias, ?string $tipoEsperado = null): Builder
    {
        return $q->where($alias.'.situacao', 'ativo')
            ->whereExists(DB::table('clientes as cliente_valido')->selectRaw('1')
                ->whereColumn('cliente_valido.id', $alias.'.cliente_id')->where('cliente_valido.situacao', 'ativo'))
            ->where(function (Builder $tipos) use ($alias, $tipoEsperado) {
                foreach ($tipoEsperado === null ? ['rede', 'nucleo', 'escola', 'pessoal'] : [$tipoEsperado] as $tipo) {
                    $tipos->orWhere(function (Builder $ramo) use ($alias, $tipo) {
                        $ramo->where($alias.'.tipo', $tipo);
                        $ref = $tipo === 'pessoal' ? 'usuario_proprietario_id' : $tipo.'_id';
                        foreach (['rede_id', 'nucleo_id', 'escola_id', 'usuario_proprietario_id'] as $campo) {
                            if ($campo === $ref) $ramo->whereNotNull($alias.'.'.$campo);
                            else $ramo->whereNull($alias.'.'.$campo);
                        }
                        if ($tipo === 'pessoal') {
                            $ramo->whereExists(DB::table('usuarios as proprietario_valido')->selectRaw('1')
                                ->whereColumn('proprietario_valido.id', $alias.'.'.$ref)->where('proprietario_valido.ativo', true));
                        } else {
                            $ramo->whereExists(self::instituicao($tipo, $alias.'.'.$ref, $alias.'.cliente_id', true, 0, true));
                        }
                    });
                }
            });
    }

    public static function instituicao(string $tipo, mixed $referencia, mixed $cliente, bool $coluna = false, int $nivel = 0, bool $clienteColuna = false): Builder
    {
        $tabela = match ($tipo) { 'rede' => 'redes', 'nucleo' => 'nucleos', 'escola' => 'escolas' };
        $i = 'instituicao_valida_'.$nivel;
        $q = DB::table($tabela.' as '.$i)->selectRaw('1');
        if ($coluna) $q->whereColumn($i.'.id', $referencia);
        else $q->where($i.'.id', $referencia);
        if ($tipo === 'rede') return $q;
        // Hierarquia finita em joins, evitando a profundidade e repetição da antiga recursão.
        $n = $i;
        if ($tipo === 'escola') {
            $n = 'instituicao_nucleo_'.$nivel;
            $q->join('nucleos as '.$n, $n.'.id', '=', $i.'.nucleo_id');
            self::pai($q, 'nucleo', $n.'.id', $cliente, $clienteColuna, 'espaco_nucleo_'.$nivel);
        }
        $r = 'instituicao_rede_'.$nivel;
        $q->join('redes as '.$r, $r.'.id', '=', $n.'.rede_id');
        return self::pai($q, 'rede', $r.'.id', $cliente, $clienteColuna, 'espaco_rede_'.$nivel);
    }

    private static function pai(Builder $q, string $tipo, string $referencia, mixed $cliente, bool $clienteColuna, string $alias): Builder
    {
        $q->join('espacos as '.$alias, $alias.'.'.$tipo.'_id', '=', $referencia)
            ->where($alias.'.tipo', $tipo)->where($alias.'.situacao', 'ativo');
        if ($clienteColuna) $q->whereColumn($alias.'.cliente_id', $cliente);
        else $q->where($alias.'.cliente_id', $cliente);
        foreach (['rede_id', 'nucleo_id', 'escola_id', 'usuario_proprietario_id'] as $campo) {
            if ($campo === $tipo.'_id') $q->whereNotNull($alias.'.'.$campo);
            else $q->whereNull($alias.'.'.$campo);
        }
        return $q;
    }
}
