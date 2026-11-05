<?php
namespace App\Http\Middleware;

use App\Services\ApiClient;
use App\Support\ContextoWeb;
use App\Support\UrlContextual;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class ContextoPorAba
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->routeIs('vinculos.*', 'trocar-senha', 'trocar-senha.store')) return $next($request);
        // O identificador do acesso é recurso de negócio; o handle/token permanece no servidor.
        $acesso = $request->route('acesso');
        if ($acesso !== null) $request->route()->forgetParameter('acesso');
        if (!session('auth_vinculos')) {
            if ($acesso !== null && $acesso !== 'sessao') return $this->selecionar($request);
            return $this->semVinculos($request,$next);
        }
        if ($acesso === null) return $this->legado($request);
        if (!is_string($acesso) || !($contexto = ContextoWeb::resolverReferencia($acesso))) return $this->selecionar($request);
        // Não escolher silenciosamente entre duas fontes conflitantes de contexto.
        if ($request->has('_ctx') || $request->hasHeader('X-G360-Contexto') ||
            ($request->hasHeader('X-G360-Acesso') && $request->header('X-G360-Acesso') !== $acesso)) {
            return response()->json(['message'=>'O contexto da requisição mudou. Atualize a página antes de continuar.'],409);
        }
        $id = $contexto['handle']; $dados = $contexto['dados'];
        $request->attributes->set('vinculo_handle', $id);
        $request->attributes->set('vinculo_rota', $acesso);
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
        session(['auth_avatar_id'=>$me['avatar_id']??null,'auth_tem_foto'=>$me['tem_foto']??false,'auth_imagem_versao'=>$me['imagem_versao']??1]);
        $dados['perfil'] = $me['perfil'];
        $dados['permissoes'] = $me['permissoes'];
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
        return $this->executar($request, $next, $acesso);
    }

    private function executar(Request $request, Closure $next, string $acesso)
    {
        URL::defaults(['acesso' => $acesso]);
        try {
            $response = $next($request);
            if ($response->isRedirect() && ($destino = $response->headers->get('Location'))) {
                $response->headers->set('Location', UrlContextual::redirecionamento($destino,$acesso));
            }
            return $response;
        } finally {
            // Evita contaminar a geração de URLs da próxima requisição em runtimes persistentes.
            URL::defaults(['acesso'=>'sessao']);
        }
    }

    private function legado(Request $request)
    {
        // POSTs antigos não são reexecutados/convertidos em redirects. Reabrir o formulário.
        if (!$request->isMethod('GET') || $request->expectsJson()) return response()->json([
            'message'=>'Este endereço foi atualizado. Reabra a página pelo menu antes de continuar.',
            'selecionar_vinculo'=>true,'url'=>route('vinculos.index'),
        ],409);
        if (!session('auth_vinculos')) return redirect(UrlContextual::redirecionamento($request->fullUrl(),'sessao'));
        $id = $request->query('_ctx');
        if ($id === null) return response()->view('auth.contexto');
        if (!is_string($id) || !($ref=ContextoWeb::referenciaLegada($id))) return $this->selecionar($request);
        return redirect(UrlContextual::redirecionamento($request->fullUrl(),$ref));
    }

    /** Compatibilidade do regime anterior; não interpreta contexto nem concede papel. */
    private function semVinculos(Request $request, Closure $next)
    {
        if ($request->isMethod('GET') && $request->query->has('_ctx')) {
            $q=$request->query();unset($q['_ctx']);
            return redirect($request->url().($q?'?'.http_build_query($q):''));
        }
        $rota=$request->route();$acao=$rota->getAction();
        // Os aliases têm o mesmo controller. Preservar routeIs sem alterar o mapa canônico.
        if (str_starts_with($rota->getName()??'','legado.')) $rota->setAction(array_replace($acao,['as'=>substr($rota->getName(),7)]));
        try { return $next($request); }
        finally { $rota->setAction($acao); }
    }

    private function selecionar(Request $r)
    {
        if ($r->expectsJson()) return response()->json(['message' => 'Escolha novamente seu vínculo.', 'selecionar_vinculo' => true, 'url' => route('vinculos.index')], 409);
        return redirect()->route('vinculos.index')->with('erro', 'Escolha seu vínculo para continuar.');
    }
}
