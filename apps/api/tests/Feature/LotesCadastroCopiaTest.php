<?php
namespace Tests\Feature;

use App\Models\Usuario;
use App\Services\{LotesCadastroCopia,LotesCopia,OutboxCopia};
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\{Crypt,DB,Schema};
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class LotesCadastroCopiaTest extends TestCase
{
    private LotesCadastroCopia $s;
    protected function setUp(): void
    {
        parent::setUp(); $db=DB::connection();
        if (!(($db->getDriverName()==='sqlite' && $db->getDatabaseName()===':memory:')
            || (in_array($db->getDriverName(),['mysql','mariadb'],true) && str_ends_with($db->getDatabaseName(),'_test')))) throw new \RuntimeException('Banco descartável obrigatório.');
        RefreshDatabaseState::$migrated=false;
        $this->artisan('migrate:fresh',['--force'=>true])->assertSuccessful();
        // Reproduz a unicidade pós-corte T2. migrate:fresh mantém a unicidade global legada.
        Schema::table('alunos',function($t) { $t->unique(['espaco_id','matricula'],'t2_alunos_matricula'); $t->dropUnique('alunos_matricula_unique'); });
        config(['copias.ativo'=>true,'copias.auditoria_entrega'=>true,'copias.execucao_ativa'=>true,
            'espacos.ativo'=>true,'espacos.pessoal'=>true,'vinculos.obrigatorio'=>true]);
        foreach ([1,2] as $id) {
            Usuario::forceCreate(['id'=>$id,'perfil'=>'professor','nome'=>'Fictício','email'=>"cadastro-$id@example.test",
                'cpf'=>sprintf('%011d',$id),'password'=>'ficticio','ativo'=>true]);
            DB::table('clientes')->insert(['id'=>$id,'tipo'=>'professor','nome'=>'Fictício','modalidade'=>'interno','situacao'=>'ativo']);
            DB::table('espacos')->insert(['id'=>$id,'cliente_id'=>$id,'tipo'=>'pessoal','nome'=>'Pessoal','situacao'=>'ativo','usuario_proprietario_id'=>$id]);
            DB::table('usuario_vinculos')->insert(['id'=>$id,'usuario_id'=>$id,'espaco_id'=>$id,'perfil'=>'professor_autonomo',
                'situacao'=>'ativo','inicio_em'=>now()->subDay(),'versao_autorizacao'=>1]);
        }
        $this->turma(1,1); $this->aluno(1,1,1,'MAT-1'); $this->s=new LotesCadastroCopia;
    }
    private function turma(int $id,int $e): void
    {
        DB::table('turmas')->insert(['id'=>$id,'espaco_id'=>$e,'espaco_tipo'=>'pessoal','escola_id'=>null,
            'nome'=>'Turma fictícia','serie'=>'3','turno'=>'manha','ano_letivo'=>2026]);
    }
    private function aluno(int $id,int $e,int $t,string $m): void
    {
        DB::table('alunos')->insert(['id'=>$id,'espaco_id'=>$e,'espaco_tipo'=>'pessoal','turma_id'=>$t,
            'nome'=>'Aluno confidencial fictício','matricula'=>$m,'avatar_id'=>3]);
    }
    private function ator(int $id=1): Usuario { return Usuario::find($id)->noVinculo($id); }
    private function criar(?string $chave=null,int $destino=2): string { return $this->s->criar($this->ator(),1,$destino,$chave??(string)Str::uuid(),[1],[1]); }
    private function revisar(string $id,?CarbonImmutable $ate=null): string
    {
        $p=$this->s->previaOrigem($this->ator(),$id);
        return $this->s->revisarEvidencia($this->ator(),$id,$p['conteudo_digest'],'DOCUMENTO FICTÍCIO RESTRITO: revisão aplicável à seleção.',true,$ate??CarbonImmutable::now()->addDay());
    }
    private function saida(string $id): void { $this->revisar($id); $this->s->aprovar($this->ator(),$id,'origem',CarbonImmutable::now()->addHour()); }
    private function aceitar(string $id): void
    {
        $r=$this->s->conciliar($this->ator(2),$id);
        $this->s->aprovar($this->ator(2),$id,'destino',CarbonImmutable::now()->addHour(),$r['digest']);
    }
    private function auditar(): void { foreach (DB::table('copias_outbox')->whereNull('entregue_em')->pluck('id') as $id) (new OutboxCopia)->entregar($id); }
    private function recusa(int $status,callable $f): void
    {
        try { $f(); $this->fail('Deveria recusar.'); } catch (HttpException $e) { $this->assertSame($status,$e->getStatusCode()); }
    }
    private function restricao(callable $f): void
    {
        try { $f(); $this->fail('O banco deveria recusar.'); } catch (QueryException $e) { $this->assertNotEmpty($e->errorInfo); }
    }
    public function test_fluxo_duas_decisoes_evidencia_cifrada_auditoria_e_sem_execucao(): void
    {
        $id=$this->criar(); $this->saida($id); $this->aceitar($id);
        $e=DB::table('copias_evidencias')->first();
        $this->assertStringNotContainsString('DOCUMENTO',$e->documento_cifrado);
        $this->assertStringContainsString('DOCUMENTO',Crypt::decryptString($e->documento_cifrado));
        $this->assertSame(['nome','matricula','avatar_id','ativo'],json_decode($e->categorias,true)['alunos']);
        $this->assertFalse($this->s->revalidar($this->ator(2),$id)); $this->auditar();
        $this->assertTrue($this->s->revalidar($this->ator(2),$id));
        $this->assertDatabaseCount('copias_outbox',4); $this->assertDatabaseCount('copias_execucoes',0);
        $this->assertDatabaseCount('alunos',1); $this->assertDatabaseCount('registros_origem',0);
        foreach (['copias_itens','copias_lotes','copias_cadastros','copias_outbox'] as $t) {
            $json=json_encode(DB::table($t)->get()); $this->assertStringNotContainsString('DOCUMENTO',$json);
            $this->assertStringNotContainsString('Aluno confidencial',$json); $this->assertStringNotContainsString('MAT-1',$json);
        }
        $this->recusa(404,fn()=>(new LotesCopia)->solicitarExecucao($this->ator(2),$id));
        $this->recusa(404,fn()=>(new LotesCopia)->executarPasso($id));
    }
    public function test_sem_evidencia_ou_saida_nao_ha_consulta_destino_nem_aceite(): void
    {
        $id=$this->criar();
        $this->recusa(409,fn()=>$this->s->conciliar($this->ator(2),$id));
        $this->recusa(409,fn()=>$this->s->aprovar($this->ator(),$id,'origem',CarbonImmutable::now()->addHour()));
        $p=$this->s->previaOrigem($this->ator(),$id);
        foreach ([['',true],['documento',false],['https://example.test/contrato',true]] as [$doc,$confirma]) {
            $this->recusa(422,fn()=>$this->s->revisarEvidencia($this->ator(),$id,$p['conteudo_digest'],$doc,$confirma,CarbonImmutable::now()->addDay()));
        }
        $this->revisar($id);
        $this->recusa(409,fn()=>$this->s->conciliar($this->ator(2),$id));
        $this->recusa(403,fn()=>$this->s->previaOrigem($this->ator(2),$id));
        $this->recusa(403,fn()=>$this->s->aprovar($this->ator(),$id,'destino',CarbonImmutable::now()->addHour()));
    }
    public function test_repeticao_nao_renova_evidencia_decisao_ou_selecao(): void
    {
        $chave=(string)Str::uuid(); $id=$this->criar($chave); $this->assertSame($id,$this->criar(strtoupper($chave)));
        $ate=CarbonImmutable::now()->addDay(); $eid=$this->revisar($id,$ate); $this->assertSame($eid,$this->revisar($id,$ate));
        $this->recusa(409,fn()=>$this->revisar($id,$ate->addHour()));
        $validade=CarbonImmutable::now()->addHour(); $d=$this->s->aprovar($this->ator(),$id,'origem',$validade);
        $this->assertSame($d,$this->s->aprovar($this->ator(),$id,'origem',$validade));
        $this->recusa(422,fn()=>$this->s->aprovar($this->ator(2),$id,'destino',$ate->addDay(),'qualquer'));
        DB::table('alunos')->where('id',1)->update(['nome'=>'Alterado']);
        $this->assertSame($id,$this->criar($chave));
        $this->recusa(409,fn()=>$this->s->conciliar($this->ator(2),$id));
        $this->assertDatabaseCount('copias_outbox',3);
    }
    public function test_colisao_matricula_nao_revela_destino_nem_mescla(): void
    {
        $this->turma(2,2); $this->aluno(99,2,2,'MAT-1');
        DB::table('alunos')->where('id',99)->update(['nome'=>'NOME-SECRETO-DESTINO']);
        $id=$this->criar(); $this->saida($id); $r=$this->s->conciliar($this->ator(2),$id);
        $this->assertFalse($r['sem_bloqueios']); $this->assertSame('conflito_matricula',$r['itens'][1]['codigo']);
        $this->assertStringNotContainsString('NOME-SECRETO',json_encode($r)); $this->assertStringNotContainsString('99',json_encode($r['itens']));
        $this->recusa(409,fn()=>$this->s->aprovar($this->ator(2),$id,'destino',CarbonImmutable::now()->addHour(),$r['digest']));
        $this->assertDatabaseCount('registros_origem',0); $this->assertDatabaseCount('alunos',2);
    }
    private function mapas(string $lote): void
    {
        $this->turma(2,2); $this->aluno(2,2,2,'ALTERADA-NO-DESTINO');
        foreach (['turma','aluno'] as $entidade) {
            DB::table('copias_itens')->where('lote_id',$lote)->where('entidade',$entidade)->update(['estado'=>'publicado']);
            DB::table('registros_origem')->insert(['lote_id'=>$lote,'entidade'=>$entidade,'origem_espaco_id'=>1,'origem_id'=>1,
                'destino_espaco_id'=>2,'destino_id'=>2,'copiado_por'=>2,'created_at'=>now()]);
        }
    }
    public function test_mapa_comprovado_preserva_edicao_e_dependencia_divergente_bloqueia(): void
    {
        $anterior=$this->criar(); $this->mapas($anterior);
        $id=$this->criar(); $this->saida($id); $r=$this->s->conciliar($this->ator(2),$id);
        $this->assertSame(['reutilizar_mapa','reutilizar_mapa'],array_column($r['itens'],'codigo')); $this->assertTrue($r['sem_bloqueios']);
        $this->aceitar($id); $this->auditar(); $this->assertTrue($this->s->revalidar($this->ator(2),$id));
        $this->assertDatabaseHas('alunos',['id'=>2,'matricula'=>'ALTERADA-NO-DESTINO']);
        $this->turma(3,2); DB::table('alunos')->where('id',2)->update(['turma_id'=>3]);
        $this->assertSame('dependencia_incompativel',$this->s->conciliar($this->ator(2),$id)['itens'][1]['codigo']);
        $this->assertFalse($this->s->revalidar($this->ator(2),$id));
    }
    public function test_conflito_apos_aceite_impede_revalidacao(): void
    {
        $id=$this->criar(); $this->saida($id); $this->aceitar($id); $this->auditar();
        $this->turma(2,2); $this->aluno(2,2,2,'MAT-1');
        $this->assertFalse($this->s->revalidar($this->ator(2),$id));
        $this->assertDatabaseHas('copias_lotes',['id'=>$id,'estado'=>'bloqueado']);
    }
    public function test_revogacao_expiracao_e_versao_invalidam_autorizacoes(): void
    {
        $id=$this->criar(); $this->saida($id); $this->aceitar($id); $this->auditar();
        DB::table('usuario_vinculos')->where('id',1)->increment('versao_autorizacao');
        $this->assertFalse($this->s->revalidar($this->ator(2),$id));
        $outro=$this->criar(); $this->saida($outro); $this->aceitar($outro);
        $this->s->revogar($this->ator(),$outro,'origem'); $this->s->revogar($this->ator(),$outro,'origem');
        $this->assertNotNull(DB::table('copias_evidencias')->where('lote_id',$outro)->value('revogada_em'));
        $this->recusa(409,fn()=>$this->s->conciliar($this->ator(2),$outro));
        $terceiro=$this->criar(); $this->saida($terceiro); $this->aceitar($terceiro);
        $this->travel(2)->days();
        $this->assertFalse($this->s->revalidar($this->ator(2),$terceiro)); $this->travelBack();
    }
    public function test_cifra_de_evidencia_corrompida_e_digest_forjado_sao_recusados(): void
    {
        $id=$this->criar();
        $this->recusa(409,fn()=>$this->s->revisarEvidencia($this->ator(),$id,str_repeat('0',64),'Documento fictício',true,CarbonImmutable::now()->addDay()));
        $this->saida($id); DB::table('copias_evidencias')->where('lote_id',$id)->update(['documento_cifrado'=>'corrompido']);
        $this->recusa(409,fn()=>$this->s->conciliar($this->ator(2),$id));
    }
    public function test_dados_faltantes_no_destino_escolar_bloqueiam_decisao(): void
    {
        DB::table('clientes')->insert(['id'=>3,'tipo'=>'rede','nome'=>'Rede','modalidade'=>'interno','situacao'=>'ativo']);
        DB::table('redes')->insert(['id'=>1,'nome'=>'Rede','tipo'=>'estadual','uf'=>'BA']);
        DB::table('nucleos')->insert(['id'=>1,'rede_id'=>1,'nome'=>'Núcleo']);
        DB::table('escolas')->insert(['id'=>1,'nucleo_id'=>1,'nome'=>'Escola','inep'=>'00000001','tipo_rede'=>'estadual']);
        foreach (['rede'=>3,'nucleo'=>4,'escola'=>5] as $tipo=>$i) DB::table('espacos')->insert(['id'=>$i,'cliente_id'=>3,'tipo'=>$tipo,'nome'=>$tipo,'situacao'=>'ativo',$tipo.'_id'=>1]);
        $id=$this->criar(destino:5); $p=$this->s->previaOrigem($this->ator(),$id);
        $this->assertSame('data_nascimento',$p['pendencias'][0]['campo']); $this->revisar($id);
        $this->recusa(409,fn()=>$this->s->aprovar($this->ator(),$id,'origem',CarbonImmutable::now()->addHour()));
    }
    public function test_fk_composta_e_mapa_unico_preservados_na_expansao(): void
    {
        $id=$this->criar(); $this->revisar($id); $this->mapas($id);
        $this->restricao(fn()=>DB::table('copias_evidencias')->where('lote_id',$id)->update(['ator_id'=>2]));
        $row=(array)DB::table('registros_origem')->where('entidade','aluno')->first(); unset($row['id']);
        $this->restricao(fn()=>DB::table('registros_origem')->insert($row));
        $this->restricao(fn()=>DB::table('registros_origem')->where('entidade','aluno')->update(['origem_id'=>999]));
    }
    public function test_falha_na_outbox_reverte_evidencia_sem_deixar_autorizacao(): void
    {
        $id=$this->criar(); $falhou=false;
        DB::listen(function($q) use (&$falhou) {
            if (!$falhou && str_starts_with($q->sql,'insert into') && str_contains($q->sql,'copias_outbox')) { $falhou=true; throw new \RuntimeException('falha fictícia'); }
        });
        try { $this->revisar($id); $this->fail('Deveria falhar.'); } catch (\RuntimeException $e) { $this->assertSame('falha fictícia',$e->getMessage()); }
        $this->assertDatabaseCount('copias_evidencias',0); $this->assertDatabaseCount('copias_outbox',1);
    }
    public function test_mariadb_serializa_conciliacao_pelo_lote(): void
    {
        if (!in_array(DB::connection()->getDriverName(),['mysql','mariadb'],true)) $this->markTestSkipped('Exige MariaDB.');
        $id=$this->criar(); $this->saida($id);
        config(['database.connections.c2_disputa'=>config('database.connections.'.config('database.default'))]);
        $outro=DB::connection('c2_disputa'); $outro->statement('SET SESSION innodb_lock_wait_timeout=1'); $tentou=false; $codigo=null;
        DB::listen(function($q) use($outro,$id,&$tentou,&$codigo) {
            if ($tentou || !str_contains($q->sql,'for update')) return; $tentou=true;
            try { $outro->transaction(fn()=>$outro->table('copias_lotes')->where('id',$id)->lockForUpdate()->first()); $codigo=0; }
            catch (QueryException $e) { $codigo=$e->errorInfo[1]; }
        });
        try { $this->s->conciliar($this->ator(2),$id); $this->assertTrue($tentou); $this->assertSame(1205,$codigo); }
        finally { DB::purge('c2_disputa'); }
    }
}
