<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Mutex sem TTL: uma sessão não muda enquanto outra requisição usa sua autoridade. */
class SerializarSessao
{
    public function handle(Request $request, Closure $next)
    {
        $sid = $request->cookie(config('session.cookie'));
        if (!is_string($sid) || !preg_match('/^[a-zA-Z0-9]{40}$/D', $sid)) return $next($request);
        abort_unless(config('session.context_lock_topology') === 'single-host-shared-filesystem', 503,
            'A configuração de concorrência da sessão precisa ser conferida.');
        $dir = config('session.context_lock_path');
        abort_unless(is_string($dir) && $dir !== '', 503);
        if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) abort(503);
        $path = $dir.'/'.hash_hmac('sha256', $sid, (string) config('app.key')).'.lock';
        $fd = @fopen($path, 'c');
        abort_unless(is_resource($fd), 503, 'Não foi possível conferir sua sessão.');
        @chmod($path, 0600);
        $limite = hrtime(true) + 15_000_000_000;
        $adquirido = false;
        try {
            do {
                if (flock($fd, LOCK_EX | LOCK_NB)) { $adquirido = true; break; }
                usleep(20_000);
            } while (hrtime(true) < $limite);
            if (!$adquirido) return response()->json(['message' => 'Há outra operação em andamento. Tente novamente.'], 423);
            return $next($request);
        } finally {
            if ($adquirido) flock($fd, LOCK_UN);
            fclose($fd);
            // Nunca remover o arquivo: unlink criaria dois inodes concorrentes para a mesma sessão.
        }
    }
}
