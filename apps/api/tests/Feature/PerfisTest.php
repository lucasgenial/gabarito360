<?php

namespace Tests\Feature;

use App\Models\Usuario;
use App\Support\Permissao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Arquivo: apps/api/tests/Feature/PerfisTest.php
 * Função: Testes por perfil (docs/21, etapa 7): para as ações principais, cada perfil recebe
 *         o que o catálogo de docs/03 v2 diz — permitido (2xx) ou negado (403/404/422) —,
 *         respeitando o escopo (núcleo/escola/turma), as extras concedidas, as permissões
 *         retiradas e a correção fechada.
 *
 * Mundo de teste: rede R; núcleos N1 e N2; escola A (N1) e B (N2); turma TA (A) e TB (B);
 * prova PA (escola A, turma TA); um usuário de cada perfil ligado a N1/A/TA.
 */
class PerfisTest extends TestCase
{
    use RefreshDatabase;

    private const NEGADO = [403, 404, 422];

    /** @var array<string, int> */
    private array $id = [];

    /** @var array<string, Usuario> */
    private array $u = [];

    protected function setUp(): void
    {
        parent::setUp();
        $agora = now();
        $ins = fn (string $tab, array $d) => DB::table($tab)->insertGetId($d + ['created_at' => $agora, 'updated_at' => $agora]);

        $this->id['rede'] = $ins('redes', ['nome' => 'Rede Estadual', 'tipo' => 'estadual', 'uf' => 'BA']);
        $this->id['n1'] = $ins('nucleos', ['rede_id' => $this->id['rede'], 'nome' => 'NTE 27']);
        $this->id['n2'] = $ins('nucleos', ['rede_id' => $this->id['rede'], 'nome' => 'NTE 01']);
        $this->id['a'] = $ins('escolas', ['nucleo_id' => $this->id['n1'], 'nome' => 'Escola A', 'inep' => '29000001', 'tipo_rede' => 'estadual']);
        $this->id['b'] = $ins('escolas', ['nucleo_id' => $this->id['n2'], 'nome' => 'Escola B', 'inep' => '29000002', 'tipo_rede' => 'estadual']);
        $this->id['ta'] = $ins('turmas', ['escola_id' => $this->id['a'], 'nome' => '3A', 'serie' => '3ª série', 'turno' => 'tarde', 'ano_letivo' => 2026]);
        $this->id['tb'] = $ins('turmas', ['escola_id' => $this->id['b'], 'nome' => '3B', 'serie' => '3ª série', 'turno' => 'tarde', 'ano_letivo' => 2026]);

        $escopos = [
            'admin_rede'  => [['rede', 'rede']],
            'dir_nucleo'  => [['nucleo', 'n1']],
            'dir_escolar' => [['escola', 'a']],
            'coordenador' => [['escola', 'a']],
            'professor'   => [['escola', 'a'], ['turma', 'ta']],
            'aplicador'   => [['escola', 'a']],
            'aluno'       => [],
        ];
        $n = 0;
        foreach ($escopos as $perfil => $lista) {
            $n++;
            $this->u[$perfil] = Usuario::create([
                'perfil' => $perfil, 'nome' => "Teste {$perfil}", 'email' => "{$perfil}@teste.dev",
                'cpf' => str_pad((string) $n, 11, '0', STR_PAD_LEFT), 'password' => bcrypt('x'), 'ativo' => true,
            ]);
            foreach ($lista as [$tipo, $chave]) {
                DB::table('usuario_escopos')->insert(['usuario_id' => $this->u[$perfil]->id, 'escopo_tipo' => $tipo, 'escopo_id' => $this->id[$chave]]);
            }
        }

        $this->id['pa'] = $ins('provas', ['escola_id' => $this->id['a'], 'criado_por' => $this->u['professor']->id, 'titulo' => 'Simulado',
            'disciplina' => 'Matemática', 'serie' => '3ª série', 'num_questoes' => 5, 'num_alternativas' => 5, 'status' => 'publicada']);
        DB::table('prova_turmas')->insert(['prova_id' => $this->id['pa'], 'turma_id' => $this->id['ta']]);
    }

    private function como(string $perfil): static
    {
        Permissao::limparCache();
        Sanctum::actingAs($this->u[$perfil]->fresh());

        return $this;
    }

