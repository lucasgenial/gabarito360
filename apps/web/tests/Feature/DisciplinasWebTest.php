<?php
namespace Tests\Feature;

use App\Support\ContextoWeb;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DisciplinasWebTest extends TestCase
{
    private function preparar(string $perfil = 'coordenador'): array
    {
        config(['services.api.base_url' => 'http://api.test/api', 'disciplinas.ativo' => true]); Http::preventStrayRequests();
        $this->withSession(['auth_token' => 'identidade-ficticia', 'auth_vinculos' => true, 'auth_nome' => 'Fictício']);
        $this->withCookie(config('session.cookie'), session()->getId())->withCredentials();
        $me = ['token' => 'contexto-ficticio', 'tipo_contexto' => 'vinculo', 'vinculo_id' => 1, 'perfil' => $perfil,
            'permissoes' => $perfil === 'coordenador' ? ['turma.ver', 'disciplina.gerir', 'turma.vincular_professor'] : ['turma.ver'],
            'espaco' => ['id' => 5, 'tipo' => 'escola', 'nome' => 'Escola fictícia']];
        return [ContextoWeb::criar($me), $me];
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
        $this->get('/disciplinas?_ctx='.$ctx.'&retorno_turma=1')->assertOk()->assertSee('Disciplinas da escola')->assertSee('Próxima')->assertSee('21 resultado(s)')
            ->assertSee('Voltar à turma')->assertSee('&lt;script&gt;', false)->assertDontSee('<script>teste</script>', false)->assertSee('retorno_turma=1', false);
        config(['disciplinas.ativo' => false]); $this->get('/disciplinas?_ctx='.$ctx)->assertNotFound();
    }

    public function test_formulario_de_referencia_e_proprio_sem_silenciosamente_alterar_origem(): void
    {
        [$ctx, $me] = $this->preparar();
        $this->fake($me, fn () => Http::response(['data' => ['gerir' => true, 'referencias' => [['chave' => 'matematica', 'nome' => 'Matemática', 'area' => 'Matemática']]]]));
        $this->get('/disciplinas/nova?_ctx='.$ctx)->assertOk()->assertSee('Escolher referência nacional')->assertSee('Escolher')->assertDontSee('name="nome"', false);
        $this->get('/disciplinas/nova?_ctx='.$ctx.'&origem=referencia&referencia=matematica')->assertOk()->assertSee('Referência escolhida')->assertSee('value="Matemática"', false);
        $this->get('/disciplinas/nova?_ctx='.$ctx.'&origem=propria')->assertOk()->assertSee('Dados na escola')->assertDontSee('name="referencia"', false);
    }

    public function test_somente_leitura_nao_mostra_botoes_de_gestao(): void
    {
        foreach (['professor', 'dir_escolar', 'vice_dir_escolar'] as $perfil) {
            [$ctx, $me] = $this->preparar($perfil);
            $this->fake($me, fn () => Http::response(['data' => ['disciplina' => $this->disciplina(), 'professores' => [], 'gerir' => false]]));
            $this->get('/disciplinas/1?_ctx='.$ctx)->assertOk()->assertDontSee('Editar disciplina')->assertDontSee('Adicionar professor à disciplina');
            $this->get('/disciplinas/nova?_ctx='.$ctx)->assertForbidden();
            $this->post('/disciplinas?_ctx='.$ctx, ['nome' => 'Tentativa', 'ativo' => 1])->assertForbidden();
        }
    }

    public function test_componente_vazio_e_formulario_elegivel_explicam_periodo_e_pre_requisitos(): void
    {
        [$ctx, $me] = $this->preparar(); $elegiveis = [];
        $this->fake($me, function ($r, $p) use (&$elegiveis) {
            return Http::response(['data' => str_ends_with($p, '/disciplinas/1') ? ['componente' => $this->componente(), 'gerir' => true, 'atribuir' => true, 'elegiveis' => $elegiveis, 'indicacoes' => []] : ['turma' => $this->turma()]]);
        });
        $this->get('/turmas/1/disciplinas/1?_ctx='.$ctx)->assertOk()->assertSee('Nenhum professor indicado nesta disciplina')->assertSee('Adicionar à disciplina')->assertSee('Gerir acesso à turma')->assertDontSee('Confirmar atribuição');
        $elegiveis = [['vinculo_id' => 3, 'nome' => 'Professor fictício']];
        $this->get('/turmas/1/disciplinas/1?_ctx='.$ctx.'&rascunho_inicio=2026-12-01&rascunho_papel=codocencia')->assertOk()->assertSee('Confirmar atribuição')->assertSee('value="2026-12-01"', false)
            ->assertSee('A partir desta data, o professor não atua mais')->assertSee('Nenhuma opção encerra automaticamente');
    }

    public function test_conflito_preserva_rascunho_e_reexige_confirmacao_com_versao_atual(): void
    {
        [$ctx, $me] = $this->preparar(); $d = $this->disciplina();
        $this->fake($me, fn ($r) => $r->method() === 'PUT' ? Http::response(['message' => 'Os dados mudaram.'], 409) : Http::response(['data' => ['disciplina' => $d, 'gerir' => true]]));
        $url = '/disciplinas/1/editar?_ctx='.$ctx;
        $this->from($url)->put('/disciplinas/1?_ctx='.$ctx, ['nome' => 'Meu rascunho', 'sigla' => 'MR', 'ativo' => '1', 'versao' => 1])->assertRedirect()->assertSessionHas('conflito_dc1', true);
        $this->get($url)->assertOk()->assertSee('Atualizar estado e manter preenchimento')->assertSee('value="Meu rascunho"', false)->assertSee('value="1"', false);
        $this->get($url.'&rever=1')->assertRedirect();
        $this->get($url)->assertOk()->assertSee('value="Meu rascunho"', false)->assertSee('value="2"', false)->assertDontSee('Atualizar estado e manter preenchimento');
    }

    public function test_indisponibilidade_nao_parece_lista_vazia_e_nao_revela_erro_interno(): void
    {
        [$ctx, $me] = $this->preparar(); $this->fake($me, fn () => Http::response(['message' => 'SEGREDO-DB'], 500));
        $this->get('/disciplinas?_ctx='.$ctx)->assertStatus(503)->assertDontSee('SEGREDO-DB')->assertDontSee('Nenhuma disciplina por aqui');
    }

    public function test_confirmacao_da_atribuicao_e_dialogo_com_acao_alvo_e_consequencia(): void
    {
        [$ctx,$me]=$this->preparar();$c=$this->componente();
        $c['atribuicoes']=[['id'=>4,'nome'=>'Professor fictício','vinculo_vigente'=>true,'inicio_em'=>'2099-01-01','fim_em'=>null,'papel'=>'titular','estado_periodo'=>'Programada','versao'=>1]];
        $this->fake($me,fn($r,$p)=>Http::response(['data'=>str_ends_with($p,'/disciplinas/1')?['componente'=>$c,'gerir'=>true,'atribuir'=>true,'elegiveis'=>[],'indicacoes'=>[]]:['turma'=>$this->turma()]]));
        $this->get('/turmas/1/disciplinas/1?_ctx='.$ctx)->assertOk()->assertSee('<dialog',false)->assertSee('aria-labelledby="dc1-confirmacao-titulo"',false)
            ->assertSee('data-dc1-verbo="Cancelar atribuição"',false)->assertSee('Cancelar atribuição de Professor fictício em Matemática')
            ->assertSee('A atribuição programada será cancelada. O histórico será preservado.')->assertDontSee('onsubmit="return confirm(',false);
    }
}
