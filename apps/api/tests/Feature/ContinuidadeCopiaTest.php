<?php
namespace Tests\Feature;

use App\Http\Controllers\Api\GestaoVinculoController;
use App\Jobs\{ProcessarCopiaMaterial,ProcessarCopiaCadastro};
use App\Models\Usuario;
use App\Services\{ContinuidadeCopia,ConviteVinculo,EncerrarVinculoProfessor,LotesCopia,LotesCadastroCopia,OutboxCopia};
use App\Support\ContextoVinculo;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Schema};
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ContinuidadeCopiaTest extends TestCase
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
    private function ator(int $v): Usuario { return Usuario::find(in_array($v,[1,3],true)?1:($v===5?3:2))->noVinculo($v); }
    private function convite(bool $aceitar=true): int
    {
        $s=new ConviteVinculo; $c=$s->emitir($this->ator(2),'d1-1@example.test',5,'professor',[1]); $this->assertNotNull($c);
        $s->responder(Usuario::find(1),$c['convite_id'],$c['token'],$aceitar); return $c['vinculo_id'];
    }
    private function recusa(int $status,callable $f): void
    { try { $f(); $this->fail('Deveria recusar.'); } catch(HttpException $e) { $this->assertSame($status,$e->getStatusCode()); } }
    private function auditar(): void { foreach (DB::table('copias_outbox')->whereNull('entregue_em')->pluck('id') as $id) (new OutboxCopia)->entregar($id); }
    private function material(int $oV,int $dV,int $o,int $d,int $prova): string
    {
        $s=new LotesCopia; $id=$s->criar($this->ator($oV),$o,$d,(string)Str::uuid(),[$prova]);
        $p=$s->previaMaterial($this->ator($oV),$id); $s->revisarMaterial($this->ator($oV),$id,$p['conteudo_digest'],true);
        $s->aprovar($this->ator($oV),$id,'origem',CarbonImmutable::now()->addHour());
        $s->aprovar($this->ator($dV),$id,'destino',CarbonImmutable::now()->addHour());
        $this->auditar(); $s->solicitarExecucao($this->ator($dV),$id); $this->auditar(); return $id;
    }
    private function concluirMaterial(string $id): void
    { (new ProcessarCopiaMaterial($id))->handle(); $this->auditar(); (new ProcessarCopiaMaterial($id))->handle(); $this->assertDatabaseHas('copias_lotes',['id'=>$id,'estado'=>'concluido']); }
    public function test_caso_1_convite_real_nao_copia_e_aceite_apenas_prepara_elegibilidade(): void
    {
        $a=new ContinuidadeCopia;
        $this->assertFalse($a->avaliar($this->ator(1),$this->ator(2),7,5)['modalidades']['material']['preparacao_disponivel']);
        $s=new ConviteVinculo; $c=$s->emitir($this->ator(2),'d1-1@example.test',5,'professor',[1]);
        $this->assertSame('convidado',$a->avaliar($this->ator(1),$this->ator(2),7,5)['situacao_institucional']);
        $s->responder(Usuario::find(1),$c['convite_id'],$c['token'],true);
        $r=$a->avaliar($this->ator(1),$this->ator(2),7,5);
        $this->assertTrue($r['modalidades']['material']['preparacao_disponivel']); $this->assertFalse($r['autoriza_execucao']); $this->assertFalse($r['execucao_automatica']);
        foreach (['copias_lotes','copias_execucoes','registros_origem'] as $t) $this->assertDatabaseCount($t,0);
        $id=$this->material(1,2,7,5,1); $this->concluirMaterial($id);
        $this->assertDatabaseHas('provas',['id'=>1,'espaco_id'=>7]);
        $mapa=DB::table('registros_origem')->where('lote_id',$id)->first(); $this->assertNotEquals(1,$mapa->destino_id);
        $this->assertDatabaseHas('provas',['id'=>$mapa->destino_id,'espaco_id'=>5]);
        $cad=new LotesCadastroCopia; $cId=$cad->criar($this->ator(1),7,5,(string)Str::uuid(),[3],[3]);
        $this->assertSame('data_nascimento',$cad->previaOrigem($this->ator(1),$cId)['pendencias'][0]['campo']);
    }
    public function test_caso_2_autoria_e_contexto_de_outra_escola_nao_concedem_saida(): void
    {
        $v=$this->convite(); $a=new ContinuidadeCopia;
        $this->recusa(403,fn()=>$a->avaliar(Usuario::find(1)->noVinculo($v),$this->ator(1),5,7));
        $this->recusa(403,fn()=>$a->avaliar($this->ator(3),$this->ator(1),5,7));
        $this->recusa(403,fn()=>$a->avaliar($this->ator(4),$this->ator(1),5,7));
        $this->recusa(403,fn()=>(new LotesCopia)->criar($this->ator(1),5,7,(string)Str::uuid(),[2]));
        $this->assertDatabaseHas('provas',['id'=>2,'espaco_id'=>5,'criado_por'=>1]); $this->assertDatabaseCount('copias_lotes',0);
    }
    public function test_caso_3_saida_preserva_vinculo_encerrado_e_gestor_atual_pode_copiar_minimo(): void
    {
        $v=$this->convite(); (new EncerrarVinculoProfessor)->executar($this->ator(2),$v);
        $this->recusa(403,fn()=>ContextoVinculo::resolver(Usuario::find(1)->noVinculo($v)));
        $r=(new ContinuidadeCopia)->avaliar($this->ator(2),$this->ator(1),5,7);
        $this->assertSame('encerrado',$r['situacao_institucional']); $this->assertTrue($r['modalidades']['cadastro_minimo']['preparacao_disponivel']);
        $id=$this->material(2,1,5,7,2); $this->concluirMaterial($id);
        DB::table('prova_turmas')->insert(['prova_id'=>2,'turma_id'=>1,'espaco_id'=>5]);
        DB::table('cartoes')->insert(['id'=>1,'prova_id'=>2,'aluno_id'=>1,'espaco_id'=>5,'imagem_url'=>'privado/ficticio.jpg']);
        DB::table('notas')->insert(['cartao_id'=>1,'prova_id'=>2,'aluno_id'=>1,'turma_id'=>1,'espaco_id'=>5,'acertos'=>1,'total_questoes'=>1,'nota_final'=>10,'status_aprovacao'=>'aprovado']);
        $s=new LotesCadastroCopia; $cad=$s->criar($this->ator(2),5,7,(string)Str::uuid(),[1],[1]); $p=$s->previaOrigem($this->ator(2),$cad);
        $s->revisarEvidencia($this->ator(2),$cad,$p['conteudo_digest'],'EVIDENCIA FICTICIA DE CADASTRO',true,CarbonImmutable::now()->addDay());
        $s->aprovar($this->ator(2),$cad,'origem',CarbonImmutable::now()->addHour()); $c=$s->conciliar($this->ator(1),$cad);
        $s->aprovar($this->ator(1),$cad,'destino',CarbonImmutable::now()->addHour(),$c['digest']);
        $this->auditar(); $s->solicitarExecucao($this->ator(1),$cad); $this->auditar();
        (new ProcessarCopiaCadastro($cad))->handle(); $this->auditar(); (new ProcessarCopiaCadastro($cad))->handle();
        $this->assertDatabaseHas('copias_lotes',['id'=>$cad,'estado'=>'concluido']);
        $this->assertDatabaseHas('alunos',['espaco_id'=>7,'matricula'=>'MAT-1','data_nascimento'=>null]);
        $this->assertDatabaseHas('alunos',['id'=>1,'espaco_id'=>5]); $this->assertDatabaseCount('cartoes',1); $this->assertDatabaseCount('notas',1);
        $this->assertDatabaseHas('usuario_vinculos',['id'=>$v,'situacao'=>'encerrado']);
        $this->recusa(403,fn()=>ContextoVinculo::resolver(Usuario::find(1)->noVinculo($v)));
        $this->assertFalse((new ContinuidadeCopia)->avaliar($this->ator(2),$this->ator(1),5,7)['modalidades']['resultados_identificados']['preparacao_disponivel']);
    }
    public function test_caso_4_saida_sem_continuidade_nao_apaga_nem_inicia_copia_e_revogacao_bloqueia_job(): void
    {
        $v=$this->convite(); $s=new EncerrarVinculoProfessor; $s->executar($this->ator(2),$v); $s->executar($this->ator(2),$v);
        $this->assertSame(1,DB::table('vinculo_eventos')->where('vinculo_id',$v)->where('acao','encerrado')->count());
        foreach (['copias_lotes','copias_execucoes','registros_origem'] as $t) $this->assertDatabaseCount($t,0);
        $this->assertDatabaseCount('alunos',3); $this->assertDatabaseCount('provas',3);
        $this->assertSame(7,(int)ContextoVinculo::resolver($this->ator(1))->espaco_id);
        $this->assertSame(6,(int)ContextoVinculo::resolver($this->ator(3))->espaco_id);
        $id=$this->material(2,1,5,7,2); $this->assertSame('preparado',(new LotesCopia)->executarPasso($id));
        $r=Request::create('/','POST',['vinculo_id'=>2]); $r->setUserResolver(fn()=>$this->ator(5));
        (new GestaoVinculoController)->toggle($r,2);
        $this->assertSame('bloqueado',(new LotesCopia)->executarPasso($id));
        $this->assertDatabaseCount('registros_origem',0); $this->assertDatabaseCount('provas',3);
    }
    public function test_caso_5_encerramento_e_suspensao_de_ancestral_bloqueiam_diretor_e_rede(): void
    {
        $this->convite(); $a=new ContinuidadeCopia;
        foreach ([['espacos',5,'encerrado'],['espacos',3,'suspenso'],['clientes',1,'encerrado']] as [$t,$id,$estado]) {
            DB::table($t)->where('id',$id)->update(['situacao'=>$estado]);
            foreach ([2,5] as $v) $this->recusa(403,fn()=>$a->avaliar($this->ator($v),$this->ator(1),5,7));
            $this->recusa(403,fn()=>(new LotesCopia)->criar($this->ator(5),5,7,(string)Str::uuid(),[2]));
            DB::table($t)->where('id',$id)->update(['situacao'=>'ativo']);
        }
        $this->assertDatabaseCount('provas',3); $this->assertDatabaseCount('alunos',3); $this->assertDatabaseCount('copias_lotes',0);
    }
    public function test_caso_6_pagamento_pessoal_nao_desbloqueia_escola_ou_modalidades_pendentes(): void
    {
        $v=$this->convite(); (new EncerrarVinculoProfessor)->executar($this->ator(2),$v);
        DB::table('clientes')->where('id',3)->update(['modalidade'=>'pagante']);
        $a=new ContinuidadeCopia; $r=$a->avaliar($this->ator(2),$this->ator(1),5,7);
        foreach (['resultados_identificados','estatistica_anonima'] as $tipo) $this->assertFalse($r['modalidades'][$tipo]['preparacao_disponivel']);
        $this->assertStringNotContainsString('NOME-ESCOLAR',json_encode($r)); $this->assertStringNotContainsString('MAT-',json_encode($r));
        DB::table('espacos')->where('id',5)->update(['situacao'=>'encerrado']);
        $this->assertSame(7,(int)ContextoVinculo::resolver($this->ator(1))->espaco_id);
        $this->recusa(403,fn()=>$a->avaliar($this->ator(2),$this->ator(1),5,7));
        $this->assertDatabaseCount('copias_lotes',0);
    }
    public function test_convite_recusado_nao_se_confunde_com_saida_apos_vinculo_ativo(): void
    {
        $this->convite(false); $a=new ContinuidadeCopia;
        foreach ([[$this->ator(1),$this->ator(2),7,5],[$this->ator(2),$this->ator(1),5,7]] as [$o,$d,$oe,$de]) {
            $r=$a->avaliar($o,$d,$oe,$de); $this->assertSame('convite_recusado',$r['situacao_institucional']);
            $this->assertFalse($r['modalidades']['material']['preparacao_disponivel']);
        }
        $this->assertDatabaseHas('usuarios',['id'=>1,'ativo'=>true]); $this->assertDatabaseCount('copias_lotes',0);
    }
    public function test_encerramento_exige_gestor_atual_alcance_e_tipo_de_vinculo(): void
    {
        $v=$this->convite(); $s=new EncerrarVinculoProfessor;
        $this->recusa(403,fn()=>$s->executar($this->ator(1),$v));
        $this->recusa(403,fn()=>$s->executar($this->ator(2),3));
        $this->recusa(403,fn()=>$s->executar($this->ator(2),2));
        DB::table('vinculo_permissoes')->insert(['vinculo_id'=>2,'permissao'=>'usuario.gerir','efeito'=>'retirar']);
        $this->recusa(403,fn()=>$s->executar($this->ator(2),$v));
        $this->assertDatabaseHas('usuario_vinculos',['id'=>$v,'situacao'=>'ativo']);
    }
    public function test_avaliacao_reconsulta_vigencia_flags_e_nao_grava(): void
    {
        $v=$this->convite(); $a=new ContinuidadeCopia;
        DB::table('usuario_vinculos')->where('id',$v)->update(['fim_em'=>now()->subMinute()]);
        $r=$a->avaliar($this->ator(1),$this->ator(2),7,5);
        $this->assertSame('fora_vigencia',$r['situacao_institucional']); $this->assertFalse($r['modalidades']['material']['preparacao_disponivel']);
        foreach (['copias.ativo','vinculos.obrigatorio','espacos.ativo'] as $flag) {
            config([$flag=>false]); $this->recusa(403,fn()=>$a->avaliar($this->ator(1),$this->ator(2),7,5)); config([$flag=>true]);
        }
        $this->assertDatabaseCount('copias_lotes',0); $this->assertDatabaseCount('registros_origem',0);
    }

    public function test_falha_no_historico_reverte_encerramento_e_versao(): void
    {
        $v=$this->convite(); $antes=DB::table('usuario_vinculos')->find($v); $eventos=DB::table('vinculo_eventos')->count(); $falhou=false;
        DB::listen(function($q) use(&$falhou) {
            if (!$falhou && str_starts_with($q->sql,'insert into') && str_contains($q->sql,'vinculo_eventos')) {
                $falhou=true; throw new \RuntimeException('falha fictícia no histórico');
            }
        });
        try { (new EncerrarVinculoProfessor)->executar($this->ator(2),$v); $this->fail('Deveria falhar.'); }
        catch (\RuntimeException $e) { $this->assertSame('falha fictícia no histórico',$e->getMessage()); }
        $this->assertDatabaseHas('usuario_vinculos',['id'=>$v,'situacao'=>'ativo','versao_autorizacao'=>$antes->versao_autorizacao,'fim_em'=>null]);
        $this->assertDatabaseCount('vinculo_eventos',$eventos);
        (new EncerrarVinculoProfessor)->executar($this->ator(2),$v);
        $this->assertDatabaseHas('usuario_vinculos',['id'=>$v,'situacao'=>'encerrado','versao_autorizacao'=>(int)$antes->versao_autorizacao+1]);
    }
}
