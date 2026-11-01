<?php

namespace Tests\Feature;

use App\Models\{Cliente, Usuario};
use App\Services\CadastroCliente;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Gate};
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CadastroClienteTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $ator;
    private CadastroCliente $cadastro;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ator = Usuario::forceCreate(['nome'=>'Operador fictício', 'perfil'=>'admin_rede', 'email'=>'t4@example.test', 'cpf'=>'00000000001', 'password'=>'ficticio', 'ativo'=>true]);
        $this->cadastro = app(CadastroCliente::class);
    }

    private function autorizarSomenteNoTeste(): void
    {
        Gate::define('gerir-clientes', fn (Usuario $u) => $u->id === $this->ator->id);
    }

    private function dados(array $extra = []): array
    {
        return $extra + ['tipo'=>'professor', 'nome'=>'Cliente fictício', 'modalidade'=>'teste'];
    }

    public function test_ability_ausente_nega_cadastro_e_edicao_sem_conceder_poder_ao_admin_escolar(): void
    {
        foreach (['criar', 'editar'] as $acao) {
            try {
                $acao === 'criar' ? $this->cadastro->cadastrar($this->ator, $this->dados()) : $this->cadastro->atualizar($this->ator, 999, []);
                $this->fail('Gate indefinido deve negar.');
            } catch (AuthorizationException $e) { $this->assertTrue(true); }
        }
        $this->assertDatabaseCount('clientes', 0);
        $this->assertDatabaseCount('cliente_eventos', 0);
    }

    public function test_tipos_modalidades_autoria_e_eventos_sem_conteudo_pessoal(): void
    {
        $this->autorizarSomenteNoTeste();
        foreach (Cliente::TIPOS as $tipo) {
            $c = $this->cadastro->cadastrar($this->ator, $this->dados(['tipo'=>$tipo]));
            $this->assertSame($tipo, $c->tipo);
            $this->assertSame('ativo', $c->situacao);
            $this->assertSame($this->ator->id, $c->criado_por);
        }
        $anterior = 'teste';
        foreach (['pagante', 'presenteado', 'interno', 'teste'] as $modalidade) {
            $this->cadastro->atualizar($this->ator, $c->id, ['modalidade'=>$modalidade]);
            $this->assertDatabaseHas('cliente_eventos', ['cliente_id'=>$c->id, 'ator_id'=>$this->ator->id, 'acao'=>'modalidade_alterada', 'modalidade_anterior'=>$anterior, 'modalidade_nova'=>$modalidade]);
            $anterior = $modalidade;
        }
        $this->cadastro->atualizar($this->ator, $c->id, ['modalidade'=>'teste']);
        $this->assertDatabaseCount('cliente_eventos', 10);
        $this->assertDatabaseCount('espacos', 0);
        $this->assertDatabaseCount('usuario_vinculos', 0);
        $this->assertDatabaseCount('assinaturas', 0);
    }

    public function test_contatos_e_documento_cifrados_ocultos_e_limpaveis(): void
    {
        $this->autorizarSomenteNoTeste();
        $sigilosos = ['documento'=>'DOC-001', 'contato_nome'=>'Contato fictício', 'contato_email'=>'contato@example.test', 'contato_telefone'=>'00000000000', 'contrato_referencia'=>'REF-001'];
        $c = $this->cadastro->cadastrar($this->ator, $this->dados($sigilosos))->fresh();
        $bruto = DB::table('clientes')->find($c->id);
        foreach ($sigilosos as $campo=>$valor) {
            $this->assertSame($valor, $c->$campo);
            $this->assertStringStartsWith('g360:v1:', $bruto->$campo);
            $this->assertStringNotContainsString($valor, $bruto->$campo);
            $this->assertArrayNotHasKey($campo, $c->toArray());
            $this->assertStringNotContainsString($valor, json_encode(DB::table('cliente_eventos')->get()));
        }
        $this->assertNotNull($bruto->documento_hash);
        // Documento comum não une contratantes nem concede vínculos.
        $outro = $this->cadastro->cadastrar($this->ator, $this->dados($sigilosos));
        $this->assertNotSame($outro->id, $c->id);
        $this->assertSame($bruto->documento_hash, $outro->documento_hash);
        $c = $this->cadastro->atualizar($this->ator, $c->id, array_fill_keys(array_keys($sigilosos), null))->fresh();
        foreach ($sigilosos as $campo=>$valor) $this->assertNull($c->$campo);
        $this->assertNull($c->documento_hash);
    }

    public function test_rejeita_campos_operacionais_e_valores_invalidos_sem_escrever(): void
    {
        $this->autorizarSomenteNoTeste();
        foreach ([['tipo'=>'inventado'], ['modalidade'=>'gratis'], ['nome'=>'  '], ['contato_email'=>'invalido'], ['situacao'=>'encerrado'], ['criado_por'=>999], ['espaco_id'=>1]] as $dados) {
            try { $this->cadastro->cadastrar($this->ator, $this->dados($dados)); $this->fail('Dados inválidos aceitos.'); }
            catch (ValidationException $e) { $this->assertNotEmpty($e->errors()); }
        }
        $this->assertDatabaseCount('clientes', 0);
        $this->assertDatabaseCount('cliente_eventos', 0);
    }

    public function test_ator_desativado_no_banco_nao_usa_instancia_antiga(): void
    {
        $this->autorizarSomenteNoTeste();
        DB::table('usuarios')->where('id', $this->ator->id)->update(['ativo'=>false]);
        try { $this->cadastro->cadastrar($this->ator, $this->dados()); $this->fail('Ator inativo aceito.'); }
        catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) { $this->assertSame(403, $e->getStatusCode()); }
        $this->assertDatabaseCount('clientes', 0);
    }

    public function test_falha_do_evento_desfaz_mudanca_de_modalidade(): void
    {
        $this->autorizarSomenteNoTeste();
        $c = $this->cadastro->cadastrar($this->ator, $this->dados());
        DB::listen(function (QueryExecuted $q) {
            if (str_starts_with(strtolower($q->sql), 'insert into') && str_contains($q->sql, 'cliente_eventos')) throw new \RuntimeException('Falha de auditoria simulada');
        });
        try { $this->cadastro->atualizar($this->ator, $c->id, ['modalidade'=>'pagante']); $this->fail('Falha ignorada.'); }
        catch (\RuntimeException $e) { $this->assertSame('Falha de auditoria simulada', $e->getMessage()); }
        $this->assertSame('teste', $c->fresh()->modalidade);
        $this->assertDatabaseCount('cliente_eventos', 1);
    }

    public function test_migracao_retomada_preserva_cliente_interno_e_contatos(): void
    {
        $this->autorizarSomenteNoTeste();
        $c = $this->cadastro->cadastrar($this->ator, $this->dados(['modalidade'=>'interno', 'contato_email'=>'t4@example.test']));
        $migration = require database_path('migrations/2026_11_01_000007_preparar_cadastro_clientes.php');
        $migration->up();
        $migration->up();
        $this->assertSame('interno', $c->fresh()->modalidade);
        $this->assertSame('t4@example.test', $c->fresh()->contato_email);
        $this->assertDatabaseCount('cliente_eventos', 1);
    }
}
