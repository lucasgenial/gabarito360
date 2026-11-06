<?php
namespace Tests\Feature;

use App\Support\ContextoWeb;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Tests\Support\NavegacaoHttp;

class DisciplinasWebTest extends TestCase
{
    use NavegacaoHttp;
    use \Tests\Support\ContextoSelecionado;
    private function preparar(string $perfil = 'coordenador'): array
    {
        config(['services.api.base_url' => 'http://api.test/api', 'disciplinas.ativo' => true]); Http::preventStrayRequests();
        $this->withSession(['auth_token' => 'identidade-ficticia', 'auth_vinculos' => true, 'auth_nome' => 'Fictício']);
        $this->withCookie(config('session.cookie'), session()->getId())->withCredentials();
        $me = ['token' => 'contexto-ficticio', 'tipo_contexto' => 'vinculo', 'vinculo_id' => 1, 'perfil' => $perfil,
            'permissoes' => $perfil === 'coordenador' ? ['turma.ver', 'disciplina.gerir', 'turma.vincular_professor'] : ['turma.ver'],
            'espaco' => ['id' => 5, 'tipo' => 'escola', 'nome' => 'Escola fictícia']];
        $this->selecionarContextoFixture($me);
        return [ContextoWeb::referencia($me), $me];
    }

    private function disciplina(): array { return ['id' => 1, 'nome' => 'Matemática <script>teste</script>', 'sigla' => 'MAT', 'ativo' => true, 'referencia' => 'matematica', 'versao' => 2]; }
    private function componente(): array { return ['id' => 1, 'disciplina_id' => 1, 'nome' => 'Matemática', 'disciplina_ativa' => true, 'versao' => 2, 'atribuicoes' => []]; }
    private function turma(): array { return ['id' => 1, 'nome' => '1º A', 'ano_letivo' => 2026, 'ativo' => true]; }

    private function fake(array $me, callable $resposta): void
    {
        Http::fake(function ($r) use ($me, $resposta) {
            $p = parse_url($r->url(), PHP_URL_PATH);
            if ($p === '/api/v1/auth/me') return Http::response(['data' => $me]);
            if ($p === '/api/v1/auth/vinculos') return Http::response(['data' => []]);
            return $resposta($r, $p);
        });
    }

    public function test_lista_paginada_escape_e_menu_respeitam_contexto(): void
    {
        [$ctx, $me] = $this->preparar();
        $this->fake($me, fn () => Http::response(['data' => ['disciplinas' => [$this->disciplina()], 'gerir' => true], 'meta' => ['pagina' => 1, 'paginas' => 2, 'total' => 21]]));
        $this->consultarTela('/app/disciplinas?retorno_turma=1')->assertOk()->assertSee('Disciplinas da escola')->assertSee('Próxima')->assertSee('21 resultado(s)')
            ->assertSee('Voltar à turma')->assertSee('&lt;script&gt;', false)->assertDontSee('<script>teste</script>', false)->assertDestinoRecurso('disciplinas.index',[],['retorno_turma'=>1]);
        config(['disciplinas.ativo' => false]); $this->consultarTela('/app/disciplinas')->assertNotFound();
    }

    public function test_formulario_de_referencia_e_proprio_sem_silenciosamente_alterar_origem(): void
    {
        [$ctx, $me] = $this->preparar();
        $this->fake($me, fn () => Http::response(['data' => ['gerir' => true, 'referencias' => [['chave' => 'matematica', 'nome' => 'Matemática', 'area' => 'Matemática']]]]));
        $this->consultarTela('/app/disciplinas/nova')->assertOk()->assertSee('Escolher referência nacional')->assertSee('Escolher')->assertDontSee('name="nome"', false);
        $this->consultarTela('/app/disciplinas/nova?origem=referencia&referencia=matematica')->assertOk()->assertSee('Referência escolhida')->assertSee('value="Matemática"', false);
        $this->consultarTela('/app/disciplinas/nova?origem=propria')->assertOk()->assertSee('Dados na escola')->assertDontSee('name="referencia"', false);
    }

