<?php
namespace App\Http\Middleware;

use App\Services\ApiClient;
use App\Support\ContextoWeb;
use App\Support\UrlContextual;
use Closure;
use Illuminate\Http\Request;

/** Compatibilidade do nome interno: o acesso agora é único por sessão, não por aba. */
class ContextoPorAba
{
    public function handle(Request $request, Closure $next)
    {
        // GET legado só encaminha; nunca interpreta IDs/handles como seleção de acesso.
        if ($request->routeIs('legado.*')) {
            if (!$request->isMethod('GET') || $request->expectsJson() || $request->ajax()) return RevisaoAcesso::recusar($request);
            if (!ContextoWeb::atual()) return $this->selecionar($request);
            return redirect(UrlContextual::redirecionamento($request->fullUrl()));
        }
        if ($request->has('_ctx') || $request->hasHeader('X-G360-Contexto') || $request->hasHeader('X-G360-Acesso')) {
            if ($request->isMethod('GET') && !$request->expectsJson() && !$request->ajax()) return redirect(UrlContextual::redirecionamento($request->fullUrl()));
            return RevisaoAcesso::recusar($request);
        }
        if ($request->routeIs('vinculos.*', 'trocar-senha', 'trocar-senha.store')) return $next($request);
        $dados = ContextoWeb::atual(); $id = ContextoWeb::id();
        if (!$dados || !$id) return $this->selecionar($request);
        try { $resp = app(ApiClient::class)->get('/v1/auth/me'); }
        catch (\Illuminate\Http\Client\ConnectionException) { abort(503, 'Não foi possível conferir seu acesso. Tente novamente.'); }
        if (in_array($resp->status(), [401, 403], true)) {
            return \App\Support\SessaoWeb::encerrar($request);
        }
        abort_unless($resp->successful(), 503, 'Não foi possível conferir seu acesso. Tente novamente.');
        $me = $resp->json('data');
        abort_unless(is_array($me) && !empty($me['perfil']) && is_array($me['permissoes'] ?? null), 503);
        if (($dados['tipo_contexto'] ?? 'vinculo') !== 'legado') {
            abort_unless(($dados['tipo_contexto'] ?? 'vinculo') === ($me['tipo_contexto'] ?? 'vinculo'), 503);
        }
        // A API atual pode omitir estes metadados. Se os publica, não aceitar divergência.
        foreach (['vinculo_id', 'acesso_interno_id'] as $campo) {
            if (array_key_exists($campo, $me)) {
                $ativo = (($dados['tipo_contexto'] ?? 'vinculo') === 'interno') ? 'acesso_interno_id' : 'vinculo_id';
                $nuloPermitido = $campo !== $ativo || ($dados['tipo_contexto'] ?? null) === 'legado';
                abort_unless($this->mesmoIdentificador($dados[$campo] ?? null, $me[$campo], $nuloPermitido), 503);
            }
        }
        if (array_key_exists('espaco', $me)) {
            abort_unless(is_array($me['espaco']) && isset($dados['espaco']), 503);
            foreach (['id', 'tipo'] as $campo) {
                if (!array_key_exists($campo, $me['espaco'])) continue;
                $esperado = $dados['espaco'][$campo] ?? null; $recebido = $me['espaco'][$campo];
                abort_unless($campo === 'id' ? $this->mesmoIdentificador($esperado, $recebido, false)
                    : (is_string($esperado) && is_string($recebido) && $esperado === $recebido), 503);
            }
        }
        session(['auth_avatar_id'=>$me['avatar_id']??null,'auth_tem_foto'=>$me['tem_foto']??false,'auth_imagem_versao'=>$me['imagem_versao']??1]);
        // A conferência acontece antes do controller; este snapshot não muda mais nesta requisição.
        $dados['perfil'] = $me['perfil']; $dados['permissoes'] = $me['permissoes'];
        $request->attributes->set('vinculo_contexto', $dados);
        ContextoWeb::renovar($id, $dados);
        if (in_array($dados['perfil'], ['secretario_escolar', 'auxiliar_secretaria'], true)) {
            $permissao = match ($request->route()->getName()) {
                'painel', 'perfil', 'perfil.update', 'perfil.senha', 'perfil.email', 'perfil.imagem', 'perfil.foto' => null,
                'turmas.index', 'turmas.show' => 'turma.ver',
                'turmas.store', 'turmas.update' => 'turma.gerir',
                'turmas.toggle' => 'turma.desativar',
                'alunos.index', 'alunos.show', 'alunos.foto' => 'aluno.ver',
                'alunos.create', 'alunos.store', 'alunos.update', 'alunos.imagem' => 'aluno.gerir',
                'alunos.toggle' => 'aluno.desativar',
                default => false,
            };
            abort_unless($permissao === null || ($permissao !== false && in_array($permissao, $dados['permissoes'], true)), 403);
        }
        if (($dados['tipo_contexto'] ?? null) === 'interno') {
            abort_unless($request->routeIs('painel', 'perfil', 'perfil.*', 'internos.*', 'clientes.*', 'pedidos.*', 'catalogo-atendimento.*', 'funcoes.*'), 403, 'Escolha um vínculo para abrir esta área.');
        }
        $response = $next($request);
        if ($response->isRedirect() && ($destino = $response->headers->get('Location'))) {
            $response->headers->set('Location', UrlContextual::redirecionamento($destino));
        }
        return $response;
    }

    private function mesmoIdentificador(mixed $esperado, mixed $recebido, bool $nuloPermitido): bool
    {
        if ($esperado === null || $recebido === null) return $nuloPermitido && $esperado === null && $recebido === null;
        foreach ([$esperado, $recebido] as $valor) {
            if ((!is_int($valor) && !is_string($valor)) || !preg_match('/^[1-9][0-9]{0,17}$/D', (string) $valor)) return false;
        }
        return (string) $esperado === (string) $recebido;
    }

    private function selecionar(Request $r)
    {
        if (ContextoWeb::temSelecao()) return \App\Support\SessaoWeb::encerrar($r);
        if ($r->expectsJson() || $r->ajax()) return response()->json(['message' => 'Escolha novamente seu vínculo.', 'selecionar_vinculo' => true, 'url' => route('vinculos.index')], 409);
        return redirect()->route('vinculos.index')->with('erro', 'Escolha seu vínculo para continuar.');
    }
}
