<?php

namespace Tests\Feature;

use App\Models\Usuario;
use App\Services\CadastroCliente;
use App\Support\{ContextoInterno, Escopo, Permissao};
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Gate};
use Laravel\Sanctum\Sanctum;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class AcessosInternosTest extends TestCase
{
    use RefreshDatabase;
    private Usuario $u;

    protected function setUp(): void
    {
        parent::setUp();
        config(['acessos_internos.ativo'=>true, 'espacos.ativo'=>true, 'espacos.pessoal'=>true, 'vinculos.obrigatorio'=>true]);
        Permissao::limparCache();
        foreach ([1,2] as $id) Usuario::forceCreate(['id'=>$id, 'perfil'=>'admin_rede', 'nome'=>'Operador fictício', 'email'=>"a1-$id@example.test", 'cpf'=>str_pad((string)$id, 11, '0', STR_PAD_LEFT), 'password'=>'ficticio', 'ativo'=>true]);
        $this->u = Usuario::find(1);
        foreach (ContextoInterno::PERFIS as $i=>$perfil) DB::table('usuario_acessos_internos')->insert(['id'=>$i+1,'usuario_id'=>1,'perfil'=>$perfil,'situacao'=>'ativo','inicio_em'=>now()->subDay(),'versao_autorizacao'=>1]);
        DB::table('usuario_acessos_internos')->insert(['id'=>4,'usuario_id'=>2,'perfil'=>'financeiro','situacao'=>'ativo','inicio_em'=>now()->subDay(),'versao_autorizacao'=>1]);
    }

    private function recusado(callable $acao): void
    {
        try { $acao(); $this->fail('Acesso deveria ser recusado.'); }
        catch (HttpException $e) { $this->assertSame(403, $e->getStatusCode()); }
    }

    public function test_politica_comercial_exige_selecao_e_habilitacao_explicitamente(): void
    {
        $this->assertFalse(Gate::forUser($this->u)->allows('gerir-clientes'));
        $this->recusado(fn () => $this->u->noAcessoInterno(4, 1));
        $this->recusado(fn () => $this->u->noAcessoInterno(1, 0));
        $this->recusado(fn () => $this->u->noAcessoInterno(999, 1));
        config(['acessos_internos.ativo'=>false]);
        $this->recusado(fn () => $this->u->noAcessoInterno(1, 1));
        try { app(CadastroCliente::class)->cadastrar($this->u, ['tipo'=>'rede','nome'=>'Fictício','modalidade'=>'teste']); $this->fail('Conta sem contexto aceita.'); }
        catch (AuthorizationException $e) { $this->assertTrue(true); }
    }

    public function test_tres_perfis_gerem_cadastro_sem_permissao_ou_acesso_pedagogico(): void
    {
        foreach (ContextoInterno::PERFIS as $i=>$perfil) {
            $ator = $this->u->noAcessoInterno($i+1, 1);
            $this->assertSame($perfil, $ator->perfil);
            $this->assertSame('admin_rede', $this->u->perfil);
            $this->assertTrue(Gate::forUser($ator)->allows('gerir-clientes'));
            $c = app(CadastroCliente::class)->cadastrar($ator, ['tipo'=>'escola','nome'=>'Contratante fictício','modalidade'=>'teste']);
            $this->assertSame(1, $c->criado_por);
            app(CadastroCliente::class)->atualizar($ator, $c->id, ['modalidade'=>'interno']);
            foreach (['aluno.ver','prova.ver','resultado.ver','relatorio.rede','usuario.gerir'] as $p) $this->assertFalse(Permissao::pode($ator, $p));
            $this->recusado(fn () => Escopo::escolaIds($ator));
            $this->recusado(fn () => Escopo::provas($ator));
            Sanctum::actingAs($ator);
            foreach (['provas','alunos','turmas','cartoes/1','relatorios/prova/1'] as $rota) $this->getJson('/api/v1/'.$rota)->assertForbidden();
        }
        $this->assertDatabaseCount('clientes', 3);
        $this->assertDatabaseCount('cliente_eventos', 6);
        $this->assertDatabaseCount('espacos', 0);
    }

    public function test_contexto_interno_nao_herda_admin_mesmo_com_regime_legado(): void
    {
        config(['espacos.ativo'=>false, 'vinculos.obrigatorio'=>false]);
        $ator = $this->u->noAcessoInterno(3, 1);
        $this->assertSame(['cliente.ver','cliente.gerir'], Permissao::efetivas($ator));
        $this->recusado(fn () => Escopo::escolaIds($ator));
        $this->assertFalse(Gate::forUser($this->u)->allows('gerir-clientes'));
    }

    public function test_suspensao_vigencia_conta_e_versao_revalidadas_sem_cache(): void
    {
        $ator = $this->u->noAcessoInterno(3, 1);
        $this->assertTrue(ContextoInterno::pode($ator, 'cliente.ver'));
        foreach ([['situacao'=>'suspenso'], ['fim_em'=>now()], ['inicio_em'=>now()->addDay()], ['versao_autorizacao'=>2], ['perfil'=>'invalido']] as $alteracao) {
            DB::table('usuario_acessos_internos')->where('id',3)->update($alteracao);
            $this->recusado(fn () => ContextoInterno::permissoes($ator));
            DB::table('usuario_acessos_internos')->where('id',3)->update(['situacao'=>'ativo','fim_em'=>null,'inicio_em'=>now()->subDay(),'versao_autorizacao'=>1,'perfil'=>'financeiro']);
        }
        DB::table('usuarios')->where('id',1)->update(['ativo'=>false]);
        $this->recusado(fn () => Gate::forUser($ator)->authorize('gerir-clientes'));
        DB::table('usuarios')->where('id',1)->update(['ativo'=>true]);
        config(['acessos_internos.ativo'=>false]);
        $this->recusado(fn () => Permissao::efetivas($ator));
    }

    public function test_troca_contexto_exclusiva_preserva_identidade_e_revalida_fresh(): void
    {
        DB::table('clientes')->insert(['id'=>1,'tipo'=>'professor','nome'=>'Fictício','modalidade'=>'interno','situacao'=>'ativo']);
        DB::table('espacos')->insert(['id'=>1,'cliente_id'=>1,'tipo'=>'pessoal','nome'=>'Pessoal','situacao'=>'ativo','usuario_proprietario_id'=>1]);
        DB::table('usuario_vinculos')->insert(['id'=>1,'usuario_id'=>1,'espaco_id'=>1,'perfil'=>'professor_autonomo','situacao'=>'ativo','inicio_em'=>now()->subDay()]);
        $pessoal = $this->u->noVinculo(1);
        $interno = $pessoal->noAcessoInterno(3, 1);
        $this->assertNull($interno->vinculoContextoId());
        $this->assertSame('professor_autonomo', $pessoal->perfil);
        $this->assertSame('financeiro', $interno->fresh()->perfil);
        $volta = $interno->noVinculo(1);
        $this->assertNull($volta->acessoInternoContextoId());
        $this->assertNull($volta->acessoInternoContextoVersao());
        $this->assertFalse(Gate::forUser($volta)->allows('gerir-clientes'));
        $this->assertSame('admin_rede', Usuario::find(1)->perfil);
        DB::table('usuario_acessos_internos')->where('id',3)->update(['versao_autorizacao'=>2]);
        $this->recusado(fn () => $interno->fresh());
    }

    public function test_hierarquia_nao_concede_desenvolvedor_ou_poder_de_gestao_a_inferiores(): void
    {
        $dev = $this->u->noAcessoInterno(1,1);
        $this->assertTrue(ContextoInterno::podeGerirPerfil($dev, 'suporte'));
        $this->assertTrue(ContextoInterno::podeGerirPerfil($dev, 'financeiro'));
        foreach (['desenvolvedor','admin_rede','professor','inexistente'] as $perfil) $this->assertFalse(ContextoInterno::podeGerirPerfil($dev, $perfil));
        foreach ([2,3] as $id) foreach (ContextoInterno::PERFIS as $perfil) $this->assertFalse(ContextoInterno::podeGerirPerfil($this->u->noAcessoInterno($id,1), $perfil));
    }

    public function test_perfil_global_nao_e_atribuicao_e_job_nao_perde_contexto(): void
    {
        config(['vinculos.obrigatorio'=>false]);
        foreach (ContextoInterno::PERFIS as $perfil) {
            // Simula atributo adulterado em memória; o ENUM legado do MariaDB
            // também impede persistir esses perfis na identidade global.
            $this->u->forceFill(['perfil'=>$perfil]);
            $this->assertSame([], Permissao::efetivas($this->u));
            $this->assertFalse(Gate::forUser($this->u)->allows('gerir-clientes'));
        }
        $interno = $this->u->noAcessoInterno(3,1);
        $this->expectException(\LogicException::class);
        $interno->getQueueableId();
    }

    public function test_migration_retomada_nao_promove_contas(): void
    {
        $migration = require database_path('migrations/2026_11_01_000008_criar_acessos_internos.php');
        $migration->up();
        $this->assertDatabaseCount('usuario_acessos_internos', 4);
        $this->assertDatabaseHas('usuarios', ['id'=>1,'perfil'=>'admin_rede']);
    }

    public function test_edicao_revalida_apos_obter_cliente_na_transacao(): void
    {
        $ator = $this->u->noAcessoInterno(3,1);
        $servico = app(CadastroCliente::class);
        $c = $servico->cadastrar($ator, ['tipo'=>'rede','nome'=>'Fictício','modalidade'=>'teste']);
        DB::listen(function (\Illuminate\Database\Events\QueryExecuted $q) {
            if (str_starts_with(strtolower($q->sql), 'select') && str_contains($q->sql, 'clientes')) config(['acessos_internos.ativo'=>false]);
        });
        $this->recusado(fn () => $servico->atualizar($ator, $c->id, ['modalidade'=>'pagante']));
        $this->assertSame('teste', $c->fresh()->modalidade);
        $this->assertDatabaseCount('cliente_eventos', 1);
    }
}
