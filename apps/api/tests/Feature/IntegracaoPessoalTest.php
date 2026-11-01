<?php

namespace Tests\Feature;

use App\Jobs\ProcessarCartaoJob;
use App\Models\{Usuario, CartaoEnvio};
use App\Services\Omr\{ImagensCartao, OmrDriverInterface, OmrIdentificacaoInterface};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{DB, Process, Queue, Storage};
use Illuminate\Support\Str;
use Tests\TestCase;

class IntegracaoPessoalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['espacos.ativo'=>true, 'espacos.pessoal'=>true, 'vinculos.obrigatorio'=>true]);
        \App\Support\Permissao::limparCache();
        foreach ([1,2] as $id) {
            Usuario::forceCreate(['id'=>$id,'nome'=>'Professor fictício','perfil'=>'professor','email'=>"pessoal$id@example.test",'cpf'=>str_pad((string)$id,11,'0',STR_PAD_LEFT),'password'=>'ficticio','ativo'=>true]);
            DB::table('clientes')->insert(['id'=>$id,'tipo'=>'professor','nome'=>'Cliente','modalidade'=>'interno','situacao'=>'ativo']);
            DB::table('espacos')->insert(['id'=>$id,'cliente_id'=>$id,'tipo'=>'pessoal','nome'=>"Pessoal $id",'situacao'=>'ativo','usuario_proprietario_id'=>$id]);
            DB::table('usuario_vinculos')->insert(['id'=>$id,'usuario_id'=>$id,'espaco_id'=>$id,'perfil'=>'professor_autonomo','situacao'=>'ativo','inicio_em'=>now()->subDay()]);
            DB::table('turmas')->insert(['id'=>$id,'escola_id'=>null,'espaco_id'=>$id,'espaco_tipo'=>'pessoal','nome'=>"Turma $id",'serie'=>'3','turno'=>'tarde','ano_letivo'=>2026]);
            DB::table('alunos')->insert(['id'=>$id,'turma_id'=>$id,'espaco_id'=>$id,'espaco_tipo'=>'pessoal','nome'=>"Aluno fictício $id",'matricula'=>"P-$id"]);
            DB::table('provas')->insert(['id'=>$id,'escola_id'=>null,'espaco_id'=>$id,'espaco_tipo'=>'pessoal','criado_por'=>$id,'titulo'=>"Prova $id",'disciplina'=>'Teste','serie'=>'3','num_questoes'=>1,'num_alternativas'=>4,'status'=>'publicada']);
            DB::table('prova_turmas')->insert(['prova_id'=>$id,'turma_id'=>$id,'espaco_id'=>$id]);
            DB::table('gabaritos')->insert(['id'=>$id,'prova_id'=>$id,'espaco_id'=>$id]);
            DB::table('gabarito_questoes')->insert(['gabarito_id'=>$id,'espaco_id'=>$id,'numero_questao'=>1,'alternativa'=>'A','peso'=>1]);
        }
    }

    private function chamar(string $token, string $metodo, string $url, array $dados=[])
    {
        app('auth')->forgetGuards();
        return $this->json($metodo,'/api/v1/'.$url,$dados,['Authorization'=>'Bearer '.$token]);
    }

    private function selecionar(): string
    {
        $identidade=Usuario::find(1)->createToken('teste-identidade')->plainTextToken;
        $this->chamar($identidade,'GET','auth/vinculos')->assertOk()->assertJsonCount(1,'data')->assertJsonPath('data.0.perfil','professor_autonomo');
        $this->chamar($identidade,'POST','auth/vinculos/selecionar',['vinculo_id'=>2])->assertForbidden();
        return $this->chamar($identidade,'POST','auth/vinculos/selecionar',['vinculo_id'=>1])->assertCreated()->json('data.token');
    }

    public function test_selecao_painel_minimo_isolamento_e_revogacao(): void
    {
        $t=$this->selecionar();
        $this->chamar($t,'GET','dashboard/professor')->assertOk()->assertJsonPath('data.kpis.minhas_provas',1)
            ->assertJsonPath('data.kpis.minhas_turmas',1)->assertJsonPath('data.professor.espaco_nome','Pessoal 1')
            ->assertJsonPath('data.professor.escola_nome',null)->assertJsonPath('data.provas.0.total_alunos',1)->assertJsonCount(1,'data.provas');
        foreach (['dashboard/admin','escolas','usuarios','provas/2/cartoes/pdf','relatorios/prova/2'] as $url) {
            $this->assertContains($this->chamar($t,'GET',$url)->status(),[403,404]);
        }
        DB::table('clientes')->where('id',1)->update(['situacao'=>'suspenso']);
        $this->chamar($t,'GET','dashboard/professor')->assertForbidden();
    }

    public function test_pdf_recebe_nome_pessoal_e_apenas_alunos_do_espaco(): void
    {
        $t=$this->selecionar(); $arquivo=null;
        Process::fake(function ($processo) use (&$arquivo) {
            $d=json_decode($processo->input,true);
            $this->assertSame('Pessoal 1',$d['escola']); $this->assertNull($d['rede']);
            $this->assertSame([1],array_column($d['alunos'],'id'));
            $this->assertSame('Aluno fictício 1',$d['alunos'][0]['nome']);
            $arquivo=$d['saida'];file_put_contents($arquivo,'%PDF-teste');
            return Process::result(output:'{"ok":true}');
        });
        try {
            $this->chamar($t,'GET','provas/1/cartoes/pdf')->assertOk()->assertHeader('Content-Type','application/pdf');
            $this->chamar($t,'GET','provas/1/cartoes/pdf?turma_id=2')->assertNotFound();
            Process::assertRanTimes(fn()=>true,1);
        } finally { if ($arquivo && is_file($arquivo)) unlink($arquivo); }
    }

    private function enviar(string $token): CartaoEnvio
    {
        Queue::fake([ProcessarCartaoJob::class]);Storage::fake('public');
        $this->mock(ImagensCartao::class,fn($m)=>$m->shouldReceive('gerar')->andReturn(true));
        $this->chamar($token,'POST','provas/1/cartoes',['envio_id'=>(string)Str::uuid(),'imagem'=>UploadedFile::fake()->image('cartao.jpg'),'aluno_id'=>1])->assertStatus(202);
        $e=CartaoEnvio::orderByDesc('id')->firstOrFail();
        Queue::assertPushed(ProcessarCartaoJob::class,fn($j)=>$j->espacoId===1 && $j->vinculoId===1);
        return $e;
    }

    public function test_pdf_revogado_durante_geracao_nao_e_entregue(): void
    {
        $t=$this->selecionar();$arquivo=null;
        Process::fake(function ($processo) use (&$arquivo) {
            $d=json_decode($processo->input,true);$arquivo=$d['saida'];file_put_contents($arquivo,'%PDF-teste');
            DB::table('usuario_vinculos')->where('id',1)->update(['situacao'=>'suspenso']);
            return Process::result(output:'{"ok":true}');
        });
        $this->chamar($t,'GET','provas/1/cartoes/pdf')->assertForbidden();
        $this->assertNotNull($arquivo);$this->assertFileDoesNotExist($arquivo);
    }

    private function job(CartaoEnvio $e): ProcessarCartaoJob
    {
        return unserialize(serialize(new ProcessarCartaoJob($e->cartao_id,$e->id,$e->tentativa,$e->espaco_id,$e->usuario_id,$e->vinculo_id)));
    }

    public function test_fila_pessoal_serializada_gera_nota_e_recusa_qr_de_outro_espaco(): void
    {
        $t=$this->selecionar();$e=$this->enviar($t);
        $d=\Mockery::mock(OmrDriverInterface::class.','.OmrIdentificacaoInterface::class);
        $d->shouldReceive('processar')->once()->andReturn([['numero_questao'=>1,'alternativa'=>'A','confianca'=>1,'ambigua'=>false,'multiplas_marcacoes'=>false,'alternativas_detectadas'=>[]]]);
        $d->shouldReceive('identificacao')->andReturn(['prova_id'=>1,'aluno_id'=>1]);
        $this->job($e)->handle($d,app(ImagensCartao::class));
        $this->assertSame('concluido',$e->fresh()->status);
        $this->assertDatabaseHas('notas',['espaco_id'=>1,'aluno_id'=>1,'nota_final'=>10]);
        $this->chamar($t,'GET','dashboard/professor')->assertOk()->assertJsonPath('data.kpis.media_turmas',10);
        $this->chamar($t,'GET','relatorios/aluno/1')->assertOk()->assertJsonPath('data.cabecalho.nome','Pessoal 1');
        $pdf=null;
        Process::fake(function ($processo) use (&$pdf) {
            $d=json_decode($processo->input,true);
            $this->assertSame('Pessoal 1',$d['escola']);$this->assertNull($d['rede']);
            $this->assertSame('Turma 1',$d['aluno']['turma']);$this->assertEquals(10,$d['nota']['nota_final']);
            $pdf=$d['saida'];file_put_contents($pdf,'%PDF-teste');
            return Process::result(output:'{"ok":true}');
        });
        try { $this->chamar($t,'GET','alunos/1/resultados/1/pdf')->assertOk()->assertHeader('Content-Type','application/pdf'); }
        finally { if ($pdf && is_file($pdf)) unlink($pdf); }

        $e2=$this->enviar($t); // Novo envio tem recibo próprio.
        $e2=CartaoEnvio::orderByDesc('id')->firstOrFail();
        $outro=\Mockery::mock(OmrDriverInterface::class.','.OmrIdentificacaoInterface::class);
        $outro->shouldReceive('processar')->once()->andReturn([]);
        $outro->shouldReceive('identificacao')->andReturn(['prova_id'=>2,'aluno_id'=>2]);
        $this->job($e2)->handle($outro,app(ImagensCartao::class));
        $this->assertSame('recusado',$e2->fresh()->status);
        $this->assertDatabaseMissing('notas',['espaco_id'=>2]);
    }

    public function test_fila_revalida_proprietario_antes_de_ler_imagem(): void
    {
        $e=$this->enviar($this->selecionar());
        DB::table('usuario_vinculos')->where('id',1)->update(['situacao'=>'suspenso']);
        $d=\Mockery::mock(OmrDriverInterface::class);$d->shouldNotReceive('processar');
        $this->job($e)->handle($d,app(ImagensCartao::class));
        $this->assertSame('recusado',$e->fresh()->status);
        $this->assertDatabaseCount('notas',0);
    }

    public function test_mesma_conta_em_escola_e_pessoal_nao_soma_poderes_nem_dados(): void
    {
        $pessoal=$this->selecionar();
        DB::table('redes')->insert(['id'=>3,'nome'=>'Rede institucional','tipo'=>'estadual','uf'=>'BA']);
        DB::table('nucleos')->insert(['id'=>3,'rede_id'=>3,'nome'=>'Núcleo']);
        DB::table('escolas')->insert(['id'=>3,'nucleo_id'=>3,'nome'=>'Escola institucional','inep'=>'00000003','tipo_rede'=>'estadual']);
        foreach (['rede'=>3,'nucleo'=>4,'escola'=>5] as $tipo=>$id) {
            DB::table('espacos')->insert(['id'=>$id,'cliente_id'=>1,'tipo'=>$tipo,'nome'=>'Institucional','situacao'=>'ativo',$tipo.'_id'=>3]);
        }
        DB::table('usuario_vinculos')->insert(['id'=>3,'usuario_id'=>1,'espaco_id'=>5,'perfil'=>'dir_escolar','situacao'=>'ativo','inicio_em'=>now()->subDay()]);
        DB::table('turmas')->insert(['id'=>3,'escola_id'=>3,'espaco_id'=>5,'nome'=>'Turma escolar','serie'=>'3','turno'=>'tarde','ano_letivo'=>2026]);
        $identidade=Usuario::find(1)->createToken('identidade-mista')->plainTextToken;
        $this->chamar($identidade,'GET','auth/vinculos')->assertOk()->assertJsonCount(2,'data');
        $escolar=$this->chamar($identidade,'POST','auth/vinculos/selecionar',['vinculo_id'=>3])->assertCreated()->json('data.token');
        $this->chamar($pessoal,'GET','turmas')->assertOk()->assertJsonCount(1,'data.turmas')->assertJsonPath('data.turmas.0.id',1);
        $this->chamar($escolar,'GET','turmas')->assertOk()->assertJsonCount(1,'data.turmas')->assertJsonPath('data.turmas.0.id',3);
        $this->chamar($pessoal,'GET','escolas')->assertForbidden();
        $this->chamar($escolar,'GET','escolas')->assertOk();
        DB::table('usuario_vinculos')->where('id',1)->update(['situacao'=>'suspenso']);
        $this->chamar($pessoal,'GET','dashboard/professor')->assertForbidden();
        $this->chamar($escolar,'GET','turmas')->assertOk();
    }
}
