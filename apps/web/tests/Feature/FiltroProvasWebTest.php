<?php
namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FiltroProvasWebTest extends TestCase
{
    private function preparar(array $opcoes, int $status=200): void
    {
        config(['services.api.base_url'=>'http://api.test/api']);
        $this->withSession(['auth_token'=>'ficticio','auth_nome'=>'Professor fictício','auth_perfil'=>'professor','auth_permissoes'=>['prova.ver']]);
        Http::preventStrayRequests();
        Http::fake(['*/v1/provas*'=>Http::response(['data'=>['provas'=>[],'kpis'=>[],'filtros'=>['disciplinas'=>$opcoes]]],$status)]);
    }

    public function test_opcoes_vem_da_faceta_autorizada_mesmo_com_lista_vazia(): void
    {
        $this->preparar(['Ciências Humanas','Matemática']);
        $this->get('/provas?disciplina=Matem%C3%A1tica')->assertOk()->assertSee('Ciências Humanas')->assertSee('Matemática')->assertDontSee('Ed. Física')
            ->assertSee('aria-label="Filtrar por disciplina ou área informada"',false)->assertSee('Limpar');
    }

    public function test_valor_antigo_escapado_permanece_identificado_para_limpar(): void
    {
        $this->preparar(['Matemática']);
        $this->get('/provas?disciplina='.urlencode('<img src=x>'))->assertOk()->assertSee('&lt;img src=x&gt; (filtro atual sem resultados)',false)
            ->assertDontSee('<option value="<img',false)->assertSee('Limpar');
    }

    public function test_falha_nao_inventa_opcoes_nem_esconde_filtro_enviado(): void
    {
        $this->preparar([],503);
        $this->get('/provas?disciplina=Linguagens')->assertOk()->assertSee('Listagem temporariamente indisponível')
            ->assertSee('Linguagens (filtro atual sem resultados)')->assertSee('Limpar')->assertDontSee('Ed. Física');
    }
}