    /** Troca marcadores {a}, {n1}, {pa}… pelos ids do mundo de teste. */
    private function uri(string $uri): string
    {
        return preg_replace_callback('/\{(\w+)\}/', fn ($m) => (string) $this->id[$m[1]], $uri);
    }

    private function dados(array $d): array
    {
        array_walk_recursive($d, function (&$v) {
            if (is_string($v) && preg_match('/^\{(\w+)\}$/', $v, $m)) {
                $v = $this->id[$m[1]];
            }
        });

        return $d;
    }

    /**
     * Ações principais × perfis. Cada linha: [método, uri, dados, perfis permitidos].
     * Todo perfil fora da lista de permitidos deve ser negado.
     */
    public static function acoes(): array
    {
        $todos = ['admin_rede', 'dir_nucleo', 'dir_escolar', 'coordenador', 'professor', 'aplicador'];
        $escola = ['nucleo_id' => '{n1}', 'nome' => 'Escola Nova', 'inep' => '29999999', 'tipo_rede' => 'estadual'];
        $prova = ['escola_id' => '{a}', 'titulo' => 'Prova nova', 'disciplina' => 'Português', 'serie' => '3ª série', 'num_questoes' => 10, 'num_alternativas' => 5];
        $prof = ['nome' => 'Novo Professor', 'email' => 'novo.prof@teste.dev', 'cpf' => '12345678901', 'perfil' => 'professor', 'escola_id' => '{a}'];

        return [
            'ver escolas'                 => ['GET', '/escolas', [], $todos],
            'cadastrar escola'            => ['POST', '/escolas', $escola, ['admin_rede', 'dir_nucleo']],
            'consultar o Censo'           => ['GET', '/censo/escolas?busca=escola', [], ['admin_rede', 'dir_nucleo']],
            'editar a escola A'           => ['PUT', '/escolas/{a}', ['nome' => 'Escola A editada'], ['admin_rede', 'dir_nucleo', 'dir_escolar']],
            'ver a equipe'                => ['GET', '/usuarios', [], ['admin_rede', 'dir_nucleo', 'dir_escolar', 'coordenador']],
            'cadastrar professor'         => ['POST', '/usuarios', $prof, ['admin_rede', 'dir_escolar', 'coordenador']],
            'ver turmas'                  => ['GET', '/turmas', [], ['admin_rede', 'dir_nucleo', 'dir_escolar', 'coordenador', 'professor']],
            'ver alunos'                  => ['GET', '/alunos', [], ['admin_rede', 'dir_nucleo', 'dir_escolar', 'coordenador', 'professor']],
            'ver provas'                  => ['GET', '/provas', [], $todos],
            'criar prova'                 => ['POST', '/provas', $prova, ['admin_rede', 'coordenador', 'professor']],
            'ver gabarito da prova A'     => ['GET', '/provas/{pa}/gabarito', [], ['admin_rede', 'dir_nucleo', 'dir_escolar', 'coordenador', 'professor']],
            'relatório da prova A'        => ['GET', '/relatorios/prova/{pa}', [], ['admin_rede', 'dir_nucleo', 'dir_escolar', 'coordenador', 'professor']],
            'relatório da turma TA'       => ['GET', '/relatorios/turma/{ta}', [], ['admin_rede', 'dir_nucleo', 'dir_escolar', 'coordenador', 'professor']],
            'relatório da escola A'       => ['GET', '/relatorios/escola/{a}', [], ['admin_rede', 'dir_nucleo', 'dir_escolar', 'coordenador']],
            'relatório do núcleo N1'      => ['GET', '/relatorios/nucleo/{n1}', [], ['admin_rede', 'dir_nucleo']],
            'ver permissões do professor' => ['GET', '/usuarios/{prof}/permissoes', [], ['admin_rede', 'dir_nucleo', 'dir_escolar', 'coordenador']],
        ];
    }

    #[DataProvider('acoes')]
    public function test_acao_permitida_so_para_os_perfis_do_catalogo(string $metodo, string $uri, array $dados, array $permitidos): void
    {
        $this->id['prof'] = $this->u['professor']->id;
        foreach (array_keys($this->u) as $perfil) {
            DB::beginTransaction();
            $r = $this->como($perfil)->json($metodo, '/api/v1' . $this->uri($uri), $this->dados($dados));
            DB::rollBack();
            $permitido = in_array($perfil, $permitidos, true);
            $this->assertTrue(
                $permitido ? $r->status() < 300 : in_array($r->status(), self::NEGADO, true),
                sprintf('%s %s como %s: esperado %s, recebeu %d %s', $metodo, $uri, $perfil,
                    $permitido ? 'permitido' : 'negado', $r->status(), mb_substr($r->getContent(), 0, 200))
            );
        }
    }

