<?php

namespace Tests\Feature;

use App\Models\Usuario;
use App\Services\{GestaoDisciplinas, OpcoesDisciplinaProva};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Interseção curricular usa o mesmo cenário institucional sem repetir a suíte anterior. */
class OpcoesDisciplinaProvaTest extends \Tests\TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase, \Tests\Support\CenarioDisciplinas;

    private function prepararOpcoes(): array
    {
        $g = app(GestaoDisciplinas::class); $u = Usuario::find(1)->noVinculo(1);
        DB::table('turmas')->insert(['id'=>3,'espaco_id'=>5,'escola_id'=>1,'nome'=>'1B','serie'=>'1','turno'=>'tarde','ano_letivo'=>2026]);
        DB::table('vinculo_turmas')->insert(['vinculo_id'=>3,'espaco_id'=>5,'turma_id'=>3]);
        $d = $g->salvar($u, ['nome'=>'Matemática','ativo'=>true]);
        $g->indicar($u,$d->id,['vinculo_id'=>3,'ativo'=>true]);
        $componentes = [];
        foreach ([1,3] as $t) $componentes[$t] = $g->adicionarComponente($u,$t,$d->id)->id;
        return [$u,$g,$d,$componentes,Usuario::find(2)->noVinculo(3)];
    }

    private function atribuir(object $g, Usuario $u, int $turma, int $componente, string $inicio='2026-11-04', ?string $fim=null): void
    {
        $g->atribuir($u,$turma,$componente,['vinculo_id'=>3,'inicio_em'=>$inicio,'fim_em'=>$fim,'papel'=>'titular','chave'=>(string)Str::uuid(),'versao'=>1]);
    }

    public function test_intersecao_exige_atribuicao_em_todas_as_turmas_sem_criar_acesso(): void
    {
        [$u,$g,$d,$cs,$prof] = $this->prepararOpcoes(); $op = app(OpcoesDisciplinaProva::class);
        $this->atribuir($g,$u,1,$cs[1]);
        $this->assertSame([$d->id],array_column($op->listar($prof,[1]),'id'));
        $this->assertSame([],$op->listar($prof,[1,3]));
        $this->atribuir($g,$u,3,$cs[3]);
        $antes=DB::table('vinculo_turmas')->orderBy('turma_id')->get()->toJson();
        $this->assertSame([$d->id],array_column($op->listar($prof,[1,3]),'id'));
        $this->assertSame($antes,DB::table('vinculo_turmas')->orderBy('turma_id')->get()->toJson());
        $this->assertSame([], $op->listar($prof,[]));
    }

    public function test_atribuicao_programada_ou_encerrada_nao_e_vigente_e_fim_e_exclusivo(): void
    {
        [$u,$g,$d,$cs,$prof] = $this->prepararOpcoes(); $op = app(OpcoesDisciplinaProva::class);
        $this->atribuir($g,$u,1,$cs[1],'2026-11-05','2026-11-06');
        $this->assertSame([],$op->listar($prof,[1]));
        $this->travelTo(\Carbon\Carbon::parse('2026-11-05 15:00:00','UTC'));
        $this->assertSame([$d->id],array_column($op->listar($prof,[1]),'id'));
        $this->travelTo(\Carbon\Carbon::parse('2026-11-06 15:00:00','UTC'));
        $this->assertSame([],$op->listar($prof,[1]));
    }

    public function test_retirada_de_acesso_apos_sugestao_invalida_a_proxima_consulta(): void
    {
        [$u,$g,$d,$cs,$prof] = $this->prepararOpcoes(); $op = app(OpcoesDisciplinaProva::class); $this->atribuir($g,$u,1,$cs[1]);
        $this->assertCount(1,$op->listar($prof,[1]));
        DB::table('vinculo_turmas')->where('vinculo_id',3)->where('turma_id',1)->delete();
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);$op->listar($prof,[1]);
    }

    public function test_direcao_precisa_da_capacidade_real_de_criar_e_da_organizacao_em_todas_as_turmas(): void
    {
        [$u,$g,$d,$cs,$prof] = $this->prepararOpcoes(); $op=app(OpcoesDisciplinaProva::class);$dir=Usuario::find(1)->noVinculo(4);
        try {$op->listar($dir,[1]);$this->fail('Direção sem extra não cria prova.');}catch(\Symfony\Component\HttpKernel\Exception\HttpException $e){$this->assertSame(403,$e->getStatusCode());}
        DB::table('vinculo_permissoes')->insert(['vinculo_id'=>4,'permissao'=>'prova.criar','efeito'=>'conceder']);
        $this->assertSame([$d->id],array_column($op->listar($dir,[1,3]),'id'));
        $g->situacaoComponente($u,3,$cs[3],['ativo'=>false,'versao'=>1]);
        $this->assertSame([],$op->listar($dir,[1,3]));
        $this->assertSame([$d->id],array_column($op->listar($dir,[1]),'id'));
    }

    public function test_turmas_estrangeiras_e_ids_duplicados_nao_formam_opcao(): void
    {
        [$u,$g,$d,$cs,$prof]=$this->prepararOpcoes();$op=app(OpcoesDisciplinaProva::class);
        try {$op->listar($u,[1,2]);$this->fail('Turma estrangeira deve ser recusada.');}catch(\Symfony\Component\HttpKernel\Exception\HttpException $e){$this->assertSame(404,$e->getStatusCode());}
        $this->expectException(\Illuminate\Validation\ValidationException::class);$op->listar($u,[1,1]);
    }
}
