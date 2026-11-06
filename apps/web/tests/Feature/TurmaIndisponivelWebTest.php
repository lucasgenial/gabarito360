<?php
namespace Tests\Feature;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\Support\AcessoAtivoLegado;
use Tests\TestCase;
use Tests\Support\NavegacaoHttp;

class TurmaIndisponivelWebTest extends TestCase
{
    use NavegacaoHttp;
    use AcessoAtivoLegado;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->selecionarAcessoLegado('coordenador', ['turma.ver']);
    }

    public function test_timeout_da_turma_oferece_recuperacao_sem_tentar_outros_dados_ou_repetir_chamada(): void
    {
        $tentativas = [];
        $this->apiLegada(function ($r) use (&$tentativas) {
            $tentativas[] = [$r->method(), parse_url($r->url(), PHP_URL_PATH)];
            throw new ConnectionException('diagnostico-privado');
        });
        $this->consultarTela('/app/turmas/14')->assertStatus(503)->assertHeader('Retry-After', '30')
            ->assertSee('Não foi possível carregar esta turma agora')->assertSee('Tentar novamente')
            ->assertSee('Voltar às turmas')->assertDontSee('diagnostico-privado')
            ->assertDontSee('Desempenho médio')->assertDontSee('Nenhum aluno');
        $this->assertSame([['GET', '/api/v1/turmas/14']], $tentativas);
        Http::assertSentCount(1); // O fake que lança exceção não registra resposta; identidade respondeu.
        Http::assertNotSent(fn ($r) => $r->method() !== 'GET' ||
            !in_array(parse_url($r->url(), PHP_URL_PATH), ['/api/v1/auth/me', '/api/v1/turmas/14'], true));
    }

    public function test_resposta_temporariamente_indisponivel_nao_vira_turma_inexistente(): void
    {
        $this->apiLegada(['*' => Http::response(['message' => 'diagnostico-privado'], 503)]);
        $this->consultarTela('/app/turmas/14')->assertStatus(503)->assertSee('Tente novamente')
            ->assertDontSee('diagnostico-privado')->assertDontSee('Turma não encontrada');
        Http::assertSentCount(2);
    }

    public function test_turma_nao_encontrada_mantem_404_sem_oferecer_retry_de_indisponibilidade(): void
    {
        $this->apiLegada(['*' => Http::response(['message' => 'Não encontrada'], 404)]);
        $this->consultarTela('/app/turmas/14')->assertNotFound()->assertDontSee('Tentar novamente');
        Http::assertSentCount(2);
    }
}
