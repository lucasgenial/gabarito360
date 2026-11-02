<?php
namespace Tests\Feature;
use App\Models\Usuario;
use App\Services\{LotesResultadosCopia,AcompanhamentoCopias};
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\{Crypt,DB,Schema};
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class AcompanhamentoCopiasTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp(); $db=DB::connection();
        if (!(($db->getDriverName()==='sqlite' && $db->getDatabaseName()===':memory:')
            || (in_array($db->getDriverName(),['mysql','mariadb'],true) && str_ends_with($db->getDatabaseName(),'_test')))) throw new \RuntimeException('Banco descartável obrigatório.');
        RefreshDatabaseState::$migrated=false; $this->artisan('migrate:fresh',['--force'=>true])->assertSuccessful();
        Schema::table('alunos',function($t) { $t->unique(['espaco_id','matricula'],'t2_alunos_matricula'); $t->dropUnique('alunos_matricula_unique'); });
        config(['espacos.ativo'=>true,'espacos.pessoal'=>true,'vinculos.obrigatorio'=>true,'copias.ativo'=>true,
            'copias.execucao_ativa'=>true,'copias.cadastro_execucao_ativa'=>true,'copias.auditoria_entrega'=>true]);
        foreach ([1,2,3] as $id) Usuario::forceCreate(['id'=>$id,'perfil'=>'professor','nome'=>'Fictício','email'=>"d1-$id@example.test",
            'cpf'=>sprintf('%011d',$id),'password'=>'ficticio','ativo'=>true]);
        foreach ([1,2] as $id) {
            DB::table('clientes')->insert(['id'=>$id,'tipo'=>'rede','nome'=>'Rede','modalidade'=>'interno','situacao'=>'ativo']);
            DB::table('redes')->insert(['id'=>$id,'nome'=>'Rede','tipo'=>'estadual','uf'=>'BA']);
            DB::table('nucleos')->insert(['id'=>$id,'rede_id'=>$id,'nome'=>'Núcleo']);
            DB::table('escolas')->insert(['id'=>$id,'nucleo_id'=>$id,'nome'=>'Escola','inep'=>sprintf('%08d',$id),'tipo_rede'=>'estadual']);
            foreach (['rede'=>0,'nucleo'=>2,'escola'=>4] as $tipo=>$offset) DB::table('espacos')->insert([
                'id'=>$id+$offset,'cliente_id'=>$id,'tipo'=>$tipo,'nome'=>$tipo,'situacao'=>'ativo',$tipo.'_id'=>$id]);
        }
        DB::table('clientes')->insert(['id'=>3,'tipo'=>'professor','nome'=>'Pessoal','modalidade'=>'teste','situacao'=>'ativo']);
        DB::table('espacos')->insert(['id'=>7,'cliente_id'=>3,'tipo'=>'pessoal','nome'=>'Pessoal','situacao'=>'ativo','usuario_proprietario_id'=>1]);
        foreach ([[1,1,7,'professor_autonomo'],[2,2,5,'dir_escolar'],[3,1,6,'professor'],[4,2,6,'dir_escolar'],[5,3,1,'admin_rede']] as [$id,$u,$e,$perfil]) {
            DB::table('usuario_vinculos')->insert(['id'=>$id,'usuario_id'=>$u,'espaco_id'=>$e,'perfil'=>$perfil,'situacao'=>'ativo','inicio_em'=>now()->subDay(),'versao_autorizacao'=>1]);
        }
        foreach ([[1,7,null,'pessoal'],[2,5,1,'escola'],[3,6,2,'escola']] as [$id,$e,$escola,$tipo]) {
            DB::table('provas')->insert(['id'=>$id,'espaco_id'=>$e,'espaco_tipo'=>$tipo,'escola_id'=>$escola,'criado_por'=>1,
                'titulo'=>'Material fictício','disciplina'=>'Matemática','serie'=>'3','num_questoes'=>1,'num_alternativas'=>4]);
            DB::table('gabaritos')->insert(['id'=>$id,'prova_id'=>$id,'espaco_id'=>$e]);
            DB::table('gabarito_questoes')->insert(['gabarito_id'=>$id,'espaco_id'=>$e,'numero_questao'=>1,'alternativa'=>'A']);
        }
        foreach ([[1,5,1,'escola'],[2,6,2,'escola'],[3,7,null,'pessoal']] as [$id,$e,$escola,$tipo]) {
            DB::table('turmas')->insert(['id'=>$id,'espaco_id'=>$e,'espaco_tipo'=>$tipo,'escola_id'=>$escola,'nome'=>'Turma','serie'=>'3','turno'=>'manha','ano_letivo'=>2026]);
            DB::table('alunos')->insert(['id'=>$id,'espaco_id'=>$e,'espaco_tipo'=>$tipo,'turma_id'=>$id,'nome'=>'NOME-ESCOLAR-FICTICIO',
                'matricula'=>"MAT-$id",'data_nascimento'=>$tipo==='escola'?'2010-01-01':null]);
        }
    }
    private function resultado(): void
    {
        DB::table('prova_turmas')->insert(['prova_id'=>2,'turma_id'=>1,'espaco_id'=>5]);
        DB::table('cartoes')->insert(['id'=>1,'prova_id'=>2,'aluno_id'=>1,'turma_aplicacao_id'=>1,'espaco_id'=>5,
            'status'=>'lido','imagem_url'=>'SEGREDO-IMAGEM']);
        DB::table('cartao_respostas')->insert(['cartao_id'=>1,'espaco_id'=>5,'numero_questao'=>1,'alternativa'=>'A','ambigua'=>false]);
        DB::table('notas')->insert(['id'=>1,'cartao_id'=>1,'prova_id'=>2,'aluno_id'=>1,'turma_id'=>1,'espaco_id'=>5,
            'acertos'=>1,'total_questoes'=>1,'nota_final'=>10,'status_aprovacao'=>'aprovado','acertos_por_tema'=>'{"SEGREDO":1}']);
    }
    private function ator(int $v): Usuario { return Usuario::find($v===1?1:2)->noVinculo($v); }
    private function prazo(): CarbonImmutable { return CarbonImmutable::now()->startOfHour()->addHours(2); }
    private function criar(?string $chave=null,?array $deps=null): string
    {
        if (!DB::table('notas')->exists()) $this->resultado();
        return (new LotesResultadosCopia)->criar($this->ator(2),5,7,$chave??(string)Str::uuid(),[1],$deps??['provas'=>[2],'alunos'=>[1],'turmas'=>[1]]);
    }
    private function previa(string $id): array { return (new LotesResultadosCopia)->previa($this->ator(2),$id,'origem'); }
    private function evidencia(string $id): string
    {
        return (new LotesResultadosCopia)->revisarEvidencia($this->ator(2),$id,$this->previa($id)['conteudo_digest'],
            'DOCUMENTO-FICTICIO: finalidade, categorias e espaços revisados','Prazo conforme contrato fictício','Exceções documentadas',true,true,$this->prazo());
    }
    private function aprovar(string $id,string $lado='origem'): string
    {
        return (new LotesResultadosCopia)->aprovar($this->ator($lado==='origem'?2:1),$id,$lado,$this->previa($id)['conteudo_digest'],true,$this->prazo());
    }
    private function recusa(int $status,callable $f): void
    {
        try { $f(); $this->fail('Deveria recusar.'); } catch(HttpException $e) { $this->assertSame($status,$e->getStatusCode()); }
    }
    private function lista(int $v,string $lado='origem',?int $espaco=null,int $pagina=1): array
    {
        config(['copias.interface_ativa'=>true]);
        $ator=$v===5?Usuario::find(3)->noVinculo(5):$this->ator($v);
        return (new AcompanhamentoCopias)->listar($ator,$lado,$espaco,$pagina);
    }
    public function test_origem_destino_e_minimizacao(): void
    {
        $id=$this->criar();
        $r=$this->lista(2); $this->assertSame(1,$r['lista']['total']); $this->assertSame($id,$r['lista']['data'][0]['id']);
        $this->assertSame(0,$this->lista(1,'destino')['lista']['total']);
        $this->evidencia($id); $this->aprovar($id);
        $this->assertSame(0,$this->lista(1,'destino')['lista']['total']);
        $this->aprovar($id,'destino'); $r=$this->lista(1,'destino'); $this->assertSame(1,$r['lista']['total']);
        foreach (['NOME-ESCOLAR','MAT-','DOCUMENTO','conteudo_digest','documento_cifrado','origem_espaco_id','destino_espaco_id','aluno_id','solicitante_id'] as $s)
            $this->assertStringNotContainsString($s,json_encode($r));
        $this->assertTrue($r['lista']['data'][0]['auditoria_pendente']); $this->assertSame(0,$r['lista']['data'][0]['publicados']);
        $this->assertDatabaseCount('copias_execucoes',0);
    }
    public function test_mesma_identidade_outro_vinculo_rede_e_flags(): void
    {
        $this->criar(); $this->assertSame(0,$this->lista(4)['lista']['total']);
        $this->recusa(404,fn()=>$this->lista(4,'origem',5));
        $r=$this->lista(5); $this->assertSame([5],array_column($r['espacos'],'id')); $this->assertSame(1,$r['lista']['total']);
        $this->recusa(404,fn()=>$this->lista(5,'origem',6));
        $this->recusa(403,fn()=>(new AcompanhamentoCopias)->listar(Usuario::find(1)->noVinculo(3),'origem',6));
        config(['copias.interface_ativa'=>false]); $this->recusa(404,fn()=>(new AcompanhamentoCopias)->listar($this->ator(2),'origem',5));
        config(['copias.interface_ativa'=>true]); DB::table('usuario_vinculos')->where('id',2)->update(['situacao'=>'encerrado']);
        $this->recusa(403,fn()=>$this->lista(2));
    }
    public function test_paginacao_so_conta_lotes_no_escopo(): void
    {
        for ($i=0;$i<21;$i++) $this->criar();
        $r=$this->lista(2); $this->assertCount(20,$r['lista']['data']); $this->assertSame(21,$r['lista']['total']);
        $r=$this->lista(2,'origem',5,2); $this->assertCount(1,$r['lista']['data']); $this->assertSame(2,$r['lista']['current_page']);
        $this->assertSame(0,$this->lista(4,'origem',6,2)['lista']['total']);
    }
    public function test_rota_usa_token_contextual_e_recusa_identidade_injetada(): void
    {
        $this->criar(); config(['copias.interface_ativa'=>true]); $u=Usuario::find(2);
        $pai=$u->createToken('api-token')->accessToken;
        $token=$u->createToken('vinculo-contexto',['vinculo:2','identidade:'.$pai->id])->plainTextToken;
        $headers=['Authorization'=>'Bearer '.$token,'X-Vinculo-Id'=>'4']; app('auth')->forgetGuards();
        $this->getJson('/api/v1/copias?lado=origem',$headers)->assertOk()->assertJsonPath('data.lista.total',1);
        app('auth')->forgetGuards();
        $this->getJson('/api/v1/copias?lado=origem&vinculo_id=4',$headers)->assertUnprocessable();
        app('auth')->forgetGuards();
        $this->getJson('/api/v1/copias?lado=origem&espaco_id=6',$headers)->assertNotFound();
    }
}