    public function test_somente_leitura_nao_mostra_botoes_de_gestao(): void
    {
        foreach (['professor', 'dir_escolar', 'vice_dir_escolar'] as $perfil) {
            [$ctx, $me] = $this->preparar($perfil);
            $this->fake($me, fn () => Http::response(['data' => ['disciplina' => $this->disciplina(), 'professores' => [], 'gerir' => false]]));
            $this->consultarTela('/app/disciplinas/1')->assertOk()->assertDontSee('Editar disciplina')->assertDontSee('Adicionar professor à disciplina');
            $this->consultarTela('/app/disciplinas/nova')->assertForbidden();
            $this->alterarRecurso('POST', '/app/disciplinas', ['nome' => 'Tentativa', 'ativo' => 1])->assertForbidden();
        }
    }

    public function test_componente_vazio_e_formulario_elegivel_explicam_periodo_e_pre_requisitos(): void
    {
        [$ctx, $me] = $this->preparar(); $elegiveis = [];
        $this->fake($me, function ($r, $p) use (&$elegiveis) {
            return Http::response(['data' => str_ends_with($p, '/disciplinas/1') ? ['componente' => $this->componente(), 'gerir' => true, 'atribuir' => true, 'elegiveis' => $elegiveis, 'indicacoes' => []] : ['turma' => $this->turma()]]);
        });
        $this->consultarTela('/app/turmas/1/disciplinas/1')->assertOk()->assertSee('Nenhum professor indicado nesta disciplina')->assertSee('Adicionar à disciplina')->assertSee('Gerir acesso à turma')->assertDontSee('Confirmar atribuição');
        $elegiveis = [['vinculo_id' => 3, 'nome' => 'Professor fictício']];
        $this->consultarTela('/app/turmas/1/disciplinas/1?rascunho_inicio=2026-12-01&rascunho_papel=codocencia')->assertOk()->assertSee('Confirmar atribuição')->assertSee('value="2026-12-01"', false)
            ->assertSee('A partir desta data, o professor não atua mais')->assertSee('Nenhuma atuação permite sobreposição');
    }

    public function test_conflito_preserva_rascunho_e_reexige_confirmacao_com_versao_atual(): void
    {
        [$ctx, $me] = $this->preparar(); $d = $this->disciplina();
        $this->fake($me, fn ($r) => $r->method() === 'PUT' ? Http::response(['message' => 'Os dados mudaram.'], 409) : Http::response(['data' => ['disciplina' => $d, 'gerir' => true]]));
        $url = '/app/disciplinas/1/editar';
        $this->from($url)->alterarRecurso('PUT', '/app/disciplinas/1', ['nome' => 'Meu rascunho', 'sigla' => 'MR', 'ativo' => '1', 'versao' => 1])->assertRetornoTela()->assertSessionHas('conflito_dc1', true);
        $this->consultarTela($url)->assertOk()->assertSee('Atualizar estado e manter preenchimento')->assertSee('value="Meu rascunho"', false)->assertSee('value="1"', false);
        $this->consultarTela($url.'?rever=1')->assertRetornoTela();
        $this->consultarTela($url)->assertOk()->assertSee('value="Meu rascunho"', false)->assertSee('value="2"', false)->assertDontSee('Atualizar estado e manter preenchimento');
    }

    public function test_indisponibilidade_nao_parece_lista_vazia_e_nao_revela_erro_interno(): void
    {
        [$ctx, $me] = $this->preparar(); $this->fake($me, fn () => Http::response(['message' => 'SEGREDO-DB'], 500));
        $this->consultarTela('/app/disciplinas')->assertStatus(503)->assertDontSee('SEGREDO-DB')->assertDontSee('Nenhuma disciplina por aqui');
    }

    public function test_confirmacao_da_atribuicao_e_dialogo_com_acao_alvo_e_consequencia(): void
    {
        [$ctx,$me]=$this->preparar();$c=$this->componente();
        $c['atribuicoes']=[['id'=>4,'nome'=>'Professor fictício','vinculo_vigente'=>true,'inicio_em'=>'2099-01-01','fim_em'=>null,'papel'=>'titular','estado_periodo'=>'Programada','versao'=>1]];
        $this->fake($me,fn($r,$p)=>Http::response(['data'=>str_ends_with($p,'/disciplinas/1')?['componente'=>$c,'gerir'=>true,'atribuir'=>true,'elegiveis'=>[],'indicacoes'=>[]]:['turma'=>$this->turma()]]));
        $this->consultarTela('/app/turmas/1/disciplinas/1')->assertOk()->assertSee('<dialog',false)->assertSee('aria-labelledby="dc1-confirmacao-titulo"',false)
            ->assertSee('data-dc1-verbo="Cancelar atribuição"',false)->assertSee('Cancelar atribuição de Professor fictício em Matemática')
            ->assertSee('A atribuição programada será cancelada. O histórico será preservado.')->assertDontSee('onsubmit="return confirm(',false);
    }

