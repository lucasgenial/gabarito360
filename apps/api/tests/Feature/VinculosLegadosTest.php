<?php

namespace Tests\Feature;

use App\Models\Usuario;
use App\Services\InventariarVinculosLegados;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VinculosLegadosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach ([1, 2] as $id) {
            DB::table('clientes')->insert(['id' => $id, 'tipo' => 'rede', 'nome' => 'Cliente fictício', 'modalidade' => 'interno', 'situacao' => 'ativo']);
            DB::table('redes')->insert(['id' => $id, 'nome' => 'Rede fictícia', 'tipo' => 'estadual', 'uf' => 'BA']);
            DB::table('nucleos')->insert(['id' => $id, 'rede_id' => $id, 'nome' => 'Núcleo fictício']);
            DB::table('escolas')->insert(['id' => $id, 'nucleo_id' => $id, 'nome' => 'Escola fictícia', 'inep' => '0000000'.$id, 'tipo_rede' => 'estadual']);
            foreach (['rede' => 0, 'nucleo' => 2, 'escola' => 4] as $tipo => $offset) {
                DB::table('espacos')->insert(['id' => $id + $offset, 'cliente_id' => $id, 'tipo' => $tipo, 'nome' => 'Espaço fictício', 'situacao' => 'ativo', $tipo.'_id' => $id]);
            }
            DB::table('turmas')->insert(['id' => $id, 'escola_id' => $id, 'espaco_id' => $id + 4, 'nome' => '3A', 'serie' => '3', 'turno' => 'tarde', 'ano_letivo' => 2026]);
            DB::table('alunos')->insert(['id' => $id, 'turma_id' => $id, 'espaco_id' => $id + 4, 'nome' => 'Nome que não deve sair', 'matricula' => 'V1-'.$id]);
        }
        DB::table('usuarios')->insert(['id' => 1, 'perfil' => 'admin_rede', 'nome' => 'Nome confidencial', 'email' => 'privado@example.test', 'cpf' => '00000000001', 'password' => 'segredo', 'ativo' => true]);
    }

    private function escopo(string $tipo, int $id): void
    {
        DB::table('usuario_escopos')->insert(['usuario_id' => 1, 'escopo_tipo' => $tipo, 'escopo_id' => $id]);
    }

    private function pessoa(): array
    {
        return app(InventariarVinculosLegados::class)->executar()['pessoas'][0];
    }

    public function test_duas_redes_produzem_dois_candidatos_sem_escrever_ou_expor_identidade(): void
    {
        $this->escopo('rede', 2);
        $this->escopo('rede', 1);
        $antes = DB::table('usuario_escopos')->get()->toJson();
        $r = app(InventariarVinculosLegados::class)->executar();
        $this->assertSame([1, 2], array_column($r['pessoas'][0]['vinculos_candidatos'], 'espaco_id'));
        $this->assertSame(0, $r['usuarios_com_pendencias']);
        $this->assertFalse($r['conversao_liberada']);
        $this->assertDatabaseCount('usuario_vinculos', 0);
        $this->assertSame($antes, DB::table('usuario_escopos')->get()->toJson());
        foreach (['Nome confidencial', 'privado@example.test', '00000000001', 'segredo'] as $privado) $this->assertStringNotContainsString($privado, json_encode($r));
        $this->artisan('g360:inventariar-vinculos')->assertSuccessful();
    }

    public function test_professor_agrega_escola_e_turmas_sem_unir_instituicoes(): void
    {
        DB::table('usuarios')->update(['perfil' => 'professor']);
        $this->escopo('escola', 1);
        $this->escopo('turma', 1);
        $this->escopo('turma', 1);
        $this->escopo('turma', 2);
        $p = $this->pessoa();
        $this->assertSame([], $p['pendencias']);
        $this->assertSame([5, 6], array_column($p['vinculos_candidatos'], 'espaco_id'));
        $this->assertSame([[1], [2]], array_column($p['vinculos_candidatos'], 'turmas'));
    }

    public function test_aluno_exige_referencia_explicita_e_coerente(): void
    {
        DB::table('usuarios')->update(['perfil' => 'aluno']);
        $this->assertContains('sem_destino_inequivoco', $this->pessoa()['pendencias']);
        $this->escopo('aluno', 1);
        $this->assertSame([1], $this->pessoa()['vinculos_candidatos'][0]['alunos']);
        DB::table('alunos')->where('id', 1)->update(['espaco_id' => 6]);
        $this->assertContains('restricao_sem_espaco_coerente', $this->pessoa()['pendencias']);
        $this->assertSame([], $this->pessoa()['vinculos_candidatos']);
    }

    public function test_ajustes_inclusive_retirada_vencida_exigem_conciliacao(): void
    {
        $this->escopo('rede', 1);
        $this->escopo('rede', 2);
        DB::table('usuario_permissoes')->insert(['usuario_id' => 1, 'permissao' => 'escola.ver', 'efeito' => 'retirar', 'valida_ate' => '2020-01-01']);
        $p = $this->pessoa();
        $this->assertContains('permissoes_exigem_destino_aprovado', $p['pendencias']);
        $this->assertSame('retirar', $p['ajustes_a_conciliar'][0]['efeito']);
        $this->assertDatabaseCount('vinculo_permissoes', 0);
        $this->artisan('g360:inventariar-vinculos')->assertFailed();
    }

    public function test_conta_inativa_hierarquia_suspensa_e_escopo_incompativel_sao_pendencias(): void
    {
        DB::table('usuarios')->update(['perfil' => 'dir_escolar', 'ativo' => false]);
        $this->escopo('escola', 1);
        $this->escopo('rede', 2);
        DB::table('espacos')->where('id', 1)->update(['situacao' => 'suspenso']);
        $p = $this->pessoa();
        foreach (['conta_inativa_nao_ativar', 'espaco_ou_hierarquia_inativa', 'escopo_incompativel_com_perfil'] as $problema) $this->assertContains($problema, $p['pendencias']);
        $this->assertCount(1, $p['vinculos_candidatos']);
    }

    public function test_orfao_e_espaco_com_referencias_mistas_nao_viram_destino(): void
    {
        $this->escopo('rede', 99);
        $this->escopo('rede', 1);
        DB::table('espacos')->where('id', 1)->update(['usuario_proprietario_id' => 1]);
        $this->assertSame([], $this->pessoa()['vinculos_candidatos']);
        $this->assertContains('instituicao_sem_espaco_coerente', $this->pessoa()['pendencias']);
    }

    private function vinculo(): int
    {
        return DB::table('usuario_vinculos')->insertGetId(['usuario_id' => 1, 'espaco_id' => 1, 'perfil' => 'admin_rede', 'situacao' => 'ativo', 'inicio_em' => now()]);
    }

    public function test_vinculos_existentes_nao_sao_sobrescritos_e_modelo_tem_relacao(): void
    {
        $this->escopo('rede', 1);
        $id = $this->vinculo();
        $this->assertContains('vinculos_existentes_exigem_comparacao', $this->pessoa()['pendencias']);
        $this->assertSame($id, Usuario::findOrFail(1)->vinculos()->firstOrFail()->id);
        $this->assertDatabaseCount('usuario_vinculos', 1);
    }

    public function test_banco_recusa_vinculo_duplicado(): void
    {
        $this->vinculo();
        $this->expectException(QueryException::class);
        $this->vinculo();
    }

    public function test_banco_preserva_vinculo_ao_tentar_excluir_conta(): void
    {
        $this->vinculo();
        $this->expectException(QueryException::class);
        DB::table('usuarios')->where('id', 1)->delete();
    }

    public function test_permissao_inexistente_no_catalogo_e_rejeitada(): void
    {
        $id = $this->vinculo();
        $this->expectException(QueryException::class);
        DB::table('vinculo_permissoes')->insert(['vinculo_id' => $id, 'permissao' => 'inventada', 'efeito' => 'conceder']);
    }
}
