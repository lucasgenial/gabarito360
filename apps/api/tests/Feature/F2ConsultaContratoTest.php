<?php

namespace Tests\Feature;

use App\Models\Usuario;
use App\Support\{ConsultasPedagogicas, ContextoVinculo, GestaoVinculos};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class F2ConsultaContratoTest extends TestCase
{
    use RefreshDatabase;
    use \Tests\Support\CenarioDisciplinas { setUp as private iniciarCenario; }

    protected function setUp(): void { $this->iniciarCenario(); }

    public function test_leitura_de_gestao_preserva_convidados_suspensos_e_contas_inativas(): void
    {
        foreach ([10 => ['convidado', true], 11 => ['suspenso', true], 12 => ['ativo', false]] as $id => [$estado, $ativo]) {
            Usuario::forceCreate(['id' => $id, 'perfil' => 'professor', 'nome' => 'Conta fictícia', 'email' => $id.'@example.test',
                'cpf' => str_pad((string) $id, 11, '0', STR_PAD_LEFT), 'password' => 'ficticio', 'ativo' => $ativo]);
            DB::table('usuario_vinculos')->insert(['id' => $id, 'usuario_id' => $id, 'espaco_id' => 5, 'perfil' => 'professor',
                'situacao' => $estado, 'inicio_em' => now()->subDay()]);
        }
        $u = Usuario::findOrFail(1)->noVinculo(1);
        $consulta = new ConsultasPedagogicas($u);
        $ids = $consulta->tabela('usuarios')->pluck('usuarios.id')->all();
        foreach ([10, 11, 12] as $id) $this->assertContains($id, $ids);
        $vigentes = GestaoVinculos::pessoas($u)->pluck('usuarios.id')->all();
        foreach ([10, 11, 12] as $id) $this->assertNotContains($id, $vigentes);
        // A projeção de gestão não cria um acesso utilizável para esses cadastros.
        foreach ([10, 11, 12] as $id) {
            try { Usuario::findOrFail($id)->noVinculo($id); $this->fail('Cadastro sem vigência não pode autorizar acesso.'); }
            catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) { $this->assertSame(403, $e->getStatusCode()); }
        }
    }

    public function test_resolver_e_fresco_e_uma_statement_inclusive_conflito_especifico(): void
    {
        $u = Usuario::findOrFail(2)->noVinculo(3);
        DB::enableQueryLog();
        DB::flushQueryLog();
        try {
            $this->assertSame('professor', ContextoVinculo::resolver($u)->perfil);
            $this->assertCount(1, DB::getQueryLog());
            $this->assertStringNotContainsString('sqlite_master', DB::getQueryLog()[0]['query']);
        } finally { DB::disableQueryLog(); DB::flushQueryLog(); }
        DB::table('usuario_vinculos')->insert(['id' => 10, 'usuario_id' => 2, 'espaco_id' => 5, 'perfil' => 'secretario_escolar',
            'situacao' => 'ativo', 'inicio_em' => now()->subDay()]);
        try { ContextoVinculo::resolver($u); $this->fail('Deveria recusar conflito atual.'); }
        catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
            $this->assertSame('Esta instituição já possui um vínculo de outro grupo funcional para esta pessoa. Encerre o vínculo anterior ou solicite ao suporte uma exceção vinculada a chamado.', $e->getMessage());
        }
    }
}
