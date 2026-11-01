<?php

namespace Tests\Feature;

use App\Models\Usuario;
use App\Services\ConviteVinculo;
use App\Support\{Cadastro, CatalogoPermissoes, ContextoVinculo, Escopo, PerfisEspaco, Permissao};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class PerfisEspacoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['espacos.ativo' => true, 'vinculos.obrigatorio' => true]);
        Permissao::limparCache();
        Usuario::forceCreate(['id'=>1, 'perfil'=>'admin_rede', 'nome'=>'Pessoa fictícia', 'email'=>'t3@example.test',
            'cpf'=>'00000000001', 'password'=>'ficticio', 'ativo'=>true]);
        DB::table('clientes')->insert(['id'=>1, 'tipo'=>'rede', 'nome'=>'Cliente', 'modalidade'=>'interno', 'situacao'=>'ativo']);
        DB::table('redes')->insert(['id'=>1, 'nome'=>'Rede', 'tipo'=>'estadual', 'uf'=>'BA']);
        DB::table('nucleos')->insert(['id'=>1, 'rede_id'=>1, 'nome'=>'Núcleo']);
        DB::table('escolas')->insert(['id'=>1, 'nucleo_id'=>1, 'nome'=>'Escola', 'inep'=>'00000001', 'tipo_rede'=>'estadual']);
        foreach (['rede'=>1, 'nucleo'=>2, 'escola'=>3, 'pessoal'=>4] as $tipo=>$id) {
            $ref = $tipo === 'pessoal' ? 'usuario_proprietario_id' : $tipo.'_id';
            DB::table('espacos')->insert(['id'=>$id, 'cliente_id'=>1, 'tipo'=>$tipo, 'nome'=>$tipo, 'situacao'=>'ativo', $ref=>1]);
        }
        DB::table('turmas')->insert(['id'=>1, 'escola_id'=>1, 'espaco_id'=>3, 'nome'=>'3A', 'serie'=>'3', 'turno'=>'tarde', 'ano_letivo'=>2026]);
        $this->vinculo(1, 1, 'admin_rede');
        $this->vinculo(2, 2, 'dir_nucleo');
        $this->vinculo(3, 3, 'professor');
        // O enum atual ainda não admite autônomo; nem um perfil escolar abre espaço pessoal.
        $this->vinculo(4, 4, 'professor');
        DB::table('vinculo_turmas')->insert(['vinculo_id'=>3, 'espaco_id'=>3, 'turma_id'=>1]);
    }

    private function vinculo(int $id, int $espaco, string $perfil): void
    {
        DB::table('usuario_vinculos')->insert(['id'=>$id, 'usuario_id'=>1, 'espaco_id'=>$espaco, 'perfil'=>$perfil,
            'situacao'=>'ativo', 'inicio_em'=>now()->subDay(), 'versao_autorizacao'=>1]);
    }

    private function negado(callable $acao): void
    {
        try { $acao(); $this->fail('Contexto deveria ser recusado.'); }
        catch (HttpException $e) { $this->assertSame(403, $e->getStatusCode()); }
    }

    public function test_mesma_conta_separa_poderes_de_rede_nucleo_e_escola(): void
    {
        $conta = Usuario::find(1);
        $rede = $conta->noVinculo(1); $nucleo = $conta->noVinculo(2); $professor = $conta->noVinculo(3);
        $this->assertTrue(Permissao::pode($rede, 'rede.configurar'));
        $this->assertTrue(Permissao::pode($nucleo, 'escola.criar'));
        $this->assertFalse(Permissao::pode($nucleo, 'prova.criar'));
        $this->assertTrue(Permissao::pode($professor, 'prova.criar'));
        $this->assertFalse(Permissao::pode($professor, 'escola.criar'));
        $this->assertFalse(Permissao::pode($professor, 'turma.gerir'));
        $this->assertSame([1], Escopo::turmaIds($professor));
        $this->assertSame('admin_rede', $conta->perfil);
    }

    public function test_matriz_recusa_todos_os_cruzamentos_de_tipo_e_perfil(): void
    {
        foreach (PerfisEspaco::POR_TIPO as $tipo=>$perfis) {
            foreach ($perfis as $perfil) {
                foreach (['rede'=>1, 'nucleo'=>2, 'escola'=>3, 'pessoal'=>4] as $destino=>$espaco) {
                    $e = DB::table('espacos')->find($espaco);
                    $this->assertSame($tipo === $destino, PerfisEspaco::compativel($perfil, $e, 1));
                }
            }
        }
        $this->assertFalse(PerfisEspaco::compativel('professor_autonomo', DB::table('espacos')->find(4), 2));
        $this->assertFalse(PerfisEspaco::compativel('perfil_desconhecido', DB::table('espacos')->find(3), 1));
        $this->assertSame(Cadastro::DE_ESCOLA, PerfisEspaco::POR_TIPO['escola']);
        foreach (['rede', 'nucleo', 'pessoal', 'desconhecido'] as $tipo) $this->assertFalse(PerfisEspaco::admitePortalPais($tipo));
        $this->assertTrue(PerfisEspaco::admitePortalPais('escola'));
    }

    public function test_professor_nao_pode_ser_transformado_em_perfil_de_nucleo(): void
    {
        $this->vinculo(5, 2, 'professor');
        $this->negado(fn()=>Usuario::find(1)->noVinculo(5));
    }

    public function test_catalogo_pessoal_nao_libera_contexto_nem_perfil_global_antes_da_integracao(): void
    {
        $politica = CatalogoPermissoes::porPerfil()['professor_autonomo'];
        foreach (['turma.gerir', 'aluno.gerir', 'aluno.desativar', 'prova.criar', 'relatorio.turma'] as $p) {
            $this->assertContains($p, $politica['padrao']);
        }
        foreach (['usuario.gerir', 'turma.vincular_professor', 'escola.editar', 'rede.configurar', 'relatorio.nucleo', 'aluno.importar'] as $p) {
            $this->assertNotContains($p, $politica['padrao']);
        }
        $this->assertSame([], $politica['extra']);
        $this->assertFalse(PerfisEspaco::habilitado('professor_autonomo'));
        $this->assertEmpty(array_diff($politica['padrao'], array_column(CatalogoPermissoes::LISTA, 0)));
        $this->negado(fn()=>Usuario::find(1)->noVinculo(4));
        config(['vinculos.obrigatorio'=>false]);
        $global = Usuario::find(1);
        $this->assertNotEmpty(Permissao::efetivas($global)); // Cache legado previamente preenchido.
        $global->perfil = 'professor_autonomo';
        $this->assertSame([], Permissao::efetivas($global));
    }

    public function test_convites_oferecem_so_perfis_institucionais_compativeis(): void
    {
        $opcoes = app(ConviteVinculo::class)->opcoes(Usuario::find(1)->noVinculo(1));
        $this->assertCount(3, $opcoes);
        foreach ($opcoes as $opcao) {
            $this->assertSame(PerfisEspaco::POR_TIPO[$opcao['tipo']], $opcao['perfis']);
            $this->assertNotContains('professor_autonomo', $opcao['perfis']);
        }
        $this->negado(fn()=>app(ConviteVinculo::class)->emitir(Usuario::find(1)->noVinculo(1), 't3@example.test', 4, 'professor_autonomo'));
        $this->assertDatabaseCount('vinculo_convites', 0);
    }
}
