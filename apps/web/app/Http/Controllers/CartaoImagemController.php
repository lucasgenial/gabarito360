<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\Client\ConnectionException;

/** O navegador apresenta a sessão web; o token da API nunca sai do servidor. */
final class CartaoImagemController extends Controller
{
    public function __invoke(int $id, string $tipo, ApiClient $api)
    {
        abort_unless($id > 0 && in_array($tipo, ['original','miniatura','endireitada'], true), 404);
        try { $resposta = $api->getArquivo('/v1/cartoes/'.$id.'/imagens/'.$tipo); }
        catch (ConnectionException) { abort(503, 'Imagem temporariamente indisponível.'); }
        abort_unless($resposta->successful(), in_array($resposta->status(), [401,403,404,409,429,503], true) ? $resposta->status() : 502);
        $mime = strtolower(trim(explode(';', (string)$resposta->header('Content-Type'))[0]));
        abort_unless(in_array($mime, ['image/jpeg','image/png','image/webp','image/gif','image/bmp'], true), 502);
        return response($resposta->body(), 200, ['Content-Type'=>$mime,
            'Cache-Control'=>'private, no-store', 'X-Content-Type-Options'=>'nosniff',
            'Content-Disposition'=>'inline; filename="cartao-'.$id.'-'.$tipo.'"']);
    }
}
