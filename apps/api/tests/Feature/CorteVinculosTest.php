<?php
namespace Tests\Feature;

use App\Models\Usuario;
use App\Services\ConciliarVinculos;
use App\Services\ConviteVinculo;
use App\Support\Permissao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CorteVinculosTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void
    {
        parent::setUp();
        config(['espacos.ativo' => true]);
        $this->travelTo(now()->startOfSecond());
        Permissao::limparCache();
        foreach ([1, 2] as $id) {
            Usuario::forceCreate(['id' => $id, 'perfil' => 'admin_rede', 'nome' => 'Pessoa fictícia', 'email' => $id.'@example.test', 'cpf' => str_pad((string) $id, 11, '0', STR_PAD_LEFT), 'password' => 'ficticio', 'ativo' => true]);
            DB::table('clientes')->insert(['id' => $id, 'tipo' => 'rede', 'nome' => 'Cliente', 'modalidade' => 'interno', 'situacao' => 'ativo']);
            DB::table('redes')->insert(['id' => $id, 'nome' => 'Rede', 'tipo' => 'estadual', 'uf' => 'BA']);
            DB::table('nucleos')->insert(['id' => $id, 'rede_id' => $id, 'nome' => 'Núcleo']);
            DB::table('escolas')->insert(['id' => $id, 'nucleo_id' => $id, 'nome' => 'Escola', 'inep' => '0000000'.$id, 'tipo_rede' => 'estadual']);
            foreach (['rede' => 0, 'nucleo' => 2, 'escola' => 4] as $tipo => $offset) {
                DB::table('espacos')->insert(['id' => $id + $offset, 'cliente_id' => $id, 'tipo' => $tipo, 'nome' => 'Espaço', 'situacao' => 'ativo', $tipo.'_id' => $id]);
            }
            DB::table('usuario_escopos')->insert(['usuario_id' => 1, 'escopo_tipo' => 'rede', 'escopo_id' => $id]);
            DB::table('turmas')->insert(['id' => $id, 'escola_id' => $id, 'espaco_id' => $id + 4, 'nome' => '3A', 'serie' => '3', 'turno' => 'tarde', 'ano_letivo' => 2026]);
            DB::table('alunos')->insert(['id' => $id, 'turma_id' => $id, 'espaco_id' => $id + 4, 'nome' => 'Fictício', 'matricula' => 'C-'.$id]);
            DB::table('provas')->insert(['id' => $id, 'escola_id' => $id, 'espaco_id' => $id + 4, 'criado_por' => 1,
                'titulo' => 'Prova', 'disciplina' => 'Teste', 'serie' => '3', 'num_questoes' => 1, 'num_alternativas' => 4]);
            DB::table('prova_turmas')->insert(['prova_id' => $id, 'turma_id' => $id, 'espaco_id' => $id + 4]);
        }
        $this->vinculo(1, 1, 5, 'dir_escolar');
        $this->vinculo(2, 1, 6, 'professor');
        $this->vinculo(3, 2, 5, 'professor');
        DB::table('vinculo_turmas')->insert(['vinculo_id' => 2, 'espaco_id' => 6, 'turma_id' => 2]);
    }

    private function vinculo(int $id, int $usuario, int $espaco, string $perfil): void
    {
        DB::table('usuario_vinculos')->insert(['id' => $id, 'usuario_id' => $usuario, 'espaco_id' => $espaco, 'perfil' => $perfil,
            'situacao' => 'ativo', 'inicio_em' => now()->subDay(), 'versao_autorizacao' => 1]);
    }


    private function recusa(callable $acao, int $status = 403): void
    {
        try { $acao(); $this->fail('Operação deveria ser recusada.'); }
        catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) { $this->assertSame($status, $e->getStatusCode()); }
    }

    private function manifesto(): array
    {
        $m = app(ConciliarVinculos::class)->preparar();
        foreach ($m['usuarios'] as &$u) $u['acao'] = 'preservar';
        return $m;
    }

    public function test_conciliacao_somente_leitura_exige_decisao_de_todas_as_contas(): void
    {
        $s = app(ConciliarVinculos::class);
        $m = $s->preparar();
        $this->assertFalse($s->conferir($m)['conciliacao_aprovavel']);
        $r = $s->conferir($this->manifesto());
        $this->assertTrue($r['conciliacao_aprovavel']);
        $this->assertFalse($r['publicacao_autorizada']);
        $this->assertFalse(config('vinculos.obrigatorio'));
        $this->assertDatabaseCount('usuario_vinculos', 3);
        $this->assertDatabaseCount('vinculo_eventos', 0);
        array_pop($m['usuarios']);
        $this->expectException(\RuntimeException::class);
        $s->conferir($m);
    }

    public function test_origem_alterada_invalida_manifesto_sem_sincronizacao_automatica(): void
    {
        $m = $this->manifesto();
        DB::table('usuario_escopos')->where('usuario_id', 1)->delete();
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Origem ou vínculos alterados');
        app(ConciliarVinculos::class)->conferir($m);
    }

    public function test_revogacao_posterior_nao_e_sobrescrita_e_bloqueio_precisa_revisao(): void
    {
        $m = $this->manifesto();
        DB::table('usuario_vinculos')->where('id', 3)->update(['situacao' => 'suspenso']);
        try { app(ConciliarVinculos::class)->conferir($m); $this->fail('Manifesto deveria ser recusado.'); }
        catch (\RuntimeException $e) { $this->assertStringContainsString('alterados', $e->getMessage()); }
        $novo = $this->manifesto();
        $this->assertFalse(app(ConciliarVinculos::class)->conferir($novo)['conciliacao_aprovavel']);
        $novo['usuarios'][1]['acao'] = 'bloqueado';
        $r = app(ConciliarVinculos::class)->conferir($novo);
        $this->assertTrue($r['conciliacao_aprovavel']);
        $this->assertSame([2], $r['contas_bloqueadas_revisadas']);
        $this->assertDatabaseHas('usuario_vinculos', ['id' => 3, 'situacao' => 'suspenso']);
    }

    public function test_conta_adiada_sem_destino_e_convite_legado_impedem_conclusao(): void
    {
        Usuario::create(['nome' => 'Sem destino', 'email' => 'sem@example.test', 'cpf' => '00000000090', 'perfil' => 'professor', 'ativo' => true, 'password' => 'ficticio']);
        $m = $this->manifesto();
        $r = app(ConciliarVinculos::class)->conferir($m);
        $this->assertFalse($r['conciliacao_aprovavel']);
        $m['usuarios'][2]['acao'] = 'bloqueado';
        $this->assertTrue(app(ConciliarVinculos::class)->conferir($m)['conciliacao_aprovavel']);
        DB::table('password_reset_tokens')->insert(['email' => 'hash-ficticio', 'token' => 'hash-token', 'expira_em' => now()->addDay()]);
        $this->assertSame(1, app(ConciliarVinculos::class)->conferir($m)['convites_legados_pendentes']);
    }

    public function test_login_e_identidade_nao_restauram_permissoes_sem_contexto(): void
    {
        config(['vinculos.obrigatorio' => true]);
        Usuario::find(1)->update(['password' => 'senha-ficticia']);
        $this->postJson('/api/v1/auth/login', ['email' => '1@example.test', 'password' => 'senha-ficticia'])
            ->assertOk()->assertJsonPath('data.perfil', null)->assertJsonPath('data.permissoes', [])->assertJsonPath('data.selecionar_vinculo', true);
        Sanctum::actingAs(Usuario::find(1));
        $this->getJson('/api/v1/auth/me')->assertOk()->assertJsonPath('data.perfil', null);
        $this->getJson('/api/v1/escolas')->assertForbidden();
        $this->getJson('/api/v1/escolas?vinculo_id=1')->assertForbidden();
        $this->recusa(fn () => Permissao::efetivas(Usuario::find(1)));
        $this->recusa(fn () => \App\Support\Escopo::escolaIds(Usuario::find(1)));
        Sanctum::actingAs(Usuario::find(1)->noVinculo(1));
        $this->getJson('/api/v1/escolas')->assertOk();
        $this->getJson('/api/v1/escolas/2')->assertNotFound();
        DB::table('usuario_vinculos')->where('id', 1)->update(['situacao' => 'suspenso']);
        $this->getJson('/api/v1/escolas')->assertForbidden();
    }

    public function test_importacao_exige_vinculo_no_corte_e_nega_destino_de_outro_papel(): void
    {
        config(['vinculos.obrigatorio' => true]);
        $this->recusa(fn () => \App\Support\ContextoImportacao::resolver(1, 1, 5, ['escola.editar']));
        $meta = \App\Support\ContextoImportacao::resolver(1, 1, 5, ['escola.editar'], 1);
        $this->assertSame(5, $meta['espaco_id']);
        $this->recusa(fn () => \App\Support\ContextoImportacao::resolver(2, 1, 6, ['escola.editar'], 1), 404);
        config(['espacos.ativo' => false]);
        $this->recusa(fn () => \App\Support\ContextoImportacao::resolver(1, 1, 5, ['escola.editar'], 1));
    }

    public function test_aceitar_novo_vinculo_preserva_senha_perfil_e_outros_papeis(): void
    {
        $this->vinculo(4, 2, 6, 'professor');
        $senha = Usuario::find(2)->password;
        $c = app(ConviteVinculo::class)->emitir(Usuario::find(1)->noVinculo(1), '2@example.test', 5, 'coordenador');
        $this->assertNotNull($c);
        $this->assertDatabaseHas('usuario_vinculos', ['id' => $c['vinculo_id'], 'situacao' => 'convidado']);
        Sanctum::actingAs(Usuario::find(2));
        config(['vinculos.obrigatorio' => true]);
        $this->postJson('/api/v1/auth/convites-vinculo/'.$c['convite_id'].'/consultar', ['token' => $c['token']])->assertOk();
        $this->postJson('/api/v1/auth/convites-vinculo/'.$c['convite_id'].'/responder', ['token' => $c['token'], 'aceitar' => true])->assertOk();
        $this->assertSame($senha, Usuario::find(2)->password);
        $this->assertDatabaseHas('usuarios', ['id' => 2, 'perfil' => 'admin_rede', 'ativo' => 1]);
        $this->assertDatabaseHas('usuario_vinculos', ['id' => 4, 'situacao' => 'ativo']);
        $this->assertSame('coordenador', Usuario::find(2)->noVinculo($c['vinculo_id'])->perfil);
        $this->postJson('/api/v1/auth/convites-vinculo/'.$c['convite_id'].'/responder', ['token' => $c['token'], 'aceitar' => false])->assertUnprocessable();
    }

    public function test_recusa_e_reenvio_afetam_so_convite_e_vinculo_correspondente(): void
    {
        $s = app(ConviteVinculo::class); $gestor = Usuario::find(1)->noVinculo(1);
        $a = $s->emitir($gestor, '2@example.test', 5, 'coordenador');
        $b = $s->emitir($gestor, '2@example.test', 5, 'coordenador');
        $this->assertSame($a['vinculo_id'], $b['vinculo_id']);
        $this->recusa(fn () => $s->responder(Usuario::find(2), $a['convite_id'], $a['token'], true), 422);
        $this->recusa(fn () => $s->responder(Usuario::find(1), $b['convite_id'], $b['token'], true), 422);
        $s->responder(Usuario::find(2), $b['convite_id'], $b['token'], false);
        $this->assertDatabaseHas('usuario_vinculos', ['id' => $b['vinculo_id'], 'situacao' => 'encerrado']);
        $this->assertDatabaseHas('usuario_vinculos', ['id' => 3, 'situacao' => 'ativo']);
        $this->assertDatabaseHas('usuarios', ['id' => 2, 'ativo' => 1]);
        $this->assertNull($s->emitir($gestor, '2@example.test', 5, 'coordenador'));
    }

    public function test_emissor_revogado_token_expirado_e_convite_forjado_nao_ativam(): void
    {
        $s = app(ConviteVinculo::class);
        $c = $s->emitir(Usuario::find(1)->noVinculo(1), '2@example.test', 5, 'coordenador');
        $this->recusa(fn () => $s->responder(Usuario::find(2), $c['convite_id'], str_repeat('x', 64), true), 422);
        DB::table('usuario_vinculos')->where('id', 1)->update(['situacao' => 'suspenso']);
        $this->recusa(fn () => $s->responder(Usuario::find(2), $c['convite_id'], $c['token'], true));
        $this->assertDatabaseHas('usuario_vinculos', ['id' => $c['vinculo_id'], 'situacao' => 'convidado']);
        $this->travel(8)->days();
        $this->recusa(fn () => $s->responder(Usuario::find(2), $c['convite_id'], $c['token'], true), 422);
    }

    public function test_endpoint_nao_revela_conta_e_nao_envia_token_ao_gestor(): void
    {
        Mail::fake();
        Sanctum::actingAs(Usuario::find(1)->noVinculo(1));
        $dados = ['email' => '2@example.test', 'espaco_id' => 5, 'perfil' => 'coordenador'];
        $a = $this->postJson('/api/v1/usuarios/convites-vinculo', $dados)->assertStatus(202)->json();
        $b = $this->postJson('/api/v1/usuarios/convites-vinculo', array_replace($dados, ['email' => 'ausente@example.test']))->assertStatus(202)->json();
        $this->assertSame($a, $b);
        $this->assertArrayNotHasKey('token', $a['data']);
        Mail::assertQueued(\App\Mail\EmailModelo::class, fn ($m) => $m->modelo === 'convite-vinculo' && $m->hasTo('2@example.test') && str_contains($m->dados['link'], '#convite='));
        $this->postJson('/api/v1/usuarios/convites-vinculo', array_replace($dados, ['espaco_id' => 6]))->assertNotFound();
    }
    public function test_manifesto_nao_pode_exibir_revisao_diferente_do_hash(): void
    {
        $m = $this->manifesto();
        $m['usuarios'][0]['revisao']['vinculos'] = [];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Revisão da conta está desatualizada');
        app(ConciliarVinculos::class)->conferir($m);
    }

    public function test_corte_nega_cache_legado_e_helpers_mesmo_se_espacos_for_desligado(): void
    {
        $u = Usuario::find(1);
        $this->assertTrue(Permissao::pode($u, 'rede.configurar'));
        config(['vinculos.obrigatorio' => true, 'espacos.ativo' => false]);
        foreach ([
            fn () => Permissao::efetivas($u),
            fn () => \App\Support\Escopo::turmaIds($u),
            fn () => \App\Support\Escopo::provas($u),
            fn () => \App\Support\Instituicoes::redesIds($u),
            fn () => \App\Support\Instituicoes::nucleosIds($u),
            fn () => \App\Support\Espacos::filtrar(DB::table('alunos'), $u, 'espaco_id'),
            fn () => \App\Support\Espacos::agregadoEscolar(DB::table('alunos'), 'alunos', $u),
        ] as $acao) $this->recusa($acao);
    }

    public function test_cli_e_link_assinado_legado_nao_ignoram_corte(): void
    {
        config(['vinculos.obrigatorio' => true]);
        $this->artisan('g360:importar-alunos', ['escola_id' => 1, 'arquivo' => 'inexistente.csv', '--usuario' => 1, '--espaco' => 5])->assertFailed();
        $url = \Illuminate\Support\Facades\URL::temporarySignedRoute('cartoes.arquivo', now()->addMinute(), ['id' => 1, 'tipo' => 'original', 'ator' => 1, 'espaco' => 5], false);
        $this->getJson($url)->assertNotFound();
        $m = app(\App\Services\ConverterVinculosLegados::class)->preparar();
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('indisponível após o corte');
        app(\App\Services\ConverterVinculosLegados::class)->executar($m, true, 1);
    }

    public function test_associacao_de_turma_invalida_nao_enumera_conta_no_convite(): void
    {
        Sanctum::actingAs(Usuario::find(1)->noVinculo(1));
        foreach (['2@example.test', 'ausente@example.test'] as $email) {
            $this->postJson('/api/v1/usuarios/convites-vinculo', ['email' => $email, 'espaco_id' => 5, 'perfil' => 'professor', 'turma_ids' => [2]])->assertNotFound();
        }
        $this->assertDatabaseCount('vinculo_convites', 0);
    }

}