    public function test_escopo_diretor_de_nucleo_e_diretor_escolar_nao_veem_outra_escola(): void
    {
        $this->como('dir_nucleo')->getJson('/api/v1' . $this->uri('/escolas/{b}'))->assertNotFound();
        $this->como('dir_escolar')->getJson('/api/v1' . $this->uri('/escolas/{b}'))->assertNotFound();
        $this->assertContains($this->como('dir_nucleo')->getJson('/api/v1' . $this->uri('/relatorios/nucleo/{n2}'))->status(), self::NEGADO);
        $this->como('admin_rede')->getJson('/api/v1' . $this->uri('/escolas/{b}'))->assertOk();
    }

    public function test_diretor_de_nucleo_so_cadastra_escola_no_proprio_nucleo(): void
    {
        $this->como('dir_nucleo')->postJson('/api/v1/escolas', $this->dados(['nucleo_id' => '{n2}', 'nome' => 'Fora', 'inep' => '29999998', 'tipo_rede' => 'estadual']))
            ->assertStatus(403);
    }

    public function test_professor_ve_so_as_turmas_dele(): void
    {
        $turmas = collect($this->como('professor')->getJson('/api/v1/turmas')->assertOk()->json('data.turmas'))->pluck('id');
        $this->assertTrue($turmas->contains($this->id['ta']));
        $this->assertFalse($turmas->contains($this->id['tb']));
    }

    public function test_quem_cadastra_quem(): void
    {
        $novo = fn (string $perfil, string $email) => ['nome' => 'X', 'email' => $email, 'cpf' => sprintf('%011d', crc32($email)), 'perfil' => $perfil, 'escola_id' => $this->id['a']];
        $this->como('dir_escolar')->postJson('/api/v1/usuarios', $novo('admin_rede', 'x1@t.dev'))->assertStatus(403);
        $this->como('coordenador')->postJson('/api/v1/usuarios', $novo('dir_escolar', 'x2@t.dev'))->assertStatus(403);
        $this->como('dir_nucleo')->postJson('/api/v1/usuarios', $novo('professor', 'x3@t.dev'))->assertStatus(403);
        $this->como('dir_escolar')->postJson('/api/v1/usuarios', $novo('aplicador', 'x4@t.dev'))->assertCreated();
    }

    public function test_extra_concedida_libera_e_retirada_bloqueia(): void
    {
        $prof = $this->u['professor']->id;
        $this->como('professor')->getJson('/api/v1' . $this->uri('/relatorios/escola/{a}'))->assertStatus(403);

        $this->como('dir_escolar')->putJson("/api/v1/usuarios/{$prof}/permissoes", ['itens' => [
            ['permissao' => 'relatorio.escola', 'estado' => 'conceder', 'valida_ate' => now()->addDays(10)->toDateString()],
            ['permissao' => 'turma.ver', 'estado' => 'retirar'],
        ]])->assertOk();

        $this->como('professor')->getJson('/api/v1' . $this->uri('/relatorios/escola/{a}'))->assertOk();
        $this->como('professor')->getJson('/api/v1/turmas')->assertStatus(403);

        // Volta ao padrão do perfil
        $this->como('dir_escolar')->putJson("/api/v1/usuarios/{$prof}/permissoes", ['itens' => [
            ['permissao' => 'relatorio.escola', 'estado' => 'padrao'],
            ['permissao' => 'turma.ver', 'estado' => 'padrao'],
        ]])->assertOk();
        $this->como('professor')->getJson('/api/v1/turmas')->assertOk();
    }

    public function test_ninguem_concede_o_que_nao_pode_dar(): void
    {
        $prof = $this->u['professor']->id;
        // Cadastrar escola nunca é extra do professor (catálogo: "—")
        $r = $this->como('dir_escolar')->putJson("/api/v1/usuarios/{$prof}/permissoes", ['itens' => [['permissao' => 'escola.criar', 'estado' => 'conceder']]]);
        $this->assertContains($r->status(), self::NEGADO);
        // Professor não altera permissões
        $this->como('professor')->putJson("/api/v1/usuarios/{$prof}/permissoes", ['itens' => [['permissao' => 'relatorio.escola', 'estado' => 'conceder']]])
            ->assertStatus(403);
    }

