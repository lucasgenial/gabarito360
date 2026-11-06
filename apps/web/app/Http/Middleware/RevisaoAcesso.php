<?php
namespace App\Http\Middleware;

use App\Support\ContextoWeb;
use Closure;
use Illuminate\Http\Request;

/** Executado após StartSession, ainda sob o mutex que envolve também o salvamento. */
class RevisaoAcesso
{
    public const MENSAGEM = 'Seu acesso ativo mudou. Esta ação não foi executada.';
    public const BINARIOS = ['perfil.foto', 'usuarios.foto', 'escolas.logo', 'redes.logo', 'alunos.foto',
        'correcao.tentativa.imagem', 'cartoes.imagem-segura', 'cartoes.imagem',
        'pedidos.exportacoes.arquivo', 'correcao.pdf', 'resultados.lote', 'resultados.pdf'];
    public const DADOS = ['correcao.status', 'correcao.cartoes', 'correcao.alunos', 'correcao.envio',
        'cartoes.retirada', 'resultados.historico', 'escolas.censo.buscar', 'escolas.censo.pesquisar',
        'instituicoes.municipios', 'catalogo-pedidos.municipios', 'catalogo-atendimento.municipios', 'catalogo-gestao.municipios'];

    public function handle(Request $request, Closure $next)
    {
        ContextoWeb::capturar($request);
        // Estas ações são públicas e invariantes ao acesso; nunca encaminham identidade da conta.
        if ($request->routeIs('home', 'web.health', 'documentos.validar', 'documentos.conferir',
            'planos.index', 'leads.index', 'leads.store', 'leads.sucesso',
            'checkout.index', 'checkout.store', 'checkout.sucesso', 'checkout.falha', 'checkout.pendente')) {
            $request->attributes->set('g360_identidade', null);
            $request->attributes->set('g360_revisao', null);
            $request->attributes->set('vinculo_contexto', null);
            $request->attributes->set('vinculo_handle', null);
            return $next($request);
        }
        if (ContextoWeb::identidade()) {
            $restrito = !$request->isMethodSafe() || $request->ajax() || $request->expectsJson()
                || $request->routeIs(...array_merge(self::BINARIOS, self::DADOS));
            $body = $request->request->get('_revisao');
            $header = $request->header('X-G360-Revisao');
            $atual = ContextoWeb::revisao();
            if ($request->query->has('_revisao') || ($restrito && $body === null && $header === null)
                || ($body !== null && (!is_string($body) || !hash_equals($atual ?? '', $body)))
                || ($header !== null && (!is_string($header) || !hash_equals($atual ?? '', $header)))) {
                return self::recusar($request);
            }
        }
        return $next($request);
    }

    public static function recusar(Request $request)
    {
        if ($request->expectsJson() || $request->ajax()) return response()->json([
            'message' => self::MENSAGEM, 'acesso_alterado' => true, 'url' => route('vinculos.index'),
        ], 409);
        return response()->view('auth.acesso-alterado', ['errors' => new \Illuminate\Support\ViewErrorBag()], 409);
    }
}
