<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Credenciais são aceitas somente no transporte próprio, nunca na query string. */
final class RecusarCredenciaisNaUrl
{
    private const CAMPOS = ['token','access_token','api_token','password','password_confirmation',
        'senha','senha_atual','senha_confirmation','current_password','_token'];

    public function handle(Request $request, Closure $next)
    {
        if (!$this->contemCredencial($request->query())) return $next($request);
        $mensagem = 'Use o formulário para enviar informações de autenticação, sem colocá-las no endereço.';
        $resposta = $request->expectsJson() || $request->is('api/*')
            ? response()->json(['message'=>$mensagem], 400)
            : response($mensagem, 400)->header('Content-Type','text/plain; charset=UTF-8');
        return $resposta->withHeaders(['Cache-Control'=>'private, no-store',
            'Referrer-Policy'=>'no-referrer','X-Content-Type-Options'=>'nosniff']);
    }

    private function contemCredencial(array $parametros): bool
    {
        foreach ($parametros as $campo=>$valor) {
            if (in_array(strtolower((string)$campo), self::CAMPOS, true)) return true;
            if (is_array($valor) && $this->contemCredencial($valor)) return true;
        }
        return false;
    }
}