    public function test_extra_vencida_nao_vale(): void
    {
        $prof = $this->u['professor']->id;
        $this->como('dir_escolar')->putJson("/api/v1/usuarios/{$prof}/permissoes", ['itens' => [
            ['permissao' => 'relatorio.escola', 'estado' => 'conceder', 'valida_ate' => now()->toDateString()],
        ]])->assertOk();
        $this->travel(2)->days();
        $this->como('professor')->getJson('/api/v1' . $this->uri('/relatorios/escola/{a}'))->assertStatus(403);
    }

    public function test_correcao_fechada_so_supervisao_reabre(): void
    {
        $pa = $this->id['pa'];
        $this->como('professor')->postJson("/api/v1/provas/{$pa}/correcao/fechar")->assertOk();
        $this->assertNotNull(DB::table('provas')->where('id', $pa)->value('correcao_fechada_em'));

        $this->como('professor')->postJson("/api/v1/provas/{$pa}/correcao/reabrir")->assertStatus(403);
        $this->como('aplicador')->postJson("/api/v1/provas/{$pa}/correcao/reabrir")->assertStatus(403);
        $this->como('coordenador')->postJson("/api/v1/provas/{$pa}/correcao/reabrir")->assertOk();
        $this->assertNull(DB::table('provas')->where('id', $pa)->value('correcao_fechada_em'));
    }

    public function test_revisao_lista_a_fila_e_aceita_em_branco_e_anulada(): void
    {
        $cartao = DB::table('cartoes')->insertGetId(['prova_id' => $this->id['pa'], 'status' => 'ambiguo']);
        foreach ([1, 2] as $q) {
            DB::table('cartao_respostas')->insert(['cartao_id' => $cartao, 'numero_questao' => $q, 'ambigua' => true,
                'alternativas_detectadas' => json_encode([['alternativa' => 'B', 'confianca' => 0.6]])]);
        }

        $this->como('professor');
        $fila = $this->getJson("/api/v1/provas/{$this->id['pa']}/revisao")->assertOk()->json('data.itens');
        $this->assertCount(2, $fila);
        $this->assertSame(['B'], $fila[0]['detectadas']);

        $resolver = "/api/v1/cartoes/{$cartao}/resolver-ambiguidade";
        $this->postJson($resolver, ['numero_questao' => 1, 'alternativa' => 'BRANCO'])->assertOk()->assertJsonPath('data.cartao_status', 'ambiguo');
        $this->postJson($resolver, ['numero_questao' => 2, 'alternativa' => 'ANULADA'])->assertOk()->assertJsonPath('data.cartao_status', 'lido');

        $this->assertSame(0, DB::table('cartao_respostas')->where('cartao_id', $cartao)->whereNotNull('alternativa')->count());
        $this->assertSame(['-', 'N'], DB::table('ambiguidade_logs')->orderBy('id')->pluck('alternativa_escolhida')->all());
        $this->assertCount(0, $this->getJson("/api/v1/provas/{$this->id['pa']}/revisao")->json('data.itens'));
    }

    public function test_recorte_segue_a_geometria_do_cartao(): void
    {
        // 45 questões e 5 alternativas: questão 1 com a última bolinha em x = 69,67 mm e y = 116 mm
        $r = \App\Services\Omr\ImagensCartao::recorte(1, 45, 5);
        $this->assertEqualsWithDelta((69.6667 - 32 - 11) / 210, $r['x'], 0.0001);
        $this->assertEqualsWithDelta(111 / 297, $r['y'], 0.0001);
        $this->assertEqualsWithDelta(48 / 210, $r['largura'], 0.0001);
    }

    public function test_login_devolve_as_permissoes_efetivas(): void
    {
        $r = $this->postJson('/api/v1/auth/login', ['email' => 'professor@teste.dev', 'password' => 'x'])->assertOk();
        $perms = $r->json('data.permissoes');
        $this->assertContains('cartao.enviar', $perms);
        $this->assertNotContains('escola.criar', $perms);
    }
}
