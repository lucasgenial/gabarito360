<?php
namespace Tests\Feature;

use App\Models\{Usuario,Prova};
use App\Services\Omr\ImagensCartao;
use App\Http\Controllers\Api\{CartaoController,EscolaController};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB,Storage};
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class LeiturasEmLoteTest extends TestCase
{
    use RefreshDatabase;
    private Usuario $ator;

    protected function setUp(): void
    {
        parent::setUp();config(['espacos.ativo'=>true]);Storage::fake('public');
        foreach([1,2] as $id) {
            Usuario::forceCreate(['id'=>$id,'nome'=>'Fictício','perfil'=>'professor','email'=>"lote$id@example.test",'cpf'=>str_pad((string)$id,11,'0',STR_PAD_LEFT),'password'=>'ficticio','ativo'=>true]);
            DB::table('clientes')->insert(['id'=>$id,'tipo'=>'rede','nome'=>'Cliente fictício','modalidade'=>'interno','situacao'=>'ativo']);
            DB::table('redes')->insert(['id'=>$id,'nome'=>'Rede','tipo'=>'estadual','uf'=>'BA']);
            DB::table('nucleos')->insert(['id'=>$id,'rede_id'=>$id,'nome'=>'Núcleo']);
            DB::table('escolas')->insert(['id'=>$id,'nucleo_id'=>$id,'nome'=>'Escola','inep'=>'0000000'.$id,'tipo_rede'=>'estadual']);
            foreach(['rede'=>0,'nucleo'=>2,'escola'=>4] as $tipo=>$off)DB::table('espacos')->insert(['id'=>$id+$off,'cliente_id'=>$id,'tipo'=>$tipo,'nome'=>'Espaço','situacao'=>'ativo',$tipo.'_id'=>$id]);
            DB::table('usuario_vinculos')->insert(['id'=>$id,'usuario_id'=>$id,'espaco_id'=>$id+4,'perfil'=>'coordenador','situacao'=>'ativo','inicio_em'=>now()->subDay()]);
            DB::table('turmas')->insert(['id'=>$id,'escola_id'=>$id,'espaco_id'=>$id+4,'nome'=>'Turma','serie'=>'1','turno'=>'tarde','ano_letivo'=>2026]);
            DB::table('provas')->insert(['id'=>$id,'espaco_id'=>$id+4,'escola_id'=>$id,'criado_por'=>$id,'titulo'=>'Prova fictícia','disciplina'=>'Teste','serie'=>'1','num_questoes'=>45,'num_alternativas'=>5]);
            DB::table('prova_turmas')->insert(['prova_id'=>$id,'turma_id'=>$id,'espaco_id'=>$id+4]);
        }
        $this->ator=Usuario::findOrFail(1)->noVinculo(1);$this->cartao(1);$this->cartao(200,2);
    }

    private function cartao(int $id,int $prova=1): void
    {
        $espaco=$prova+4;
        DB::table('alunos')->insert(['id'=>$id,'espaco_id'=>$espaco,'turma_id'=>$prova,'nome'=>'Aluno fictício','matricula'=>'FICTICIO-'.$id,'ativo'=>true]);
        DB::table('cartoes')->insert(['id'=>$id,'espaco_id'=>$espaco,'prova_id'=>$prova,'aluno_id'=>$id,'turma_aplicacao_id'=>$prova,'imagem_url'=>'cartoes/ficticio-'.$id.'.jpg','status'=>'ambiguo']);
        Storage::disk('public')->put('cartoes/ficticio-'.$id.'.jpg','original fictício');
        Storage::disk('public')->put(ImagensCartao::caminho($id,'miniatura',$espaco),'miniatura fictícia');
        Storage::disk('public')->put(ImagensCartao::caminho($id,'endireitada',$espaco),'foto fictícia');
        DB::table('cartao_respostas')->insert(['cartao_id'=>$id,'espaco_id'=>$espaco,'numero_questao'=>1,'alternativa'=>'A','ambigua'=>true]);
        DB::table('notas')->insert(['cartao_id'=>$id,'prova_id'=>$prova,'aluno_id'=>$id,'turma_id'=>$prova,'espaco_id'=>$espaco,'acertos'=>1,'total_questoes'=>45,'nota_final'=>5,'status_aprovacao'=>'recuperacao']);
    }

    private function request(array $dados=[]): Request { $r=Request::create('/api/v1/provas/1/revisao','GET',$dados);$r->setUserResolver(fn()=>$this->ator);return $r; }
    private function medir(callable $operacao): array
    {
        $n=0;$medindo=true;DB::listen(function()use(&$n,&$medindo){if($medindo)$n++;});
        try{$r=$operacao();}finally{$medindo=false;}
        $this->assertSame(200,$r->getStatusCode());return [$n,$r->getData(true)];
    }

    public function test_volume_nao_repete_autorizacao_por_cartao_ou_por_questao(): void
    {
        $c=app(CartaoController::class);$i=app(ImagensCartao::class);
        [$pequeno]=$this->medir(fn()=>$c->index($this->request(),1,$i));
        for($id=3;$id<=32;$id++)$this->cartao($id);
        [$grande,$lista]=$this->medir(fn()=>$c->index($this->request(),1,$i));
        $this->assertCount(31,$lista['data']['cartoes']);$this->assertLessThanOrEqual($pequeno+10,$grande);
        $this->assertSame(1,$lista['data']['cartoes'][0]['ambiguas']);
        [$uma]=$this->medir(fn()=>$c->revisao($this->request(['cartao'=>1]),1,$i));
        for($q=2;$q<=45;$q++)DB::table('cartao_respostas')->insert(['cartao_id'=>1,'espaco_id'=>5,'numero_questao'=>$q,'alternativa'=>'A','ambigua'=>true]);
        [$muitas,$fila]=$this->medir(fn()=>$c->revisao($this->request(['cartao'=>1]),1,$i));
        $this->assertCount(45,$fila['data']['itens']);$this->assertLessThanOrEqual($uma+10,$muitas);
        $this->assertSame($fila['data']['itens'][0]['foto_url'],$fila['data']['itens'][44]['foto_url']);
        $this->assertDatabaseCount('notas',32);$this->assertDatabaseHas('notas',['aluno_id'=>1,'nota_final'=>5,'status_aprovacao'=>'recuperacao']);
    }

    public function test_lote_mantem_assinatura_vigencia_isolamento_e_revogacao_na_abertura(): void
    {
        $i=app(ImagensCartao::class);$p=Prova::findOrFail(1);
        $urls=$i->urlsDaProva($p,[1],['original','endireitada'],$this->ator);
        $this->assertSame($i->url(1,'original',$this->ator),$urls[1]['original']);
        $this->get($urls[1]['original'])->assertOk();
        $this->get(str_replace('/1/arquivo','/200/arquivo',$urls[1]['original']))->assertForbidden();
        try{$i->urlsDaProva($p,[1,200],['original'],$this->ator);$this->fail('Não pode emitir URL de outra prova/espaço.');}
        catch(HttpException $e){$this->assertSame(404,$e->getStatusCode());}
        DB::table('usuario_vinculos')->where('id',1)->update(['situacao'=>'suspenso']);
        $this->get($urls[1]['original'])->assertNotFound();
    }

    public function test_escola_agrega_notas_sem_consultas_por_aluno_e_sem_misturar_espacos(): void
    {
        $c=app(EscolaController::class);
        [$pequeno]=$this->medir(fn()=>$c->show($this->request(),1));
        for($id=3;$id<=32;$id++)$this->cartao($id);
        [$grande,$d]=$this->medir(fn()=>$c->show($this->request(),1));
        $this->assertLessThanOrEqual($pequeno+10,$grande);$this->assertCount(31,$d['data']['alunos']);
        $this->assertEquals(5.0,$d['data']['alunos'][0]['media_geral']);$this->assertSame(1,$d['data']['alunos'][0]['total_provas']);
        $this->assertSame(31,$d['data']['turmas'][0]['total_alunos']);
        $this->assertNotContains(200,array_column($d['data']['alunos'],'id'));
    }
}