    public function test_disciplina_independente_mostra_areas_mas_nao_repete_seletor_ou_cancelar(): void
    {
        [$ctx,$me]=$this->preparar(); $d=$this->disciplina();
        $d['areas']=[['id'=>11,'nome'=>'Área própria','sigla'=>null,'ativo'=>true],['id'=>12,'nome'=>'Área preservada','sigla'=>'AP','ativo'=>false]];
        $this->fake($me,fn()=>Http::response(['data'=>['disciplina'=>$d,'professores'=>[],'gerir'=>true]]));
        $html=$this->consultarTela('/app/disciplinas/1/editar')->assertOk()->assertDontSee('name="area_ids[]"',false)->getContent();
        $dom=new \DOMDocument; @$dom->loadHTML($html); $xp=new \DOMXPath($dom);
        $this->assertSame(0,$xp->query('//div[contains(@class,"g-pagina")]//a[normalize-space(.)="Cancelar"]')->length);
        $this->assertSame(1,$xp->query('//form//a[normalize-space(.)="Cancelar"]')->length);
        $this->consultarTela('/app/disciplinas/1')->assertOk()->assertSee('Áreas do conhecimento:')->assertSee('Área preservada')->assertSee('(inativa)');
    }

    public function test_cadastro_da_area_separa_identificacao_da_vinculacao_e_nao_duplica_cancelar(): void
    {
        [$ctx,$me]=$this->preparar(); $falhar=false;
        $disciplina=$this->disciplina();
        $this->fake($me,function($r) use (&$falhar,$disciplina) {
            if ($r->method()==='POST') return $falhar?Http::response(['message'=>'Seleção inválida.','errors'=>['disciplina_ids.0'=>['Seleção inválida.']]],422):Http::response(['data'=>['id'=>4]],201);
            return Http::response(['data'=>['disciplinas'=>[$disciplina]]]);
        });
        $url='/app/areas-conhecimento/nova';
        $html=$this->consultarTela($url)->assertOk()->assertSee('Após adicionar a área')->assertDontSee('name="disciplina_ids[]"',false)->getContent();
        $dom=new \DOMDocument; @$dom->loadHTML($html); $xp=new \DOMXPath($dom);
        $this->assertSame(0,$xp->query('//div[contains(@class,"g-pagina")]//a[normalize-space(.)="Cancelar"]')->length);
        $this->assertSame(1,$xp->query('//form//a[normalize-space(.)="Cancelar"]')->length);
        $this->alterarRecurso('POST', '/app/areas-conhecimento',['nome'=>'Área','ativo'=>'1','disciplinas_selecao'=>'1'])->assertRetornoTela();
        Http::assertSent(fn($r)=>$r->method()==='POST' && $r['disciplina_ids']===[]);
        $falhar=true;
        $this->from($url)->alterarRecurso('POST', '/app/areas-conhecimento',['nome'=>'Área','ativo'=>'1','disciplinas_selecao'=>'1','disciplina_ids'=>[1],'disciplina_versoes'=>[1=>2]])->assertRetornoTela();
        $this->consultarTela($url)->assertOk()->assertSee('Seleção inválida.')->assertDontSee('name="disciplina_ids[]"',false);
    }

    public function test_catalogo_de_areas_paginado_e_somente_leitura(): void
    {
        [$ctx,$me]=$this->preparar('professor');
        $this->fake($me,fn()=>Http::response(['data'=>['areas'=>[['id'=>8,'nome'=>'Tecnologia <teste>','sigla'=>null,'ativo'=>true]],'gerir'=>false],'meta'=>['pagina'=>1,'paginas'=>2,'total'=>21]]));
        $this->consultarTela('/app/areas-conhecimento')->assertOk()->assertSee('Tecnologia &lt;teste&gt;',false)->assertSee('Próxima')->assertDontSee('Adicionar área')->assertDontSee('Editar');
        $this->consultarTela('/app/areas-conhecimento/nova')->assertForbidden();
        $this->alterarRecurso('POST', '/app/areas-conhecimento',['nome'=>'Invasão','ativo'=>1])->assertForbidden();
    }

