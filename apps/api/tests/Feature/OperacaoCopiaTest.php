<?php
namespace Tests\Feature;

use App\Models\Usuario;
use App\Services\{LotesResultadosCopia,LotesCopia,LotesCadastroCopia,RecebimentosCopia,OperacaoCopia,OutboxCopia};
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\{Crypt,Queue};
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\{DB,Schema};
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class OperacaoCopiaTest extends TestCase
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
    private function ator(int $v=2): Usuario { return Usuario::find($v===1?1:2)->noVinculo($v); }
    private function recusa(int $status,callable $f): void
    { try { $f(); $this->fail('Deveria recusar.'); } catch(HttpException $e) { $this->assertSame($status,$e->getStatusCode()); } }
    private function preparar(string $tipo='preparacao_material',bool $decidir=true): array
    {
        config(['copias.interface_ativa'=>true,'copias.resultados_execucao_ativa'=>true]); Queue::fake();
        $o=$this->ator(2); $d=$this->ator(1); $r=new RecebimentosCopia($tipo); $code=$r->emitir($d,7)['codigo'];
        $dest=$r->consultar($o,5,$code); $chave=(string)Str::uuid(); $ate=CarbonImmutable::now()->addHour();
        if($tipo==='preparacao_material') {
            $s=new LotesCopia; $p=(new \App\Services\SelecaoMaterialCopia)->previa($o,5,[2]);
            $id=$s->criarViaRecebimento($o,5,$code,$dest['destino_digest'],$p['conteudo_digest'],$chave,[2]);
            if($decidir) { $s->revisarMaterial($o,$id,$s->previaMaterial($o,$id)['conteudo_digest'],true); $s->aprovar($o,$id,'origem',$ate); $s->aprovar($d,$id,'destino',$ate); }
        } elseif($tipo==='cadastro_minimo') {
            $s=new LotesCadastroCopia; $p=(new \App\Services\SelecaoCadastroCopia)->previa($o,5,'pessoal',[1],[1]);
            $id=$s->criarViaRecebimento($o,5,$code,$dest['destino_digest'],$p['conteudo_digest'],$chave,[1],[1]);
            if($decidir) {
                $p=$s->previaOrigem($o,$id); $s->revisarEvidencia($o,$id,$p['conteudo_digest'],'DOCUMENTO RESTRITO',true,$ate);
                $s->aprovar($o,$id,'origem',$ate); $s->aprovar($d,$id,'destino',$ate,$s->conciliar($d,$id)['digest']);
            }
        } else {
            $this->resultado(); $s=new LotesResultadosCopia; $p=$s->prepararInterface($o,5,$code,[1]);
            $id=$s->criarViaRecebimento($o,5,$code,$dest['destino_digest'],$p['conteudo_digest'],$chave,[1],$p['dependencias']);
            if($decidir) {
                $s->revisarEvidencia($o,$id,$p['conteudo_digest'],'DOCUMENTO RESTRITO','Contrato fictício','Exceção documentada',true,true,$ate);
                $s->aprovar($o,$id,'origem',$p['conteudo_digest'],true,$ate); $s->aprovar($d,$id,'destino',$p['conteudo_digest'],true,$ate);
            }
        }
        return [$id,$s];
    }
    private function auditar(): void
    { foreach(DB::table('copias_outbox')->whereNull('entregue_em')->pluck('id') as $id) (new OutboxCopia)->entregar($id); }
    private function headers(int $v=1): array
    {
        $u=Usuario::find($v===1?1:2); $pai=$u->createToken('api-token')->accessToken;
        return ['Authorization'=>'Bearer '.$u->createToken('vinculo-contexto',['vinculo:'.$v,'identidade:'.$pai->id])->plainTextToken];
    }
    #[\PHPUnit\Framework\Attributes\DataProvider('modalidades')]
    public function test_tres_modalidades_solicitam_repetem_sem_duplicar_e_publicam(string $tipo): void
    {
        $op=new OperacaoCopia;

            [$id,$s]=$this->preparar($tipo);
            $this->assertFalse($op->consultar($this->ator(1),$id,'destino')['pode_solicitar']);
            $this->recusa(409,fn()=>$op->solicitar($this->ator(1),$id)); $this->assertDatabaseCount('copias_execucoes',0);
            $this->auditar(); $this->assertTrue($op->consultar($this->ator(1),$id,'destino')['pode_solicitar']);
            $op->solicitar($this->ator(1),$id); $op->solicitar($this->ator(1),$id); $this->assertDatabaseCount('copias_execucoes',1);
            $this->assertDatabaseHas('copias_execucoes',['lote_id'=>$id,'estado'=>'pendente']);
            for($i=0;$i<10;$i++) { $this->auditar(); if($s->executarPasso($id)==='concluido') break; }
            $this->assertDatabaseHas('copias_lotes',['id'=>$id,'estado'=>'concluido']);
            $this->assertFalse($op->consultar($this->ator(1),$id,'destino')['pode_revogar']);
            $this->recusa(409,fn()=>$op->revogar($this->ator(2),$id,'origem'));
        Queue::assertNothingPushed();
    }
    public static function modalidades(): array { return [['preparacao_material'],['cadastro_minimo'],['resultados_identificados']]; }
    public function test_acervo_minimo_independente_da_origem_e_restrito_ao_destino(): void
    {
        [$id,$s]=$this->preparar('resultados_identificados'); $op=new OperacaoCopia;
        $this->recusa(404,fn()=>$op->acervo($this->ator(1),$id)); $this->auditar(); $op->solicitar($this->ator(1),$id);
        for($i=0;$i<5;$i++) { $this->auditar(); if($s->executarPasso($id)==='concluido') break; }
        DB::table('usuario_vinculos')->where('id',2)->update(['situacao'=>'encerrado']); DB::table('notas')->where('id',1)->update(['nota_final'=>2]);
        $p=$op->acervo($this->ator(1),$id); $json=json_encode($p);
        $this->assertEquals(10,$p['pacote']['resultados'][0]['dados']['nota_final']);
        foreach(['origem_id','origem_espaco_id','cartao_id','SEGREDO','DOCUMENTO RESTRITO','solicitante'] as $segredo) $this->assertStringNotContainsString($segredo,$json);
        $this->assertSame('legado_sem_versao_original',$p['pacote']['condicao_historica']);
        $this->recusa(403,fn()=>$op->acervo($this->ator(4),$id));
        DB::table('acervo_resultados')->update(['conteudo_cifrado'=>'corrompido']); $this->recusa(409,fn()=>$op->acervo($this->ator(1),$id));
    }
    public function test_revogacao_bloqueia_sem_reabrir_ou_apagar_publicados(): void
    {
        [$id,$s]=$this->preparar(); $op=new OperacaoCopia; $this->auditar(); $op->solicitar($this->ator(1),$id);
        $this->auditar(); $s->executarPasso($id); $op->revogar($this->ator(2),$id,'origem'); $op->revogar($this->ator(2),$id,'origem');
        $this->assertDatabaseHas('copias_lotes',['id'=>$id,'estado'=>'bloqueado']); $this->recusa(409,fn()=>$op->solicitar($this->ator(1),$id));
        $this->assertSame('bloqueado',$s->executarPasso($id)); $this->assertDatabaseCount('provas',3);
    }
    public function test_http_confirmacao_ator_contexto_e_flags(): void
    {
        [$id]=$this->preparar(); $url='/api/v1/copias/operacao/'.$id; $h=$this->headers(); $op=new OperacaoCopia;
        foreach([[],['confirmado'=>true,'ator_id'=>2],['confirmado'=>true,'lado'=>'origem']] as $d) {
            app('auth')->forgetGuards(); $this->postJson($url.'/solicitar',$d,$h)->assertUnprocessable();
        }
        $origem=$this->headers(2); app('auth')->forgetGuards(); $this->postJson($url.'/solicitar',['confirmado'=>true],$origem)->assertForbidden();
        $this->auditar(); config(['copias.execucao_ativa'=>false]); $this->recusa(403,fn()=>$op->solicitar($this->ator(1),$id));
        config(['copias.execucao_ativa'=>true]); app('auth')->forgetGuards(); $this->postJson($url.'/solicitar',['confirmado'=>true],$h)->assertStatus(202);
        app('auth')->forgetGuards(); $this->getJson($url.'?lado=destino',$h)->assertOk()->assertJsonPath('data.execucao_solicitada',true)->assertJsonMissingPath('data.ator_id');
        config(['copias.interface_ativa'=>false]); $this->recusa(404,fn()=>$op->consultar($this->ator(1),$id,'destino'));
    }
    public function test_detalhe_nao_divulga_antes_da_decisao_nem_aceita_lote_interno(): void
    {
        [$id]=$this->preparar('resultados_identificados',false); $op=new OperacaoCopia;
        $this->recusa(404,fn()=>$op->consultar($this->ator(1),$id,'destino'));
        $this->recusa(403,fn()=>$op->consultar($this->ator(4),$id,'origem'));
        $antes=DB::table('copias_outbox')->count(); $p=$op->consultar($this->ator(2),$id,'origem');
        $this->assertSame($antes,DB::table('copias_outbox')->count()); $this->assertFalse($p['autoriza_execucao']);
        DB::table('copias_recebimentos')->where('lote_id',$id)->update(['finalidade'=>'cadastro_minimo']);
        $this->recusa(404,fn()=>$op->consultar($this->ator(2),$id,'origem'));
    }
    public function test_fonte_alterada_recusa_processamento_de_resultados(): void
    {
        [$id]=$this->preparar('resultados_identificados'); $this->auditar();
        DB::table('cartao_respostas')->where('cartao_id',1)->update(['alternativa'=>'B']);
        $this->recusa(409,fn()=>(new OperacaoCopia)->solicitar($this->ator(1),$id)); $this->assertDatabaseCount('copias_execucoes',0);
    }
}