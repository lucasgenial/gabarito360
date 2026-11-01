<?php

namespace Tests\Feature;

use App\Models\Usuario;
use App\Services\AuditarIntegridadeEspacos;
use App\Services\PreencherEspacosLegados;
use App\Services\RestringirEspacos;
use App\Support\EstruturaEspacos;
use App\Support\Permissao;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** DDL real: execução exclusiva em banco MariaDB descartável (migrate:fresh). */
class IntegridadeEspacosMariaDbTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (!in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) $this->markTestSkipped('Ensaio de DDL exige MariaDB descartável.');
        if (!str_ends_with(DB::connection()->getDatabaseName(), '_test')) throw new \RuntimeException('Use banco descartável terminado em _test.');
        $this->artisan('migrate:fresh', ['--force' => true])->assertSuccessful();
        config(['espacos.ativo' => true]);
        Permissao::limparCache();
        foreach (EstruturaEspacos::MANIFESTO as $rede => $nucleos) {
            DB::table('redes')->insert(['id' => $rede, 'nome' => 'Rede fictícia '.$rede, 'tipo' => 'estadual', 'uf' => 'BA']);
            foreach ($nucleos as $nucleo => $escolas) {
                DB::table('nucleos')->insert(['id' => $nucleo, 'rede_id' => $rede, 'nome' => 'Núcleo fictício']);
                foreach ($escolas as $escola) {
                    DB::table('escolas')->insert(['id' => $escola, 'nucleo_id' => $nucleo, 'nome' => 'Escola fictícia', 'inep' => str_pad((string) $escola, 8, '0', STR_PAD_LEFT), 'tipo_rede' => 'estadual']);
                }
            }
        }
        DB::table('usuarios')->insert(['id' => 1, 'nome' => 'Autor fictício', 'perfil' => 'admin_rede', 'email' => 't2@example.test', 'cpf' => '00000000001', 'password' => 'ficticio']);
        DB::table('turmas')->insert(['id' => 1, 'escola_id' => 8, 'nome' => '3A', 'serie' => '3', 'turno' => 'tarde', 'ano_letivo' => 2026]);
        DB::table('alunos')->insert(['id' => 1, 'turma_id' => 1, 'nome' => 'Aluno fictício', 'matricula' => 'T2-1', 'avatar_id' => 36]);
        DB::table('provas')->insert(['id' => 1, 'escola_id' => 8, 'criado_por' => 1, 'titulo' => 'Prova fictícia', 'disciplina' => 'Teste', 'serie' => '3', 'num_questoes' => 1, 'num_alternativas' => 4]);
        DB::table('prova_turmas')->insert(['prova_id' => 1, 'turma_id' => 1]);
        DB::table('gabaritos')->insert(['id' => 1, 'prova_id' => 1]);
        for ($i = 1; $i <= 1; $i++) {
            DB::table('gabarito_questoes')->insert(['gabarito_id' => 1, 'numero_questao' => $i, 'alternativa' => 'A', 'peso' => 1]);
        }
        DB::table('cartoes')->insert(['id' => 1, 'prova_id' => 1, 'aluno_id' => 1, 'imagem_url' => 'cartoes/ficticio.jpg']);
        DB::table('notas')->insert(['cartao_id' => 1, 'prova_id' => 1, 'aluno_id' => 1, 'turma_id' => 1, 'acertos' => 1, 'total_questoes' => 1, 'nota_final' => 10, 'status_aprovacao' => 'aprovado']);
        app(PreencherEspacosLegados::class)->executar(true);
        foreach ([1, 2] as $rede) DB::table('usuario_escopos')->insert(['usuario_id' => 1, 'escopo_tipo' => 'rede', 'escopo_id' => $rede]);
    }

    private function espaco(int $escola): int
    {
        return (int) DB::table('espacos')->where('escola_id', $escola)->value('id');
    }

    private function recusa(callable $acao): void
    {
        try { $acao(); $this->fail('O banco deveria recusar a escrita inválida.'); }
        catch (\Illuminate\Database\QueryException $e) { $this->assertTrue(in_array((string) $e->getCode(), ['23000', 'HY000'], true)); }
    }

    public function test_auditoria_detecta_cobertura_autoria_referencias_e_relacoes_sem_pii(): void
    {
        $a = app(AuditarIntegridadeEspacos::class)->executar();
        $this->assertTrue($a['apto_dados']);
        DB::table('alunos')->where('id', 1)->update(['espaco_id' => $this->espaco(1), 'origem_autoria' => 'registrada']);
        DB::table('espacos')->where('escola_id', 8)->update(['usuario_proprietario_id' => 1]);
        DB::table('gabarito_questoes')->update(['espaco_id' => null]);
        $a = app(AuditarIntegridadeEspacos::class)->executar();
        $this->assertFalse($a['apto_dados']);
        foreach (['alunos_turma_id', 'alunos_autoria', 'espacos_referencia', 'gabarito_questoes_espaco_nulo'] as $problema) $this->assertGreaterThan(0, $a['inconsistencias'][$problema]);
        $saida = json_encode($a);
        foreach (['Aluno fictício', 'T2-1', '00000000001', 'ficticio.jpg'] as $dado) $this->assertStringNotContainsString($dado, $saida);
        $this->artisan('g360:auditar-espacos')->assertFailed();
        $this->assertTrue(collect(Schema::getColumns('alunos'))->firstWhere('name', 'espaco_id')['nullable']);
    }

    public function test_corte_real_repete_e_rejeita_nulos_orfaos_cruzamentos_e_destinos_multiplos(): void
    {
        $s = app(RestringirEspacos::class);
        $sim = $s->executar();
        $this->assertFalse($sim['aplicado']);
        $this->assertNotEmpty($sim['passos_pendentes_antes']);
        $antes = DB::table('alunos')->get()->toJson();
        $r = $s->executar(true);
        $this->assertTrue($r['esquema_concluido']);
        $this->assertNotEmpty($r['tempos_ms']);
        $this->assertSame([], $s->executar(true)['passos_pendentes_antes']);
        $this->assertSame($antes, DB::table('alunos')->get()->toJson());
        $this->recusa(fn () => DB::table('alunos')->where('id', 1)->update(['espaco_id' => null]));
        $this->recusa(fn () => DB::table('alunos')->where('id', 1)->update(['espaco_id' => $this->espaco(1)]));
        $this->recusa(fn () => DB::table('alunos')->where('id', 1)->update(['criado_por' => 999, 'origem_autoria' => 'registrada']));
        $this->recusa(fn () => DB::table('alunos')->where('id', 1)->update(['origem_autoria' => 'registrada']));
        $this->recusa(fn () => DB::table('espacos')->where('escola_id', 8)->update(['rede_id' => 1]));
        $this->recusa(fn () => DB::table('espacos')->where('escola_id', 8)->delete());
        $this->recusa(fn () => DB::table('usuarios')->where('id', 1)->delete());
        $meta = ['espaco_id' => $this->espaco(8), 'origem_autoria' => 'legado_desconhecido'];
        DB::table('alunos')->insert(['id' => 9, 'turma_id' => 1, 'nome' => 'Outro fictício', 'matricula' => 'OUTRO'] + $meta);
        DB::table('notas')->update(['aluno_id' => 9]);
        $this->assertFalse(app(AuditarIntegridadeEspacos::class)->executar()['apto_dados']);
        DB::table('notas')->update(['aluno_id' => 1]);
        $this->assertTrue(app(AuditarIntegridadeEspacos::class)->executar()['apto_dados']);
        // Evidência de duração total fictícia, sem conteúdo de registro.
        fwrite(STDERR, 'T2-E DDL ms: '.array_sum($r['tempos_ms']).PHP_EOL);
    }

    public function test_unicidade_por_espaco_na_api_csv_e_recibos_preserva_origem(): void
    {
        app(RestringirEspacos::class)->executar(true);
        $admin = Usuario::findOrFail(1); Sanctum::actingAs($admin);
        $meta = ['espaco_id' => $this->espaco(1), 'criado_por' => 1, 'origem_autoria' => 'registrada'];
        DB::table('turmas')->insert(['id' => 2, 'escola_id' => 1, 'nome' => '3A', 'serie' => '3', 'turno' => 'tarde', 'ano_letivo' => 2026] + $meta);
        $dados = ['nome' => 'Novo fictício', 'matricula' => 'REPETIDA', 'cpf' => '12345678901', 'data_nascimento' => '2010-01-01'];
        $a = $this->postJson('/api/v1/alunos', $dados + ['turma_id' => 1])->assertCreated()->json('data.id');
        $b = $this->postJson('/api/v1/alunos', $dados + ['turma_id' => 2])->assertCreated()->json('data.id');
        $this->assertNotEquals($a, $b);
        $this->postJson('/api/v1/alunos', $dados + ['turma_id' => 2])->assertUnprocessable();
        $this->putJson('/api/v1/alunos/'.$b, ['nome' => 'Nome atualizado', 'cpf' => $dados['cpf']])->assertOk();
        $this->recusa(fn () => DB::table('alunos')->insert(['turma_id' => 2, 'nome' => 'Duplicado', 'matricula' => 'REPETIDA'] + $meta));
        Storage::fake('local'); Storage::disk('local')->put('ensaio.csv', "CLASS;NAME;MATRICULA\n3A;Importado;T2-1\n");
        $args = ['escola_id' => 1, 'arquivo' => Storage::disk('local')->path('ensaio.csv'), '--usuario' => 1, '--espaco' => $this->espaco(1), '--ano' => 2026];
        $this->artisan('g360:importar-alunos', $args)->assertSuccessful();
        $this->assertSame(1, DB::table('alunos')->where('matricula', 'T2-1')->count());
        $this->artisan('g360:importar-alunos', $args + ['--aplicar' => true])->assertSuccessful();
        $this->artisan('g360:importar-alunos', $args + ['--aplicar' => true])->assertSuccessful();
        $this->assertSame(2, DB::table('alunos')->where('matricula', 'T2-1')->count());
        $this->assertDatabaseHas('alunos', ['id' => 1, 'turma_id' => 1, 'nome' => 'Aluno fictício']);
        DB::table('provas')->insert(['id' => 2, 'escola_id' => 1, 'titulo' => 'Outra', 'disciplina' => 'Teste', 'serie' => '3', 'num_questoes' => 1, 'num_alternativas' => 4] + $meta);
        foreach ([1 => 8, 2 => 1] as $prova => $escola) DB::table('cartao_envios')->insert(['chave' => '00000000-0000-0000-0000-000000000001', 'usuario_id' => 1, 'prova_id' => $prova, 'espaco_id' => $this->espaco($escola), 'origem_autoria' => 'registrada', 'criado_por' => 1]);
        $this->assertDatabaseCount('cartao_envios', 2);
        $fila = app(\App\Services\Omr\FilaCartoes::class);
        $foto = \Illuminate\Http\UploadedFile::fake()->image('ficticio.jpg');
        $primeiro = $fila->receber($admin, 1, '00000000-0000-0000-0000-000000000001', $foto, null);
        $segundo = $fila->receber($admin, 2, '00000000-0000-0000-0000-000000000001', $foto, null);
        $this->assertNotEquals($primeiro->id, $segundo->id);
        $this->assertSame($this->espaco(1), (int) $segundo->espaco_id);
        $this->recusa(fn () => DB::table('cartao_envios')->where('prova_id', 2)->update(['cartao_id' => 1]));
        $this->assertTrue(app(AuditarIntegridadeEspacos::class)->executar()['apto_dados']);
    }

    public function test_omr_apos_corte_preserva_qr_idempotencia_revisao_substituicao_e_exclusao(): void
    {
        app(RestringirEspacos::class)->executar(true);
        \Illuminate\Support\Facades\Queue::fake([\App\Jobs\ProcessarCartaoJob::class]);
        Storage::fake('public'); Sanctum::actingAs(Usuario::findOrFail(1));
        $meta = ['espaco_id' => $this->espaco(8), 'criado_por' => 1, 'origem_autoria' => 'registrada'];
        DB::table('provas')->where('id', 1)->update(['status' => 'publicada', 'data_aplicacao' => now()->toDateString()]);
        DB::table('provas')->insert(['id' => 2, 'escola_id' => 8, 'titulo' => 'Destino QR', 'disciplina' => 'Outra', 'serie' => '3',
            'num_questoes' => 1, 'num_alternativas' => 4, 'status' => 'publicada', 'data_aplicacao' => now()->toDateString()] + $meta);
        DB::table('prova_turmas')->insert(['prova_id' => 2, 'turma_id' => 1] + $meta);
        DB::table('gabaritos')->insert(['id' => 2, 'prova_id' => 2] + $meta);
        DB::table('gabarito_questoes')->insert(['gabarito_id' => 2, 'numero_questao' => 1, 'alternativa' => 'A'] + $meta);
        $enviar = function (int $prova, string $chave) {
            return $this->postJson('/api/v1/provas/'.$prova.'/cartoes', ['envio_id' => $chave,
                'imagem' => \Illuminate\Http\UploadedFile::fake()->image('ficticio.jpg')]);
        };
        $chave = '00000000-0000-0000-0000-000000000001';
        $enviar(1, $chave)->assertStatus(202); $enviar(1, $chave)->assertStatus(202);
        $enviar(2, $chave)->assertConflict();
        $e = \App\Models\CartaoEnvio::where('chave', $chave)->firstOrFail();
        $processar = function ($envio) {
            $d = \Mockery::mock(\App\Services\Omr\OmrDriverInterface::class.','.\App\Services\Omr\OmrIdentificacaoInterface::class);
            $d->shouldReceive('processar')->andReturn([['numero_questao' => 1, 'alternativa' => 'A', 'confianca' => 1,
                'ambigua' => false, 'multiplas_marcacoes' => false, 'alternativas_detectadas' => []]]);
            $d->shouldReceive('identificacao')->andReturn(['prova_id' => 2, 'aluno_id' => 1]);
            $imagens = \Mockery::mock(\App\Services\Omr\ImagensCartao::class);
            $imagens->shouldReceive('gerar')->andReturn(true); $imagens->shouldReceive('apagar')->andReturnNull();
            (new \App\Jobs\ProcessarCartaoJob($envio->cartao_id, $envio->id, $envio->tentativa, $envio->espaco_id, 1))->handle($d, $imagens);
        };
        $processar($e);
        $this->assertSame('concluido', $e->fresh()->status);
        $this->assertDatabaseHas('cartoes', ['id' => $e->cartao_id, 'prova_id' => 2]);
        $this->getJson('/api/v1/provas/1/cartoes/envios/'.$chave)->assertOk()->assertJsonPath('data.resultado.prova_id', 2);
        $this->postJson('/api/v1/cartoes/'.$e->cartao_id.'/revisar', ['numero_questao' => 1, 'alternativa' => 'B'])->assertOk();
        $this->assertDatabaseHas('notas', ['cartao_id' => $e->cartao_id, 'nota_final' => 0]);
        $this->assertTrue(app(AuditarIntegridadeEspacos::class)->executar()['apto_dados']);
        $chave2 = '00000000-0000-0000-0000-000000000002'; $enviar(2, $chave2)->assertStatus(202);
        $novo = \App\Models\CartaoEnvio::where('chave', $chave2)->firstOrFail(); $processar($novo);
        $this->assertSame('substituido', $e->fresh()->status); $this->assertNull($e->fresh()->cartao_id);
        $this->assertSame('concluido', $novo->fresh()->status);
        $this->deleteJson('/api/v1/cartoes/'.$novo->cartao_id)->assertOk();
        $this->assertNull($novo->fresh()->cartao_id);
        $this->assertSame('recusado', $novo->fresh()->status);
        $this->assertTrue(app(AuditarIntegridadeEspacos::class)->executar()['apto_dados']);
    }

    public function test_falha_entre_passos_retomavel_sem_rollback_de_ddl(): void
    {
        $s = app(RestringirEspacos::class); $antes = $s->executar()['passos_pendentes_antes'];
        try {
            $s->executar(true, function ($p) { if ($p['estado'] === 'concluido' && $p['passo'] === 't2_alunos_matricula') throw new \RuntimeException('Interrupção fictícia'); });
            $this->fail('Deveria interromper o ensaio');
        } catch (\RuntimeException $e) { $this->assertSame('Interrupção fictícia', $e->getMessage()); }
        $depois = $s->executar()['passos_pendentes_antes'];
        $this->assertLessThan(count($antes), count($depois));
        $this->assertNotContains('t2_alunos_matricula', $depois);
        $this->assertTrue($s->executar(true)['esquema_concluido']);
        $this->assertDatabaseCount('alunos', 1);
    }

    public function test_guarda_operacional_e_esquema_divergente_bloqueiam_antes_do_ddl(): void
    {
        $this->artisan('g360:restringir-espacos --aplicar')->assertFailed();
        config(['espacos.ativo' => false]);
        try { app(RestringirEspacos::class)->executar(true); $this->fail('Adaptador desligado deveria bloquear'); }
        catch (\RuntimeException $e) { $this->assertStringContainsString('adaptador', $e->getMessage()); }
        config(['espacos.ativo' => true]); $this->app['env'] = 'production';
        try { app(RestringirEspacos::class)->executar(true); $this->fail('Manutenção ausente deveria bloquear'); }
        catch (\RuntimeException $e) { $this->assertStringContainsString('manutenção', $e->getMessage()); }
        finally { $this->app['env'] = 'testing'; }
        Schema::table('alunos', fn ($t) => $t->index('matricula', 't2_alunos_matricula'));
        try { app(RestringirEspacos::class)->executar(true); $this->fail('Índice divergente deveria bloquear'); }
        catch (\RuntimeException $e) { $this->assertStringContainsString('Índice divergente', $e->getMessage()); }
        $this->assertTrue(collect(Schema::getColumns('alunos'))->firstWhere('name', 'espaco_id')['nullable']);
    }
}
