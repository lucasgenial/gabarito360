<?php
namespace App\Http\Middleware;

use App\Services\ApiClient;
use App\Support\ContextoWeb;
use Closure;
use Illuminate\Http\Request;

class ContextoPorAba
{
    public function handle(Request $request, Closure $next)
    {
        if (!session('auth_vinculos') || $request->routeIs('vinculos.*', 'trocar-senha', 'trocar-senha.store')) return $next($request);
        $id = $request->header('X-G360-Contexto') ?? $request->input('_ctx');
        if ($id === null && $request->isMethod('GET') && !$request->expectsJson()) return response()->view('auth.contexto');
        if (!is_string($id) || !($dados = ContextoWeb::obter($id))) return $this->selecionar($request);
        $request->attributes->set('vinculo_handle', $id);
        $request->attributes->set('vinculo_contexto', $dados);
        try { $resp = app(ApiClient::class)->get('/v1/auth/me'); }
        catch (\Illuminate\Http\Client\ConnectionException) { abort(503, 'Não foi possível conferir seu acesso. Tente novamente.'); }
        if (in_array($resp->status(), [401, 403], true)) {
            ContextoWeb::esquecer($id);
            return $this->selecionar($request);
        }
        abort_unless($resp->successful(), 503, 'Não foi possível conferir seu acesso. Tente novamente.');
        $me = $resp->json('data');
        abort_unless(is_array($me) && !empty($me['perfil']) && is_array($me['permissoes'] ?? null), 503);
        abort_unless(($dados['tipo_contexto'] ?? 'vinculo') === ($me['tipo_contexto'] ?? 'vinculo'), 503);
        $dados['perfil'] = $me['perfil'];
        $dados['permissoes'] = $me['permissoes'];
        $request->attributes->set('vinculo_contexto', $dados);
        ContextoWeb::renovar($id, $dados);
        if (($dados['tipo_contexto'] ?? null) === 'interno') {
            abort_unless($request->routeIs('painel', 'perfil', 'perfil.*', 'internos.*'), 403, 'Escolha um vínculo para abrir esta área.');
        }
        $response = $next($request);
        // Redirecionamentos de formulários preservam o contexto da requisição.
        if ($response->isRedirect()) {
            $url = $response->headers->get('Location');
            $partes = parse_url($url);
            if ((!isset($partes['host']) || $partes['host'] === $request->getHost())
                && !in_array($partes['path'] ?? '', ['/login', '/vinculos', '/logout'], true)) {
                parse_str($partes['query'] ?? '', $query);
                if (!isset($query['_ctx'])) $response->headers->set('Location', $url.(str_contains($url, '?') ? '&' : '?').'_ctx='.$id);
            }
        }
        return $response;
    }

    private function selecionar(Request $r)
    {
        if ($r->expectsJson()) return response()->json(['message' => 'Escolha novamente seu vínculo.', 'selecionar_vinculo' => true, 'url' => route('vinculos.index')], 409);
        return redirect()->route('vinculos.index')->with('erro', 'Escolha seu vínculo para continuar.');
    }
}
