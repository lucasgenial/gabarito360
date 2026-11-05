<?php

namespace Tests\Feature;

use App\Models\Prova;
use App\Models\Usuario;
use App\Services\Omr\AtualizadorStatusProva;
use App\Services\Omr\ProgressoCorrecao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProgressoCorrecaoTest extends TestCase
{
    use RefreshDatabase;

    private Prova $prova;

    private int $turma;

    private Usuario $usuario;

    protected function setUp(): void
    {
        parent::setUp();
        config(['espacos.ativo' => false]);
        $this->usuario = Usuario::create(['nome' => 'Pessoa fictícia', 'email' => 'progresso@example.test', 'cpf' => '00000000001', 'password' => 'teste', 'perfil' => 'admin_rede', 'ativo' => true]);
        $rede = DB::table('redes')->insertGetId(['nome' => 'Teste', 'tipo' => 'estadual', 'uf' => 'BA']);
        $nucleo = DB::table('nucleos')->insertGetId(['rede_id' => $rede, 'nome' => 'Teste']);
        $escola = DB::table('escolas')->insertGetId(['nucleo_id' => $nucleo, 'nome' => 'Teste', 'inep' => '29000001', 'tipo_rede' => 'estadual']);
        $this->turma = DB::table('turmas')->insertGetId(['escola_id' => $escola, 'nome' => '1A', 'serie' => '1', 'turno' => 'tarde', 'ano_letivo' => 2026]);
        $this->prova = Prova::create(['escola_id' => $escola, 'criado_por' => $this->usuario->id, 'titulo' => 'Teste', 'disciplina' => 'Matemática', 'serie' => '1', 'num_questoes' => 2, 'num_alternativas' => 5, 'status' => 'publicada', 'nota_maxima' => 10]);
        DB::table('prova_turmas')->insert(['prova_id' => $this->prova->id, 'turma_id' => $this->turma]);
    }

    private function aluno(bool $ativo = true): int
    {
        return DB::table('alunos')->insertGetId(['turma_id' => $this->turma, 'nome' => 'Aluno fictício', 'matricula' => (string) Str::uuid(), 'ativo' => $ativo]);
    }

    private function cartao(?int $aluno, string $status = 'lido', bool $nota = false): int
    {
        $id = DB::table('cartoes')->insertGetId(['prova_id' => $this->prova->id, 'aluno_id' => $aluno, 'status' => $status]);
        if ($nota) {
            DB::table('notas')->insert(['cartao_id' => $id, 'prova_id' => $this->prova->id, 'aluno_id' => $aluno, 'turma_id' => $this->turma, 'acertos' => 1, 'total_questoes' => 2, 'nota_final' => 5, 'status_aprovacao' => 'recuperacao']);
        }

        return $id;
    }

    public function test_um_envio_nao_significa_cem_por_cento_da_turma(): void
    {
        $this->cartao($this->aluno(), nota: true);
        $this->aluno();
        $this->aluno();
        $this->aluno(false);
        $r = ProgressoCorrecao::calcular($this->prova);
        $this->assertSame(3, $r['esperados']);
        $this->assertSame(1, $r['concluidos']);
        $this->assertSame(2, $r['sem_cartao']);
        $this->assertSame(33.33, $r['percentual_concluido']);
        $this->assertFalse($r['concluida']);
        AtualizadorStatusProva::atualizar($this->prova);
        $this->assertSame('em_correcao', $this->prova->fresh()->status);
    }

    public function test_fotos_duplicadas_nao_identificadas_e_sem_nota_nao_inflam_conclusao(): void
    {
        $a = $this->aluno();
        $this->cartao($a, nota: true);
        $this->cartao($a, nota: true);
        $this->cartao(null);
        $this->cartao($this->aluno());
        $r = ProgressoCorrecao::calcular($this->prova);
        $this->assertSame(4, $r['cartoes_recebidos']);
        $this->assertSame(1, $r['nao_identificados']);
        $this->assertSame(1, $r['concluidos']);
        $this->assertSame(1, $r['falhas']);
        $this->assertSame(50.0, $r['percentual_concluido']);
    }

    public function test_revisao_processamento_e_falha_sao_estados_distintos(): void
    {
        $this->cartao($this->aluno(), 'ambiguo');
        $fila = $this->cartao($this->aluno(), 'pendente');
        $this->cartao($this->aluno(), 'pendente');
        $this->aluno();
        DB::table('cartao_envios')->insert(['chave' => (string) Str::uuid(), 'usuario_id' => $this->usuario->id, 'prova_id' => $this->prova->id, 'cartao_id' => $fila, 'status' => 'processando']);
        $r = ProgressoCorrecao::calcular($this->prova);
        foreach (['alunos_revisao', 'em_processamento', 'falhas', 'sem_cartao'] as $k) {
            $this->assertSame(1, $r[$k]);
        }
        $this->assertSame(0, $r['concluidos']);
        $this->assertFalse($r['concluida']);
    }

    public function test_proposta_ou_novo_processamento_nao_invalida_resultado_confirmado(): void
    {
        $a = $this->aluno();
        $this->cartao($a, nota: true);
        $this->cartao($a, 'ambiguo');
        $r = ProgressoCorrecao::calcular($this->prova);
        $this->assertSame(1, $r['concluidos']);
        $this->assertTrue($r['concluida']);
        $this->assertEquals(100, $r['percentual_concluido']);
        AtualizadorStatusProva::atualizar($this->prova);
        $this->assertSame('corrigida', $this->prova->fresh()->status);
    }

    public function test_sem_alunos_nao_declara_conclusao_ou_percentual_zero_falso(): void
    {
        $this->cartao(null);
        $r = ProgressoCorrecao::calcular($this->prova);
        $this->assertNull($r['percentual_concluido']);
        $this->assertFalse($r['concluida']);
        $this->assertSame(0,$r['esperados']);
    }
}
