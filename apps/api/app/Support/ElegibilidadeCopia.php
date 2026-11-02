<?php

namespace App\Support;

use App\Models\Usuario;
use Illuminate\Support\Facades\DB;

/** Fotografia de elegibilidade; não substitui decisão persistida nem revalidação no commit. */
class ElegibilidadeCopia
{
    public static function avaliar(Usuario $origem, Usuario $destino, int $espacoOrigem, int $espacoDestino): array
    {
        abort_unless(config('copias.ativo') === true, 403, 'Cópia indisponível.');
        abort_if($espacoOrigem === $espacoDestino, 403, 'Cópia indisponível.');

        return [
            'origem' => self::lado($origem, $espacoOrigem, 'origem'),
            'destino' => self::lado($destino, $espacoDestino, 'destino'),
        ];
    }

    public static function lado(Usuario $ator, int $espacoId, string $lado): array
    {
        abort_unless(config('copias.ativo') === true && in_array($lado, ['origem', 'destino'], true), 403, 'Cópia indisponível.');
        $capacidade = 'copia.autorizar_'.$lado;
        abort_if(ContextoInterno::presente($ator) || !ContextoVinculo::presente($ator), 403, 'Cópia indisponível.');
        $v = ContextoVinculo::resolver($ator);
        abort_unless(in_array($v->perfil, ['admin_rede', 'dir_escolar', 'professor_autonomo'], true)
            && Permissao::pode($ator, $capacidade), 403, 'Cópia indisponível.');
        $espaco = DB::table('espacos')->find($espacoId);
        abort_unless($espaco && in_array($espaco->tipo, ['escola', 'pessoal'], true)
            && ContextoVinculo::espacoValido($espaco), 403, 'Cópia indisponível.');

        $alcance = match ($v->perfil) {
            'professor_autonomo' => $espaco->tipo === 'pessoal'
                && (int) $espaco->usuario_proprietario_id === (int) $ator->id
                && (int) $v->espaco_id === $espacoId,
            'dir_escolar' => $espaco->tipo === 'escola' && (int) $v->espaco_id === $espacoId,
            'admin_rede' => $espaco->tipo === 'escola'
                && (int) $espaco->cliente_id === (int) $v->espaco->cliente_id
                && in_array((int) $espaco->escola_id, ContextoVinculo::escolas($ator), true),
        };
        abort_unless($alcance, 403, 'Cópia indisponível.');

        return [
            'usuario_id' => (int) $ator->id,
            'vinculo_id' => (int) $v->id,
            'espaco_id' => $espacoId,
            'capacidade' => $capacidade,
            'versao_acesso' => ContextoVinculo::versaoAcesso($ator),
        ];
    }
}
