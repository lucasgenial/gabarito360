<?php
namespace App\Support;

use App\Jobs\GravarAuditoria;
use App\Models\Usuario;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class Auditoria
{
    public static function capturarContexto(Request $r): void
    {
        if (!config('auditoria.ativa')) return;
        try {
            $u = $r->user();
            if ($u instanceof Usuario && !ContextoVinculo::presente($u) && !ContextoInterno::presente($u)) {
                $u = TokenInterno::resolver(TokenVinculo::resolver($u));
            }
            $contexto = [];
            if ($u instanceof Usuario && ContextoVinculo::presente($u)) {
                $v = ContextoVinculo::resolver($u);
                $contexto = ['vinculo_id'=>$v->id, 'espaco_id'=>$v->espaco_id,
                    'cliente_id'=>$v->espaco->cliente_id, 'escola_id'=>$v->espaco->escola_id,
                    'nucleo_id'=>$v->espaco->nucleo_id, 'perfil'=>$v->perfil];
                if ($v->espaco->escola_id) $contexto['nucleo_id'] = DB::table('escolas')->where('id', $v->espaco->escola_id)->value('nucleo_id');
            } elseif ($u instanceof Usuario && ContextoInterno::presente($u)) {
                $a = ContextoInterno::resolver($u);
                $contexto = ['acesso_interno_id'=>$a->id, 'perfil'=>$a->perfil];
            }
            // Estado apenas do servidor, capturado antes de possíveis revogações na operação.
            $r->attributes->set('auditoria.contexto', $contexto);
        } catch (Throwable) {
            // Recusa não recebe contexto que não passou pela validação.
        }
    }

    public static function registrar(Request $r, Response $resposta): void
    {
        if (!config('auditoria.ativa')) return;
        try {
            $rota = $r->route();
            if (!$rota || !str_starts_with($rota->uri(), 'api/v1/')) return;
            if (in_array($rota->uri(), ['api/v1/health','api/v1/planos'], true) && $r->isMethod('GET')) return;
            $agora = now()->utc();
            $u = $r->user();
            $evento = [
                'id'=>(string) Str::uuid(), 'mes'=>$agora->format('Y-m'), 'ocorrido_em'=>$agora->format('Y-m-d H:i:s.u'),
                'usuario_real_id'=>$r->attributes->get('auditoria.usuario_id', $u?->id),
                'usuario_visto_id'=>$r->attributes->get('auditoria.usuario_id', $u?->id),
                'vinculo_id'=>null, 'acesso_interno_id'=>null, 'espaco_id'=>null, 'cliente_id'=>null,
                'escola_id'=>null, 'nucleo_id'=>null, 'perfil'=>null,
                'metodo'=>$r->method(), 'rota'=>$rota->uri(),
                'referencias'=>json_encode(self::referencias($rota->parameters()), JSON_THROW_ON_ERROR),
                'status_http'=>$resposta->getStatusCode(),
                'resultado'=>$resposta->getStatusCode() < 400 ? 'sucesso' : ($resposta->getStatusCode() < 500 ? 'recusado' : 'erro'),
                'ip_rede'=>self::rede($r->ip()),
            ];
            $evento = array_replace($evento, $r->attributes->get('auditoria.contexto', []));
            $conexao = config('auditoria.connection');
            if (!in_array(config("queue.connections.$conexao.driver"), ['database','redis','sqs','beanstalkd'], true)) {
                throw new \RuntimeException('Auditoria exige fila assíncrona.');
            }
            app(Dispatcher::class)->dispatch((new GravarAuditoria($evento))->onConnection($conexao));
        } catch (Throwable) {
            // Sem exceção, dados pessoais ou payload no log operacional.
            try { Log::error('auditoria_enfileiramento_indisponivel'); } catch (Throwable) {}
        }
    }

    private static function referencias(array $parametros): array
    {
        $saida = [];
        foreach (['id','alunoId','provaId','turmaId','usuarioId'] as $chave) {
            $valor = $parametros[$chave] ?? null;
            if ((is_string($valor) || is_int($valor)) && preg_match('/^[1-9][0-9]{0,17}$/D', (string) $valor)) $saida[$chave] = (string) $valor;
        }
        return $saida;
    }

    private static function rede(?string $ip): ?string
    {
        $bin = $ip ? @inet_pton($ip) : false;
        if ($bin === false) return null;
        $v4 = strlen($bin) === 4;
        return inet_ntop(substr($bin, 0, $v4 ? 3 : 6).str_repeat("\0", $v4 ? 1 : 10)).($v4 ? '/24' : '/48');
    }
}
