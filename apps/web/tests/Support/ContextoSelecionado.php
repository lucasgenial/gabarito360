<?php
namespace Tests\Support;

use App\Support\ContextoWeb;

/** Fixture explícita de uma seleção já autorizada; não substitui requests ou middleware. */
trait ContextoSelecionado
{
    private function selecionarContextoFixture(array $dados): string
    {
        $revisao = ContextoWeb::ativar($dados);
        session()->save();
        $this->withCookie(config('session.cookie'), session()->getId())->withCredentials();
        $this->withHeader('X-G360-Revisao', $revisao);
        return session('auth_contexto_ativo');
    }
}
