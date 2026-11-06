<?php
namespace App\Support;

use Illuminate\Http\Request;

/** Encerra toda a autoridade Web, sem reconstruir acesso a partir do navegador. */
final class SessaoWeb
{
    public static function encerrar(Request $request)
    {
        try {
            if (ContextoWeb::identidade()) app(\App\Services\ApiClient::class)->identidade()->post('/v1/auth/logout');
        } catch (\Illuminate\Http\Client\ConnectionException) {
            // A saída local independe da disponibilidade da API.
        } finally {
            ContextoWeb::limpar();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            foreach (['g360_identidade','g360_revisao','vinculo_contexto','vinculo_handle'] as $chave) $request->attributes->set($chave, null);
        }
        $mensagem = 'Sua sessão terminou. Entre novamente para continuar.';
        if ($request->expectsJson() || $request->ajax()) return response()->json(['message'=>$mensagem,'login_necessario'=>true],401);
        return redirect()->route('login')->with('erro',$mensagem);
    }
}