    public function test_orientacoes_sao_recolhidas_nativas_e_nao_escondem_campos_ou_erros(): void
    {
        [$ctx,$me]=$this->preparar();
        $this->fake($me,fn()=>Http::response(['data'=>['gerir'=>true,'referencias'=>[],'areas'=>[]]]));
        $html=$this->consultarTela('/app/disciplinas/nova?origem=propria')->assertOk()->assertSee('Saiba mais')->assertSee('Saiba menos')
            ->assertSee('sobre disciplinas próprias')->assertSee('sobre situação da disciplina')
            ->assertDontSee('name="area_ids[]"',false)->assertSee('name="nome"',false)->assertSee('Adicionar disciplina')->getContent();
        $dom=new \DOMDocument; @$dom->loadHTML('<?xml encoding="UTF-8">'.$html); $xp=new \DOMXPath($dom);
        $this->assertSame(2,$xp->query('//details[contains(@class,"dc1-orientacao")]')->length);
        $this->assertSame(0,$xp->query('//details[contains(@class,"dc1-orientacao")][@open]')->length);
        $this->assertSame(0,$xp->query('//details[contains(@class,"dc1-orientacao")]//input')->length);
        $this->assertSame(2,$xp->query('//details[contains(@class,"dc1-orientacao")]/summary')->length);
    }

    public function test_detalhe_da_area_prioriza_tabela_e_nao_repete_card_de_metadados(): void
    {
        [$ctx,$me]=$this->preparar();
        $this->fake($me,fn($r,$p)=>Http::response(['data'=>str_ends_with($p,'opcoes')?['disciplinas'=>[]]:['area'=>['id'=>8,'nome'=>'Matemática e raciocínio','sigla'=>'MR','ativo'=>true,'versao'=>1,'disciplinas'=>[$this->disciplina()]],'gerir'=>true]]));
        $this->consultarTela('/app/areas-conhecimento/8')->assertOk()->assertSee('Sigla: MR')->assertSee('Disciplinas desta área')
            ->assertSee('data-colecao-completa="true"',false)->assertDontSee('aria-label="Dados da área"',false)->assertDontSee('sobre organização desta área');
    }

    public function test_conflito_sem_referrer_volta_ao_formulario_correto_mesmo_apos_outra_aba(): void
    {
        [$ctx,$me]=$this->preparar();
        $disciplinas=[]; for($i=1;$i<=5;$i++) $disciplinas[]=['id'=>$i,'nome'=>'Disciplina '.$i,'sigla'=>null,'ativo'=>true,'versao'=>2];
        $this->fake($me,fn($r,$p)=>$r->method()==='PUT'?Http::response(['message'=>'Uma disciplina mudou.'],409):Http::response(['data'=>str_ends_with($p,'opcoes')?['disciplinas'=>$disciplinas]:['area'=>['id'=>8,'nome'=>'Área','sigla'=>'MR','ativo'=>true,'versao'=>2,'disciplinas'=>$disciplinas],'gerir'=>true]]));
        $url=route('areas.edit',['id'=>8,]);
        $this->withSession(['_previous'=>['url'=>url('/app/disciplinas/99/editar')]])->withHeaders(['Referer'=>'']);
        $this->alterarRecurso('PUT', '/app/areas-conhecimento/8',['nome'=>'Área','sigla'=>'MR5','ativo'=>'1','versao'=>1,'disciplina_ids'=>[1,2,3,4,5],'disciplina_versoes'=>[1=>1,2=>1,3=>1,4=>1,5=>1],'disciplinas_selecao'=>1])
            ->assertRetornoTela($url)->assertSessionHas('conflito_dc1',true);
        $this->consultarTela($url)->assertOk()->assertSee('value="MR5"',false)->assertSee('Atualizar estado e manter preenchimento');
        $this->consultarTela($url.'?rever=1')->assertRetornoTela($url);
        $html=$this->consultarTela($url)->assertOk()->assertSee('value="MR5"',false)->assertDontSee('Atualizar estado e manter preenchimento')->getContent();
        $dom=new \DOMDocument; @$dom->loadHTML($html); $xp=new \DOMXPath($dom);
        $this->assertSame(0,$xp->query('//input[@name="disciplina_ids[]"]')->length);
        $this->assertSame(1,$xp->query('//input[@name="versao"][@value="2"]')->length);
    }

