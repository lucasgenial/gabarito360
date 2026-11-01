<?php

namespace Tests\Feature;

use App\Models\Usuario;
use App\Services\ConverterVinculosLegados;
use App\Services\InventariarVinculosLegados;
use App\Support\Permissao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class ConversaoVinculosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->startOfSecond());
        Permissao::limparCache();
        foreach ([1, 2] as $id) {
            DB::table('clientes')->insert(['id' => $id, 'tipo' => 'rede', 'nome' => 'Cliente fictício', 'modalidade' => 'interno', 'situacao' => 'ativo']);
            DB::table('redes')->insert(['id' => $id, 'nome' => 'Rede fictícia', 'tipo' => 'estadual', 'uf' => 'BA']);
            DB::table('nucleos')->insert(['id' => $id, 'rede_id' => $id, 'nome' => 'Núcleo fictício']);
            DB::table('escolas')->insert(['id' => $id, 'nucleo_id' => $id, 'nome' => 'Escola fictícia', 'inep' => '0000000'.$id, 'tipo_rede' => 'estadual']);
            foreach (['rede' => 0, 'nucleo' => 2, 'escola' => 4] as $tipo => $offset) {
                DB::table('espacos')->insert(['id' => $id + $offset, 'cliente_id' => $id, 'tipo' => $tipo, 'nome' => 'Espaço fictício', 'situacao' => 'ativo', $tipo.'_id' => $id]);
            }
            DB::table('turmas')->insert(['id' => $id, 'escola_id' => $id, 'espaco_id' => $id + 4, 'nome' => '3A', 'serie' => '3', 'turno' => 'tarde', 'ano_letivo' => 2026]);
            DB::table('alunos')->insert(['id' => $id, 'turma_id' => $id, 'espaco_id' => $id + 4, 'nome' => 'Fictício', 'matricula' => 'B-'.$id]);
        }
        foreach ([1 => 'admin_rede', 2 => 'professor'] as $id => $perfil) {
            DB::table('usuarios')->insert(['id' => $id, 'perfil' => $perfil, 'nome' => 'Nome privado', 'email' => $id.'@example.test', 'cpf' => str_pad((string) $id, 11, '0', STR_PAD_LEFT), 'password' => 'segredo', 'ativo' => true]);
            foreach ([1, 2] as $ref) $this->escopo($id, $perfil === 'admin_rede' ? 'rede' : 'turma', $ref);
        }
    }

    private function escopo(int $u, string $tipo, int $id): void
    {
        DB::table('usuario_escopos')->insert(['usuario_id' => $u, 'escopo_tipo' => $tipo, 'escopo_id' => $id]);
    }

    private function servico(): ConverterVinculosLegados
    {
        return app(ConverterVinculosLegados::class);
    }

    /** Manifesto fictício, com decisão explícita de preservar ajustes em cada destino. */
    private function manifesto(): array
    {
        $m = $this->servico()->preparar();
        $pessoas = app(InventariarVinculosLegados::class)->executar()['pessoas'];
        foreach ($m['usuarios'] as $i => &$u) {
            $u['acao'] = 'converter';
            $u['motivo'] = 'conversao_revisada';
            $u['vinculos'] = array_map(fn ($c) => ['espaco_id' => $c['espaco_id'], 'turmas' => $c['turmas'], 'alunos' => $c['alunos'],
                'ajustes' => array_column($pessoas[$i]['ajustes_a_conciliar'], 'id'), 'reducoes_aceitas' => []], $pessoas[$i]['vinculos_candidatos']);
        }
        return $m;
    }

    private function rejeita(array $m, string $mensagem): void
    {
        try { $this->servico()->executar($m, true, 1); $this->fail('Conversão deveria ser recusada.'); }
        catch (RuntimeException $e) { $this->assertStringContainsString($mensagem, $e->getMessage()); }
    }

    private function ajuste(string $efeito, ?string $ate = null): array
    {
        $chave = DB::table('perfil_permissoes')->where('perfil', 'professor')->where('tipo', $efeito === 'conceder' ? 'extra' : 'padrao')->orderBy('permissao')->value('permissao');
        $id = DB::table('usuario_permissoes')->insertGetId(['usuario_id' => 2, 'permissao' => $chave, 'efeito' => $efeito, 'valida_ate' => $ate, 'definida_por' => 1]);
        return [$id, $chave];
    }

    public function test_simulacao_conversao_e_repeticao_preservam_fontes_e_permissoes(): void
    {
        $this->ajuste('conceder');
        $this->ajuste('retirar');
        $m = $this->manifesto();
        $fonte = $this->servico()->fonte();
        $esperadas = [1 => Permissao::efetivas(Usuario::find(1)), 2 => Permissao::efetivas(Usuario::find(2))];
        $sim = $this->servico()->executar($m);
        $this->assertFalse($sim['aplicado']);
        $this->assertDatabaseCount('usuario_vinculos', 0);
        $r = $this->servico()->executar($m, true, 1);
        $this->assertFalse($r['autorizacao_por_vinculo_ativada']);
        foreach ($r['comparacoes'] as $c) {
            $this->assertSame($esperadas[$c['usuario_id']], $c['antes']);
            $this->assertSame($c['antes'], $c['depois']);
        }
        $this->assertSame($r, $this->servico()->executar($m, true, 1));
        $this->assertSame($fonte, $this->servico()->fonte());
        $this->assertDatabaseCount('usuario_vinculos', 4);
        $this->assertDatabaseCount('vinculo_eventos', 4);
        $this->assertDatabaseCount('vinculo_turmas', 2);
        $this->assertDatabaseCount('vinculo_permissoes', 4);
        $this->assertDatabaseHas('vinculo_permissoes', ['definida_por' => 1, 'efeito' => 'retirar']);
        $this->assertDatabaseHas('usuarios', ['id' => 2, 'perfil' => 'professor']);
        foreach (['Nome privado', 'segredo', '@example.test'] as $privado) $this->assertStringNotContainsString($privado, json_encode($r));
    }

    public function test_concessao_pode_ter_destino_unico_com_reducao_explicita_no_outro(): void
    {
        [$extra, $chave] = $this->ajuste('conceder');
        [$retirada] = $this->ajuste('retirar');
        $m = $this->manifesto();
        $m['usuarios'][1]['vinculos'][1]['ajustes'] = [$retirada];
        $this->rejeita($m, 'Redução');
        $this->assertDatabaseCount('usuario_vinculos', 0);
        $m['usuarios'][1]['vinculos'][1]['reducoes_aceitas'] = [$chave];
        $r = $this->servico()->executar($m, true, 1);
        $this->assertSame([$chave], $r['comparacoes'][3]['reducoes_aceitas']);
        $this->assertDatabaseCount('vinculo_permissoes', 3);
        $this->assertSame(1, DB::table('vinculo_permissoes')->where('permissao', $chave)->count());
    }

    public function test_omitir_retirada_em_outro_vinculo_nao_pode_ampliar_poderes(): void
    {
        $this->ajuste('retirar');
        $m = $this->manifesto();
        $m['usuarios'][1]['vinculos'][1]['ajustes'] = [];
        $this->rejeita($m, 'ampliaria');
        $this->assertDatabaseCount('usuario_vinculos', 0);
        $this->assertDatabaseCount('vinculo_eventos', 0);
    }

    public function test_ajuste_vencido_nao_e_perdido_nem_reativado(): void
    {
        [$id, $chave] = $this->ajuste('conceder', '2020-01-01');
        $m = $this->manifesto();
        foreach ($m['usuarios'][1]['vinculos'] as &$v) $v['ajustes'] = [];
        unset($v);
        $this->rejeita($m, 'Todo ajuste');
        $this->assertDatabaseCount('usuario_vinculos', 0);
        $m['usuarios'][1]['vinculos'][0]['ajustes'] = [$id];
        $r = $this->servico()->executar($m, true, 1);
        $this->assertNotContains($chave, $r['comparacoes'][2]['depois']);
        $this->assertDatabaseHas('vinculo_permissoes', ['permissao' => $chave, 'valida_ate' => '2020-01-01']);
    }

    public function test_conta_sem_destino_so_pode_ser_adiada_explicitamente(): void
    {
        DB::table('usuarios')->where('id', 2)->update(['perfil' => 'aluno']);
        DB::table('usuario_escopos')->where('usuario_id', 2)->delete();
        $m = $this->manifesto();
        $this->rejeita($m, 'pendências');
        $m['usuarios'][1]['acao'] = 'adiar';
        $m['usuarios'][1]['motivo'] = 'sem_destino';
        $r = $this->servico()->executar($m, true, 1);
        $this->assertSame([['usuario_id' => 2, 'motivo' => 'sem_destino']], $r['adiados']);
        $this->assertDatabaseCount('usuario_vinculos', 2);
        $this->assertDatabaseCount('usuarios', 2);
    }

    public function test_aluno_com_referencia_explicita_preserva_restricao(): void
    {
        DB::table('usuarios')->where('id', 2)->update(['perfil' => 'aluno']);
        DB::table('usuario_escopos')->where('usuario_id', 2)->delete();
        $this->escopo(2, 'aluno', 1);
        $this->servico()->executar($this->manifesto(), true, 1);
        $this->assertDatabaseHas('vinculo_alunos', ['espaco_id' => 5, 'aluno_id' => 1]);
        $this->assertDatabaseCount('vinculo_alunos', 1);
        $this->assertDatabaseCount('vinculo_turmas', 0);
    }

    public function test_fonte_alterada_bloqueia_manifesto_antigo(): void
    {
        $m = $this->manifesto();
        DB::table('usuarios')->where('id', 2)->update(['ativo' => false]);
        $this->rejeita($m, 'Fontes alteradas');
        $this->assertDatabaseCount('usuario_vinculos', 0);
    }

    public function test_alterar_restricao_no_manifesto_nao_concede_ou_retira_turma(): void
    {
        $m = $this->manifesto();
        $m['usuarios'][1]['vinculos'][0]['turmas'] = [2];
        $this->rejeita($m, 'Restrições');
        $this->assertDatabaseCount('usuario_vinculos', 0);
    }

    public function test_ajuste_alheio_e_destino_duplicado_sao_recusados(): void
    {
        [$id] = $this->ajuste('conceder');
        $m = $this->manifesto();
        $m['usuarios'][0]['vinculos'][0]['ajustes'] = [$id];
        $this->rejeita($m, 'Ajuste inválido');
        $m = $this->manifesto();
        $m['usuarios'][1]['vinculos'][1] = $m['usuarios'][1]['vinculos'][0];
        $this->rejeita($m, 'Destinos');
        $this->assertDatabaseCount('usuario_vinculos', 0);
    }

    public function test_repeticao_nao_reabre_vinculo_suspenso(): void
    {
        $m = $this->manifesto();
        $this->servico()->executar($m, true, 1);
        DB::table('usuario_vinculos')->where('usuario_id', 2)->update(['situacao' => 'suspenso']);
        $this->rejeita($m, 'foi alterado');
        $this->assertSame(2, DB::table('usuario_vinculos')->where('situacao', 'suspenso')->count());
        $this->assertDatabaseCount('vinculo_eventos', 4);
    }

    public function test_repeticao_detecta_alteracao_de_restricao_e_permissao(): void
    {
        $this->ajuste('conceder');
        $m = $this->manifesto();
        $this->servico()->executar($m, true, 1);
        DB::table('vinculo_turmas')->where('turma_id', 1)->delete();
        $this->rejeita($m, 'Restrições convertidas');
        $id = DB::table('usuario_vinculos')->where('usuario_id', 2)->where('espaco_id', 5)->value('id');
        DB::table('vinculo_turmas')->insert(['vinculo_id' => $id, 'espaco_id' => 5, 'turma_id' => 1]);
        DB::table('vinculo_permissoes')->where('vinculo_id', $id)->delete();
        $this->rejeita($m, 'Permissões convertidas');
    }

    public function test_contas_omitidas_e_rascunho_nao_sao_manifesto_aprovado(): void
    {
        $m = $this->manifesto();
        array_pop($m['usuarios']);
        $this->rejeita($m, 'todas as contas');
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->servico()->executar($this->servico()->preparar(), true, 1);
    }

    public function test_aplicacao_exige_executor_e_manutencao_fora_de_testing(): void
    {
        $m = $this->manifesto();
        try { $this->servico()->executar($m, true, 2); $this->fail(); }
        catch (RuntimeException $e) { $this->assertStringContainsString('executor', $e->getMessage()); }
        $this->app->instance('env', 'production');
        try { $this->rejeita($m, 'manutenção'); }
        finally { $this->app->instance('env', 'testing'); }
        $this->assertDatabaseCount('usuario_vinculos', 0);
    }

    public function test_cli_simula_por_padrao_e_exige_hash_exato_para_aplicar(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'v1b-');
        try {
            file_put_contents($path, json_encode($this->manifesto(), JSON_THROW_ON_ERROR));
            $this->artisan('g360:converter-vinculos', ['manifesto' => $path])->assertSuccessful();
            $this->assertDatabaseCount('usuario_vinculos', 0);
            $this->artisan('g360:converter-vinculos', ['manifesto' => $path, '--aplicar' => true, '--ator' => '1', '--sha256' => str_repeat('0', 64)])->assertFailed();
            $this->assertDatabaseCount('usuario_vinculos', 0);
            $this->artisan('g360:converter-vinculos', ['manifesto' => $path, '--aplicar' => true, '--ator' => '1', '--sha256' => hash_file('sha256', $path)])->assertSuccessful();
            $this->assertDatabaseCount('usuario_vinculos', 4);
        } finally { unlink($path); }
    }

    public static function perfis(): array
    {
        return [
            ['admin_rede', 'rede'], ['dir_nucleo', 'nucleo'], ['dir_escolar', 'escola'],
            ['coordenador', 'escola'], ['professor', 'turma'], ['aplicador', 'escola'], ['aluno', 'aluno'],
        ];
    }

    #[DataProvider('perfis')]
    public function test_comparacao_com_motor_atual_para_cada_perfil(string $perfil, string $tipo): void
    {
        DB::table('usuarios')->where('id', 2)->update(['perfil' => $perfil]);
        DB::table('usuario_escopos')->where('usuario_id', 2)->delete();
        foreach ([1, 2] as $id) $this->escopo(2, $tipo, $id);
        $antes = Permissao::efetivas(Usuario::find(2));
        $r = $this->servico()->executar($this->manifesto(), true, 1);
        foreach (array_filter($r['comparacoes'], fn ($c) => $c['usuario_id'] === 2) as $c) {
            $this->assertSame($antes, $c['depois']);
            $this->assertDatabaseHas('usuario_vinculos', ['id' => $c['vinculo_id'], 'perfil' => $perfil]);
        }
        Permissao::limparCache();
        $this->assertSame($antes, Permissao::efetivas(Usuario::find(2)));
    }
}
