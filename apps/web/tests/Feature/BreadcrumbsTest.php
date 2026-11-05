<?php

namespace Tests\Feature;

use App\Support\Breadcrumbs;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Tests\TestCase;

class BreadcrumbsTest extends TestCase
{
    private function requisicao(string $rota, string $query = ''): Request
    {
        $r = Request::create('/resultado'.$query);
        $r->setRouteResolver(fn () => (new Route('GET', 'resultado', fn () => null))->name($rota));

        return $r;
    }

    public function test_origem_validada_preserva_contexto_e_nao_usa_endereco_arbitrario(): void
    {
        session(['auth_permissoes' => ['correcao.acompanhar', 'resultado.ver', 'turma.ver']]);
        $r = $this->requisicao('resultados.show', '?from=correcao');
        $r->attributes->set('vinculo_handle', 'contexto-ficticio');
        $dados = ['prova' => ['id' => 4, 'titulo' => 'Prova fictícia'], 'aluno' => ['turma_id' => 7, 'turma_nome' => 'Turma fictícia']];
        $t = Breadcrumbs::itens($r, $dados);
        $this->assertSame(['Início', 'Correção da prova', 'Resultado'], array_column($t, 'rotulo'));
        $this->assertStringContainsString('_ctx=contexto-ficticio', $t[1]['url']);
        $t = Breadcrumbs::itens($this->requisicao('resultados.show', '?from=https://externo.test'), $dados);
        $this->assertSame('Turma fictícia', $t[1]['rotulo']);
        $this->assertStringNotContainsString('externo.test', json_encode($t));
    }

    public function test_relatorio_da_turma_usa_turma_autorizada_e_nao_id_da_query(): void
    {
        session(['auth_permissoes' => ['resultado.ver']]);
        $t = Breadcrumbs::itens($this->requisicao('resultados.show', '?from=rel-turma&turma=999'), ['prova' => ['id' => 4], 'aluno' => ['turma_id' => 7]]);
        $this->assertSame(route('relatorios.turmaProva', [7, 4]), $t[1]['url']);
    }

    public function test_tela_nao_mapeada_mantem_fallback_sem_adivinhar_hierarquia(): void
    {
        $this->assertNull(Breadcrumbs::itens($this->requisicao('perfil')));
    }
}
