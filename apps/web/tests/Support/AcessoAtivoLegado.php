<?php

namespace Tests\Support;

use App\Support\ContextoWeb;
use Illuminate\Support\Facades\Http;

/** Seleção explícita do acesso legado para as fixtures de interface que o exercitam. */
trait AcessoAtivoLegado
{
    protected array $identidadeLegada = [];

    protected function selecionarAcessoLegado(string $perfil, array $permissoes, string $token = 'ficticio'): void
    {
        config(['services.api.base_url' => 'http://api.test/api']);
        $this->withSession(['auth_token' => $token, 'auth_vinculos' => false]);
        $this->identidadeLegada = ['perfil' => $perfil, 'permissoes' => $permissoes, 'titular' => false];
        $revisao = ContextoWeb::ativarLegado($this->identidadeLegada);
        session()->save();
        $this->withCookie(config('session.cookie'), session()->getId())->withCredentials();
        $this->withHeader('X-G360-Revisao', $revisao);
    }

    protected function apiLegada(array|callable $respostas): void
    {
        // Só a identidade autorizada desta fixture precede os mocks de negócio.
        // Erros, escopos e recusas dos demais endpoints continuam definidos em cada teste.
        if (is_array($respostas)) {
            Http::fake(['api.test/api/v1/auth/me' => Http::response(['data' => $this->identidadeLegada])] + $respostas);
            return;
        }
        Http::fake(function ($request) use ($respostas) {
            if ($request->method() === 'GET' && parse_url($request->url(), PHP_URL_PATH) === '/api/v1/auth/me') {
                return Http::response(['data' => $this->identidadeLegada]);
            }
            return $respostas($request);
        });
    }

    protected function assertNenhumaOperacaoDeNegocioEnviada(): void
    {
        Http::assertNotSent(fn ($request) => $request->method() !== 'GET'
            || parse_url($request->url(), PHP_URL_PATH) !== '/api/v1/auth/me');
    }
}
