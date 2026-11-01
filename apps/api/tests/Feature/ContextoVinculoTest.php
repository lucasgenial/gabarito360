<?php

namespace Tests\Feature;

use App\Models\Usuario;
use App\Support\ContextoCorrecao;
use App\Support\ContextoVinculo;
use App\Support\Escopo;
use App\Support\Instituicoes;
use App\Support\Permissao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ContextoVinculoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['espacos.ativo' => true]);
        $this->travelTo(now()->startOfSecond());
        Permissao::limparCache();
        foreach ([1, 2] as $id) {
            DB::table('usuarios')->insert(['id' => $id, 'perfil' => 'admin_rede', 'nome' => 'Pessoa fictícia', 'email' => $id.'@example.test', 'cpf' => str_pad((string) $id, 11, '0', STR_PAD_LEFT), 'password' => 'ficticio', 'ativo' => true]);
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

    private function recusa(callable $acao): void
    {
        try { $acao(); $this->fail('Contexto deveria ser recusado.'); }
        catch (HttpException $e) { $this->assertSame(403, $e->getStatusCode()); }
    }

    public function test_diretor_em_a_e_professor_em_b_nao_unem_papeis_dados_ou_cache(): void
    {
        $pessoa = Usuario::findOrFail(1);
        $this->assertTrue(Permissao::pode($pessoa, 'rede.configurar')); // Preenche cache legado da mesma pessoa.
        $a = $pessoa->noVinculo(1);
        $b = $pessoa->noVinculo(2);
        for ($i = 0; $i < 2; $i++) {
            $this->assertSame('dir_escolar', $a->perfil);
            $this->assertSame('professor', $b->perfil);
            $this->assertFalse(Permissao::pode($a, 'rede.configurar'));
            $this->assertFalse(Permissao::pode($b, 'usuario.gerir'));
            $this->assertTrue(Permissao::pode($a, 'usuario.gerir'));
            $this->assertSame([1], Escopo::escolaIds($a));
            $this->assertSame([2], Escopo::escolaIds($b));
            $this->assertSame([1], Escopo::provas($a)->pluck('id')->all());
            $this->assertSame([2], Escopo::provas($b)->pluck('id')->all());
            $this->assertSame([1], Escopo::alunos($a)->pluck('id')->all());
            $this->assertSame([2], Escopo::alunos($b)->pluck('id')->all());
        }
        $this->assertSame('admin_rede', $pessoa->perfil);
        $this->assertSame('admin_rede', $a->getRawOriginal('perfil'));
        $this->assertDatabaseHas('usuarios', ['id' => 1, 'perfil' => 'admin_rede']);
    }

    public function test_permissoes_individuais_so_valem_no_vinculo_e_revogam_sem_limpar_cache(): void
    {
        $b = Usuario::find(1)->noVinculo(2);
        $extra = DB::table('perfil_permissoes')->where('perfil', 'professor')->where('tipo', 'extra')->value('permissao');
        DB::table('usuario_permissoes')->insert(['usuario_id' => 1, 'permissao' => $extra, 'efeito' => 'conceder']);
        $this->assertFalse(Permissao::pode($b, $extra));
        DB::table('vinculo_permissoes')->insert(['vinculo_id' => 2, 'permissao' => $extra, 'efeito' => 'conceder']);
        $this->assertTrue(Permissao::pode($b, $extra));
        $this->vinculo(4, 1, 5, 'professor');
        $this->assertFalse(Permissao::pode(Usuario::find(1)->noVinculo(4), $extra));
        DB::table('vinculo_permissoes')->where('vinculo_id', 2)->update(['valida_ate' => now()->subDay()->toDateString()]);
        $this->assertFalse(Permissao::pode($b, $extra));
        $this->assertTrue(Permissao::pode($b, 'prova.ver'));
        DB::table('vinculo_permissoes')->insert(['vinculo_id' => 2, 'permissao' => 'prova.ver', 'efeito' => 'retirar']);
        $this->assertFalse(Permissao::pode($b, 'prova.ver'));
    }

    public function test_professor_nao_ganha_outra_turma_da_mesma_escola(): void
    {
        DB::table('turmas')->insert(['id' => 3, 'escola_id' => 2, 'espaco_id' => 6, 'nome' => '3B', 'serie' => '3', 'turno' => 'tarde', 'ano_letivo' => 2026]);
        DB::table('provas')->insert(['id' => 3, 'escola_id' => 2, 'espaco_id' => 6, 'criado_por' => 2,
            'titulo' => 'Outra prova', 'disciplina' => 'Teste', 'serie' => '3', 'num_questoes' => 1, 'num_alternativas' => 4]);
        DB::table('prova_turmas')->insert(['prova_id' => 3, 'turma_id' => 3, 'espaco_id' => 6]);
        $b = Usuario::find(1)->noVinculo(2);
        $this->assertSame([2], Escopo::turmaIds($b));
        $this->assertSame([2], Escopo::provas($b)->pluck('id')->all());
        DB::table('vinculo_turmas')->where('vinculo_id', 2)->delete();
        $this->assertSame([], Escopo::turmaIds($b));
        $this->assertSame([], Escopo::alunos($b)->pluck('id')->all());
    }

    public function test_duas_redes_explicitamente_separadas_e_ancestrais_corretos(): void
    {
        $this->vinculo(4, 1, 1, 'admin_rede');
        $this->vinculo(5, 1, 2, 'admin_rede');
        $this->vinculo(6, 1, 4, 'dir_nucleo');
        foreach ([4 => 1, 5 => 2, 6 => 2] as $v => $escola) {
            $u = Usuario::find(1)->noVinculo($v);
            $this->assertSame([$escola], Escopo::escolaIds($u));
            $this->assertSame([$escola], Instituicoes::redesIds($u));
            $this->assertSame([$escola], Instituicoes::nucleosIds($u));
        }
    }

    public function test_aluno_so_recebe_seu_registro_sem_colegas_turma_ou_provas(): void
    {
        $this->vinculo(4, 1, 6, 'aluno');
        DB::table('vinculo_alunos')->insert(['vinculo_id' => 4, 'espaco_id' => 6, 'aluno_id' => 2]);
        DB::table('alunos')->insert(['id' => 3, 'turma_id' => 2, 'espaco_id' => 6, 'nome' => 'Colega', 'matricula' => 'C-3']);
        $u = Usuario::find(1)->noVinculo(4);
        $this->assertSame([2], Escopo::alunos($u)->pluck('id')->all());
        $this->assertSame([], Escopo::turmaIds($u));
        $this->assertSame([], Escopo::provas($u)->pluck('id')->all());
        $this->assertSame([2], Instituicoes::escolasLogoIds($u));
    }

    public static function bloqueios(): array
    {
        return [
            ['vinculo', 'situacao', 'convidado'], ['vinculo', 'situacao', 'suspenso'], ['vinculo', 'situacao', 'encerrado'],
            ['vinculo', 'perfil', 'dir_nucleo'], ['usuario', 'ativo', false],
            ['espaco', 'situacao', 'suspenso'], ['nucleo', 'situacao', 'encerrado'], ['rede', 'situacao', 'suspenso'],
            ['cliente', 'situacao', 'encerrado'], ['espaco', 'cliente_id', 1],
        ];
    }

    #[DataProvider('bloqueios')]
    public function test_contexto_ja_criado_revalida_conta_vinculo_e_hierarquia(string $alvo, string $campo, mixed $valor): void
    {
        $u = Usuario::find(1)->noVinculo(2);
        $this->assertNotEmpty(Permissao::efetivas($u));
        [$tabela, $id] = match ($alvo) {
            'vinculo' => ['usuario_vinculos', 2], 'usuario' => ['usuarios', 1], 'cliente' => ['clientes', 2],
            'espaco' => ['espacos', 6], 'nucleo' => ['espacos', 4], 'rede' => ['espacos', 2],
        };
        DB::table($tabela)->where('id', $id)->update([$campo => $valor]);
        $this->recusa(fn () => Permissao::efetivas($u));
        $this->recusa(fn () => Escopo::escolaIds($u));
    }

    public function test_vinculo_alheio_inexistente_e_vigencia_sao_recusados(): void
    {
        $p = Usuario::find(1);
        $this->recusa(fn () => $p->noVinculo(3));
        $this->recusa(fn () => $p->noVinculo(999));
        DB::table('usuario_vinculos')->where('id', 2)->update(['inicio_em' => now()->addSecond()]);
        $this->recusa(fn () => $p->noVinculo(2));
        $this->travel(1)->seconds();
        $u = $p->noVinculo(2);
        DB::table('usuario_vinculos')->where('id', 2)->update(['fim_em' => now()->addSecond()]);
        $this->assertNotEmpty(Permissao::efetivas($u));
        $this->travel(1)->seconds();
        $this->recusa(fn () => Permissao::efetivas($u));
    }

    public function test_versao_de_acesso_distingue_contextos_e_mudancas(): void
    {
        $p = Usuario::find(1);
        $a = $p->noVinculo(1);
        $b = $p->noVinculo(2);
        $antes = ContextoCorrecao::versaoAcesso($b);
        $this->assertNotSame(ContextoCorrecao::versaoAcesso($a), $antes);
        $this->assertSame($antes, ContextoVinculo::versaoAcesso($b));
        DB::table('usuario_vinculos')->where('id', 2)->increment('versao_autorizacao');
        $depois = ContextoCorrecao::versaoAcesso($b);
        $this->assertNotSame($antes, $depois);
        DB::table('vinculo_turmas')->where('vinculo_id', 2)->delete();
        $this->assertNotSame($depois, ContextoCorrecao::versaoAcesso($b));
    }

    public function test_fresh_preserva_vinculo_e_serializacao_de_fila_nao_o_descarta(): void
    {
        $pessoa = Usuario::find(1);
        $this->assertSame(1, $pessoa->getQueueableId());
        $b = $pessoa->noVinculo(2)->fresh();
        $this->assertSame(2, $b->vinculoContextoId());
        $this->assertSame('professor', $b->perfil);
        $b->refresh();
        $this->assertSame(2, $b->vinculoContextoId());
        $this->expectException(\LogicException::class);
        $b->getQueueableId();
    }

    public function test_link_assinado_preserva_contexto_e_recusa_revogacao_ou_troca(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        \Illuminate\Support\Facades\Storage::disk('public')->put('cartoes/contexto.jpg', 'imagem ficticia');
        DB::table('cartoes')->insert(['id' => 1, 'prova_id' => 2, 'espaco_id' => 6, 'aluno_id' => 2, 'imagem_url' => 'cartoes/contexto.jpg']);
        $u = Usuario::find(1)->noVinculo(2);
        $url = app(\App\Services\Omr\ImagensCartao::class)->url(1, 'original', $u);
        $this->assertStringContainsString('vinculo=2', $url);
        $this->get($url)->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->get(str_replace('vinculo=2', 'vinculo=1', $url))->assertForbidden();
        DB::table('usuario_vinculos')->where('id', 2)->increment('versao_autorizacao');
        $this->get($url)->assertNotFound();
        $atual = app(\App\Services\Omr\ImagensCartao::class)->url(1, 'original', $u);
        $this->get($atual)->assertOk();
        DB::table('usuario_vinculos')->where('id', 2)->update(['situacao' => 'suspenso']);
        $this->get($atual)->assertNotFound();
    }
}
