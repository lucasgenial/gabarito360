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
    /** Ativa somente a resposta autenticada da API; nunca recebe autorização do navegador. */
    public static function ativar(array $dados): string
    {
        $anterior = session('auth_contexto_ativo');
        $id = self::criar($dados);
        $revisao = Str::random(40);
        session(['auth_contexto_ativo' => $id, 'auth_contexto_revisao' => $revisao]);
        if (is_string($anterior)) self::esquecer($anterior);
        return $revisao;
    }

    public static function ativarLegado(array $me): string
    {
        abort_if(session('auth_vinculos'), 404);
        return self::ativar([
            'tipo_contexto' => 'legado', 'token' => self::identidade(),
            'perfil' => $me['perfil'], 'permissoes' => $me['permissoes'],
        ]);
    }

    public static function limpar(): void
    {
        if (is_string($id = session('auth_contexto_ativo'))) self::esquecer($id);
        session()->forget(['auth_contexto_ativo', 'auth_contexto_revisao']);
    }

    public static function garantirRevisao(): ?string
    {
        if (!session('auth_token')) return null;
        if (!is_string(session('auth_contexto_revisao'))) session(['auth_contexto_revisao' => Str::random(40)]);
        return session('auth_contexto_revisao');
    }

    /** Snapshot por requisição; alterações de sessão posteriores não trocam a autoridade em uso. */
    public static function capturar(\Illuminate\Http\Request $request): void
    {
        if ($request->attributes->has('g360_snapshot')) return;
        $request->attributes->set('g360_snapshot', true);
        $request->attributes->set('g360_identidade', session('auth_token'));
        $request->attributes->set('g360_revisao', self::garantirRevisao());
        $id = session('auth_contexto_ativo');
        $dados = is_string($id) ? self::obter($id) : null;
        // Nunca atravessar a fronteira entre os regimes com um contexto de sessão antigo.
        if ($dados && ((($dados['tipo_contexto'] ?? null) === 'legado') === (bool) session('auth_vinculos'))) $dados = null;
        $request->attributes->set('vinculo_contexto', $dados);
        $request->attributes->set('vinculo_handle', $dados ? $id : null);
    }

    public static function identidade(): ?string
    {
        return request()->attributes->has('g360_snapshot') ? request()->attributes->get('g360_identidade') : session('auth_token');
    }

    public static function revisao(): ?string
    {
        return request()->attributes->has('g360_snapshot') ? request()->attributes->get('g360_revisao') : self::garantirRevisao();
    }

    public static function atual(): ?array { return request()->attributes->get('vinculo_contexto'); }
    public static function id(): ?string { return request()->attributes->get('vinculo_handle'); }
}
