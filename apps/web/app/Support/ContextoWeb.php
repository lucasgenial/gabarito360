<?php
namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

/** Handles imutáveis, vinculados à sessão; o navegador nunca recebe o token API. */
class ContextoWeb
{
    private static function chave(string $id): string
    {
        return 'v2:'.hash('sha256', session()->getId().'|'.session('auth_token')).':'.$id;
    }

    public static function criar(array $dados): string
    {
        $id = Str::random(48);
        Cache::put(self::chave($id), Crypt::encryptString(json_encode($dados, JSON_THROW_ON_ERROR)), now()->addMinutes(config('session.lifetime')));
        return $id;
    }

    public static function obter(?string $id): ?array
    {
        if (!$id || !preg_match('/^[A-Za-z0-9]{48}$/D', $id)) return null;
        $valor = Cache::get(self::chave($id));
        if (!$valor) return null;
        try { return json_decode(Crypt::decryptString($valor), true, 512, JSON_THROW_ON_ERROR); }
        catch (\Throwable) { return null; }
    }

    public static function renovar(string $id, array $dados): void
    {
        Cache::put(self::chave($id), Crypt::encryptString(json_encode($dados, JSON_THROW_ON_ERROR)), now()->addMinutes(config('session.lifetime')));
    }

    public static function esquecer(string $id): void { Cache::forget(self::chave($id)); }
    public static function atual(): ?array { return request()->attributes->get('vinculo_contexto'); }
    public static function id(): ?string { return request()->attributes->get('vinculo_handle'); }
}
