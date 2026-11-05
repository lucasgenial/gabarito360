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
        self::registrarReferencia($id, $dados);
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
        self::registrarReferencia($id, $dados);
    }

    /** Identificador de negócio, não credencial: o vínculo ainda precisa pertencer à sessão. */
    public static function referencia(?array $dados = null): ?string
    {
        $dados ??= self::atual();
        if (!$dados) return null;
        $interno = ($dados['tipo_contexto'] ?? null) === 'interno';
        $numero = $dados[$interno ? 'acesso_interno_id' : 'vinculo_id'] ?? null;
        if ((!is_int($numero) && !is_string($numero)) || !preg_match('/^[1-9][0-9]{0,17}$/D', (string) $numero)) return null;
        $tipo = $interno ? 'interno' : (($dados['espaco']['tipo'] ?? null) === 'pessoal' ? 'pessoal' : 'vinculo');
        return $tipo.'-'.$numero;
    }

    public static function referenciaValida(?string $referencia): bool
    {
        return is_string($referencia) && (bool) preg_match('/^(vinculo|pessoal|interno)-[1-9][0-9]{0,17}$/D', $referencia);
    }

    private static function registrarReferencia(string $id, array $dados): void
    {
        if ($ref = self::referencia($dados)) {
            Cache::put(self::chave('referencia:'.$ref), $id, now()->addMinutes(config('session.lifetime')));
        }
    }

    /** Retorna o handle apenas para uso no servidor. Nunca enviar esse valor ao navegador. */
    public static function resolverReferencia(string $referencia): ?array
    {
        if (!self::referenciaValida($referencia)) return null;
        $id = Cache::get(self::chave('referencia:'.$referencia));
        if (!is_string($id) || !($dados = self::obter($id)) || self::referencia($dados) !== $referencia) return null;
        return ['handle' => $id, 'dados' => $dados];
    }

    public static function referenciaLegada(string $id): ?string
    {
        if (!($dados = self::obter($id)) || !($ref = self::referencia($dados))) return null;
        self::registrarReferencia($id, $dados);
        return $ref;
    }

    public static function esquecer(string $id): void
    {
        if (($dados = self::obter($id)) && ($ref = self::referencia($dados)) && Cache::get(self::chave('referencia:'.$ref)) === $id) {
            Cache::forget(self::chave('referencia:'.$ref));
        }
        Cache::forget(self::chave($id));
    }
    public static function atual(): ?array { return request()->attributes->get('vinculo_contexto'); }
    public static function id(): ?string { return request()->attributes->get('vinculo_handle'); }
}