    public function test_tabela_de_vinculos_tem_duas_colunas_datas_e_acoes_somente_em_periodo_ativo(): void
    {
        [$ctx,$me]=$this->preparar();
        $p=['id'=>41,'disciplina_id'=>1,'nome'=>'Matemática','sigla'=>'MAT','ativo'=>true,'disciplina_ativa'=>true,'disciplina_versao'=>2,'versao'=>1,'vinculado_em'=>'2026-11-04 15:00:00','desvinculado_em'=>null];
        $e=array_replace($p,['id'=>40,'ativo'=>false,'vinculado_em'=>'2025-11-15 15:00:00','desvinculado_em'=>'2026-11-04 14:00:00']);
        $this->fake($me,fn($r,$url)=>Http::response(['data'=>str_ends_with($url,'opcoes')?['disciplinas'=>[['id'=>1,'nome'=>'Matemática','sigla'=>'MAT','ativo'=>true,'versao'=>2],['id'=>2,'nome'=>'Português','sigla'=>null,'ativo'=>true,'versao'=>1]]]:['area'=>['id'=>8,'nome'=>'Área','sigla'=>null,'ativo'=>true,'versao'=>3,'vinculos'=>[$p,$e]],'gerir'=>true]]));
        $html=$this->consultarTela('/app/areas-conhecimento/8')->assertOk()->assertSee('Nome/dados')->assertSee('Data da vinculação:')->assertSee('Data da desvinculação:')->assertSee('04/11/2026 · 12:00')->assertSee('Vínculo encerrado')->getContent();
        $dom=new \DOMDocument; @$dom->loadHTML($html); $xp=new \DOMXPath($dom);
        $this->assertSame(2,$xp->query('//table//th')->length);
        $this->assertSame(1,$xp->query('//table//button[@type="submit"]')->length);
        $this->assertSame(1,$xp->query('//select[@name="disciplina_selecao"]/option[@value="2:1"]')->length);
        $this->assertSame(0,$xp->query('//select[@name="disciplina_selecao"]/option[@value="1:2"]')->length);
        $this->assertSame(1,$xp->query('//table[contains(@class,"tabela-compacta")]')->length);
    }

    public function test_vincular_envia_versoes_e_desvincular_mostra_confirmacao_so_quando_api_exige(): void
    {
        [$ctx,$me]=$this->preparar(); $confirmar=false;
        $p=['id'=>41,'disciplina_id'=>1,'nome'=>'Matemática','sigla'=>'MAT','ativo'=>true,'disciplina_ativa'=>true,'disciplina_versao'=>2,'versao'=>1,'vinculado_em'=>null,'desvinculado_em'=>null];
        $this->fake($me,function($r,$url) use (&$confirmar,$p) {
            if($r->method()==='POST') return $confirmar?Http::response(['codigo'=>'confirmacao_desvinculacao','data'=>['confirmacao'=>'confirmacao-ficticia','provas'=>2]],409):Http::response(['data'=>[]]);
            return Http::response(['data'=>str_ends_with($url,'opcoes')?['disciplinas'=>[]]:['area'=>['id'=>8,'nome'=>'Área','sigla'=>null,'ativo'=>true,'versao'=>3,'vinculos'=>[$p]],'gerir'=>true]]);
        });
        $this->alterarRecurso('POST', '/app/areas-conhecimento/8/vinculos',['disciplina_selecao'=>'2:3','versao'=>2])->assertRetornoTela(route('areas.show',['id'=>8,]));
        Http::assertSent(fn($r)=>$r->method()==='POST' && $r['disciplina_id']===2 && $r['disciplina_versao']===3);
        $confirmar=true;
        $this->alterarRecurso('POST', '/app/areas-conhecimento/8/desvincular',['disciplina_id'=>1,'disciplina_versao'=>2,'versao'=>3,'vinculo_id'=>41])->assertRetornoTela();
        $this->consultarTela('/app/areas-conhecimento/8')->assertOk()->assertSee('data-dc1-confirmacao-area',false)->assertSee('As provas, os gabaritos, as notas')->assertSee('name="confirmacao"',false)->assertSee('Não registrada');
    }

    public function test_erro_local_de_validacao_tambem_ignora_historico_de_outra_aba(): void
    {
        [$ctx,$me]=$this->preparar();$this->fake($me,fn()=>Http::response(['data'=>[]]));
        $this->withSession(['_previous'=>['url'=>url('/app/disciplinas/99/editar')]])->withHeaders(['Referer'=>'']);
        $this->alterarRecurso('POST', '/app/areas-conhecimento',['nome'=>'','ativo'=>1])->assertRetornoTela(route('areas.create',[]))->assertSessionHasErrors('nome');
    }
}
