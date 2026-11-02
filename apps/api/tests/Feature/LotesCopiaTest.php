<?php

namespace Tests\Feature;

use App\Models\Usuario;
use App\Services\LotesCopia;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class LotesCopiaTest extends TestCase
{
    private LotesCopia $servico;

    protected function setUp(): void
    {
        parent::setUp();
        $driver = DB::connection()->getDriverName();
        if ($driver !== 'sqlite' && !str_ends_with(DB::connection()->getDatabaseName(), '_test')) {
            throw new \RuntimeException('Exige banco descartável terminado em _test.');
        }
        if ($driver === 'sqlite' && DB::connection()->getDatabaseName() !== ':memory:') throw new \RuntimeException('Exige SQLite em memória.');
        // Sem transação externa: testa exatamente o isolamento usado pelo serviço.
        RefreshDatabaseState::$migrated = false;
        $this->artisan('migrate:fresh', ['--force'=>true])->assertSuccessful();
        config(['espacos.ativo'=>true,'espacos.pessoal'=>true,'vinculos.obrigatorio'=>true,'copias.ativo'=>true]);
        Usuario::forceCreate(['id'=>1,'perfil'=>'admin_rede','nome'=>'Fictício','email'=>'lote@example.test',
            'cpf'=>'00000000001','password'=>'ficticio','ativo'=>true]);
        DB::table('clientes')->insert(['id'=>1,'tipo'=>'rede','nome'=>'Cliente','modalidade'=>'interno','situacao'=>'ativo']);
        DB::table('redes')->insert(['id'=>1,'nome'=>'Rede','tipo'=>'estadual','uf'=>'BA']);
        DB::table('nucleos')->insert(['id'=>1,'rede_id'=>1,'nome'=>'Núcleo']);
        DB::table('escolas')->insert(['id'=>1,'nucleo_id'=>1,'nome'=>'Escola','inep'=>'00000001','tipo_rede'=>'estadual']);
        foreach (['rede'=>1,'nucleo'=>2,'escola'=>3,'pessoal'=>4] as $tipo=>$id) {
            DB::table('espacos')->insert(['id'=>$id,'cliente_id'=>1,'tipo'=>$tipo,'nome'=>$tipo,'situacao'=>'ativo',
                ($tipo === 'pessoal' ? 'usuario_proprietario_id' : $tipo.'_id')=>1]);
        }
        foreach ([[1,1,'admin_rede'],[2,4,'professor_autonomo']] as [$id,$espaco,$perfil]) {
            DB::table('usuario_vinculos')->insert(['id'=>$id,'usuario_id'=>1,'espaco_id'=>$espaco,'perfil'=>$perfil,
                'situacao'=>'ativo','inicio_em'=>now()->subDay(),'versao_autorizacao'=>1]);
        }
        foreach ([1,2] as $id) DB::table('provas')->insert(['id'=>$id,'escola_id'=>1,'espaco_id'=>3,'criado_por'=>1,
            'titulo'=>'Material fictício','disciplina'=>'Matemática','serie'=>'3','num_questoes'=>1,'num_alternativas'=>4]);
        DB::table('gabaritos')->insert(['id'=>1,'prova_id'=>1,'espaco_id'=>3]);
        DB::table('gabarito_questoes')->insert(['id'=>1,'gabarito_id'=>1,'espaco_id'=>3,'numero_questao'=>1,'alternativa'=>'A']);
        $this->servico = new LotesCopia;
    }

    private function ator(int $id = 1): Usuario { return Usuario::findOrFail(1)->noVinculo($id); }
    private function criar(array $ids = [1], ?string $chave = null): string
    {
        return $this->servico->criar($this->ator(),3,4,$chave ?? (string) Str::uuid(),$ids);
    }
    private function aprovar(string $lote, string $lado = 'origem', ?CarbonImmutable $validade = null): array
    {
        if (!DB::table('copias_revisoes')->where('lote_id',$lote)->exists()) {
            $previa=$this->servico->previaMaterial($this->ator(),$lote);
            $this->servico->revisarMaterial($this->ator(),$lote,$previa['conteudo_digest'],true);
        }
        return $this->servico->aprovar($this->ator($lado === 'origem' ? 1 : 2),$lote,$lado,$validade ?? CarbonImmutable::now()->addHour());
    }
    private function recusa(int $status, callable $acao): void
    {
        try { $acao(); $this->fail('Operação deveria ser recusada.'); }
        catch (HttpException $e) { $this->assertSame($status,$e->getStatusCode()); }
    }
    private function restricao(callable $acao): void
    {
        try { $acao(); $this->fail('O banco deveria recusar.'); }
        catch (QueryException $e) { $this->assertNotEmpty($e->errorInfo); }
    }

    public function test_pedido_idempotente_normaliza_ordem_sem_renovar_a_fonte(): void
    {
        $chave=(string) Str::uuid(); $lote=$this->criar([2,1],$chave);
        $this->assertSame($lote,$this->criar([1,2],strtoupper($chave)));
        $this->recusa(409,fn()=>$this->criar([1],$chave));
        $antes=DB::table('copias_itens')->where('origem_id',1)->value('fonte_digest');
        DB::table('provas')->where('id',1)->update(['titulo'=>'Alterado']);
        $this->assertSame($lote,$this->criar([1,2],$chave));
        $this->assertSame($antes,DB::table('copias_itens')->where('origem_id',1)->value('fonte_digest'));
        $this->assertDatabaseCount('copias_lotes',1);
        $this->assertDatabaseCount('copias_itens',2);
        $this->assertDatabaseCount('copias_autorizacoes',0);
        $this->assertStringNotContainsString('Material fictício',json_encode(DB::table('copias_itens')->get()));
    }

    public function test_duas_decisoes_na_mesma_conta_nao_disparam_copia_e_sao_idempotentes(): void
    {
        $lote=$this->criar(); $validade=CarbonImmutable::now()->addHour()->startOfSecond();
        $a=$this->aprovar($lote,'origem',$validade);
        $this->assertSame('aguardando_autorizacao',$a['estado']);
        $this->assertFalse($this->servico->revalidar($this->ator(),$lote,'origem'));
        $this->assertSame($a,$this->aprovar($lote,'origem',$validade));
        $this->recusa(403,fn()=>$this->servico->aprovar($this->ator(),$lote,'destino',$validade));
        $b=$this->aprovar($lote,'destino',$validade);
        $this->assertSame('aguardando_auditoria',$b['estado']);
        $this->assertTrue($this->servico->revalidar($this->ator(),$lote,'origem'));
        $this->assertDatabaseCount('provas',2);
        $this->assertDatabaseCount('registros_origem',0);
        $this->assertDatabaseCount('copias_autorizacoes',2);
        $this->assertSame([1,2],DB::table('copias_autorizacoes')->orderBy('vinculo_id')->pluck('vinculo_id')->map(fn($id)=>(int)$id)->all());
        $this->recusa(409,fn()=>$this->aprovar($lote,'origem',$validade->addDay()));
    }

    public function test_selecao_invalida_ou_incompleta_faz_rollback(): void
    {
        foreach ([[1,1],[],['1']] as $ids) $this->recusa(422,fn()=>$this->criar($ids));
        $this->recusa(404,fn()=>$this->criar([1,999]));
        $this->recusa(404,fn()=>$this->servico->criar($this->ator(2),4,3,(string)Str::uuid(),[1]));
        $this->assertDatabaseCount('copias_lotes',0);
        $this->assertDatabaseCount('copias_itens',0);
        config(['copias.ativo'=>false]);
        $this->recusa(403,fn()=>$this->criar());
    }

    public function test_revogacao_e_versao_invalidam_sem_reabrir_lote(): void
    {
        $lote=$this->criar(); $this->aprovar($lote); $this->aprovar($lote,'destino');
        DB::table('usuario_vinculos')->where('id',1)->increment('versao_autorizacao');
        $this->assertFalse($this->servico->revalidar($this->ator(2),$lote,'destino'));
        $this->assertDatabaseHas('copias_lotes',['id'=>$lote,'estado'=>'bloqueado']);
        $this->recusa(409,fn()=>$this->aprovar($lote));
        $novo=$this->criar(); $this->aprovar($novo);
        $this->servico->revogar($this->ator(),$novo,'origem');
        $this->assertNotNull(DB::table('copias_autorizacoes')->where('lote_id',$novo)->value('revogada_em'));
        $this->assertDatabaseHas('copias_autorizacoes',['lote_id'=>$novo,'revogada_por'=>1,'revogada_vinculo_id'=>1]);
        $this->assertFalse($this->servico->revalidar($this->ator(2),$novo,'destino'));
    }

    public function test_validade_e_retirada_bloqueiam_a_revalidacao(): void
    {
        $lote=$this->criar();
        $this->recusa(409,fn()=>$this->aprovar($lote,'origem',CarbonImmutable::now()->subSecond()));
        $this->aprovar($lote,'origem',CarbonImmutable::now()->addMinute()); $this->aprovar($lote,'destino');
        $this->travel(2)->minutes();
        $this->assertFalse($this->servico->revalidar($this->ator(2),$lote,'destino'));
        $this->travelBack();
        $novo=$this->criar(); $this->aprovar($novo); $this->aprovar($novo,'destino');
        DB::table('vinculo_permissoes')->insert(['vinculo_id'=>1,'permissao'=>'copia.autorizar_origem','efeito'=>'retirar']);
        $this->assertFalse($this->servico->revalidar($this->ator(2),$novo,'destino'));
        $this->assertDatabaseHas('copias_lotes',['id'=>$novo,'estado'=>'bloqueado']);
    }

    public function test_gabarito_alterado_e_fonte_excluida_bloqueiam_sem_apagar_historico(): void
    {
        $lote=$this->criar(); $this->aprovar($lote);
        DB::table('gabarito_questoes')->where('id',1)->update(['alternativa'=>'B']);
        $this->recusa(409,fn()=>$this->aprovar($lote,'destino'));
        $this->assertFalse($this->servico->revalidar($this->ator(),$lote,'origem'));
        $novo=$this->criar(); $this->aprovar($novo); $this->aprovar($novo,'destino');
        DB::table('provas')->where('id',1)->delete();
        $this->assertFalse($this->servico->revalidar($this->ator(2),$novo,'destino'));
        $this->assertDatabaseCount('copias_itens',2);
        $this->assertDatabaseCount('copias_autorizacoes',3);
    }

    public function test_constraints_amarram_item_decisao_e_mapa_ao_lote_sem_cascata(): void
    {
        $lote=$this->criar(); $this->aprovar($lote);
        $item=(array)DB::table('copias_itens')->first(); unset($item['id']);
        $this->restricao(fn()=>DB::table('copias_itens')->insert($item));
        $this->restricao(fn()=>DB::table('copias_itens')->where('lote_id',$lote)->update(['origem_espaco_id'=>4]));
        $this->restricao(fn()=>DB::table('copias_autorizacoes')->where('lote_id',$lote)->update(['destino_espaco_id'=>3]));
        $this->restricao(fn()=>DB::table('copias_lotes')->where('id',$lote)->delete());
        Usuario::forceCreate(['id'=>2,'perfil'=>'professor','nome'=>'Outra pessoa','email'=>'outra@example.test',
            'cpf'=>'00000000002','password'=>'ficticio','ativo'=>true]);
        $this->restricao(fn()=>DB::table('copias_lotes')->where('id',$lote)->update(['solicitante_id'=>2]));
        $this->restricao(fn()=>DB::table('copias_autorizacoes')->where('lote_id',$lote)->update(['ator_id'=>2]));
        if (in_array(DB::connection()->getDriverName(),['mysql','mariadb'],true)) {
            $this->restricao(fn()=>DB::table('copias_lotes')->where('id',$lote)->update(['total_itens'=>0]));
            $linha=(array)DB::table('copias_lotes')->where('id',$lote)->first();
            $this->restricao(fn()=>DB::table('copias_lotes')->insert(array_replace($linha,
                ['id'=>(string)Str::uuid(),'chave'=>(string)Str::uuid(),'destino_espaco_id'=>3])));
        }
        $mapa=['lote_id'=>$lote,'entidade'=>'prova','origem_espaco_id'=>3,'origem_id'=>1,'destino_espaco_id'=>4,
            'destino_id'=>50,'copiado_por'=>1,'created_at'=>now()];
        DB::table('registros_origem')->insert($mapa); // Fixture de publicação futura, não API de gravação.
        $this->restricao(fn()=>DB::table('registros_origem')->insert(array_replace($mapa,['destino_id'=>51])));
        $this->restricao(fn()=>DB::table('registros_origem')->insert(array_replace($mapa,['origem_id'=>2])));
        $outro=$this->criar([2]);
        $this->restricao(fn()=>DB::table('registros_origem')->insert(array_replace($mapa,['lote_id'=>$outro,'origem_id'=>2])));
        $this->recusa(409,fn()=>$this->criar());
        $this->assertDatabaseCount('registros_origem',1);
    }

    public function test_transacao_externa_e_regime_legado_sao_recusados(): void
    {
        DB::beginTransaction();
        try {
            try { $this->criar(); $this->fail('Transação externa deveria ser recusada.'); }
            catch (\LogicException $e) { $this->assertStringContainsString('transação própria',$e->getMessage()); }
        } finally { DB::rollBack(); }
        config(['vinculos.obrigatorio'=>false]);
        $this->recusa(403,fn()=>$this->criar());
    }

    public function test_mariadb_revogacao_concorrente_e_nova_retirada_aguardam_commit(): void
    {
        if (!in_array(DB::connection()->getDriverName(),['mysql','mariadb'],true)) $this->markTestSkipped('Concorrência exige MariaDB.');
        config(['database.connections.t5_disputa'=>config('database.connections.'.config('database.default'))]);
        $outro=DB::connection('t5_disputa'); $outro->statement('SET SESSION innodb_lock_wait_timeout = 1');
        $lote=$this->criar(); $tentou=false; $codigos=[];
        DB::listen(function ($query) use ($outro,&$tentou,&$codigos) {
            if ($tentou || !str_starts_with($query->sql,'insert into `copias_autorizacoes`')) return;
            $tentou=true;
            foreach ([fn()=>$outro->table('usuario_vinculos')->where('id',1)->update(['situacao'=>'encerrado']),
                fn()=>$outro->table('vinculo_permissoes')->insert(['vinculo_id'=>1,'permissao'=>'copia.autorizar_origem','efeito'=>'retirar'])] as $acao) {
                try { $acao(); $codigos[]=0; } catch (QueryException $e) { $codigos[]=$e->errorInfo[1]; }
            }
        });
        try {
            $this->aprovar($lote);
            $this->assertTrue($tentou); $this->assertSame([1205,1205],$codigos);
            $outro->table('usuario_vinculos')->where('id',1)->update(['situacao'=>'encerrado']);
            $this->assertFalse($this->servico->revalidar($this->ator(2),$lote,'destino'));
        } finally { DB::purge('t5_disputa'); }
    }

    public function test_mariadb_mesma_chave_concorrente_nao_duplica_lote(): void
    {
        if (!in_array(DB::connection()->getDriverName(),['mysql','mariadb'],true)) $this->markTestSkipped('Concorrência exige MariaDB.');
        config(['database.connections.t5_disputa'=>config('database.connections.'.config('database.default'))]);
        $outro=DB::connection('t5_disputa'); $outro->statement('SET SESSION innodb_lock_wait_timeout = 1');
        $tentou=false; $codigo=null;
        DB::listen(function ($query) use ($outro,&$tentou,&$codigo) {
            if ($tentou || !str_starts_with($query->sql,'insert into `copias_lotes`')) return;
            $tentou=true;
            try { $outro->insert($query->sql,$query->bindings); $codigo=0; }
            catch (QueryException $e) { $codigo=$e->errorInfo[1]; }
        });
        try {
            $chave=(string)Str::uuid(); $id=$this->criar([1],$chave);
            $this->assertTrue($tentou); $this->assertSame(1205,$codigo);
            $this->assertSame($id,$this->criar([1],$chave));
            $this->assertDatabaseCount('copias_lotes',1);
        } finally { DB::purge('t5_disputa'); }
    }

    public function test_previa_lista_positiva_e_textos_exigem_revisao_sem_acesso_do_destino(): void
    {
        DB::table('provas')->where('id',1)->update(['titulo'=>'Texto livre a revisar','status'=>'corrigida','data_aplicacao'=>'2026-01-01']);
        $lote=$this->criar(); $previa=$this->servico->previaMaterial($this->ator(),$lote);
        $this->assertTrue($previa['revisao_humana_obrigatoria']);
        $material=$previa['itens'][0]['material'];
        $this->assertSame(\App\Support\MaterialCopia::CAMPOS,array_keys($material['prova']));
        $this->assertSame(['numero_questao','alternativa','alternativa_espanhol','peso','anulada'],array_keys($material['questoes'][0]));
        $this->assertSame('Texto livre a revisar',$material['prova']['titulo']);
        foreach (['criado_por','escola_id','espaco_id','status','data_aplicacao','publicado_por','cartoes','alunos','notas'] as $campo) {
            $this->assertArrayNotHasKey($campo,$material['prova']); $this->assertArrayNotHasKey($campo,$material['questoes'][0]);
        }
        $this->recusa(403,fn()=>$this->servico->previaMaterial($this->ator(2),$lote));
        $this->recusa(409,fn()=>$this->servico->aprovar($this->ator(),$lote,'origem',CarbonImmutable::now()->addHour()));
        $this->assertDatabaseCount('copias_revisoes',0);
        $this->assertDatabaseCount('copias_autorizacoes',0);
    }

    public function test_revisao_explicita_idempotente_e_digest_desatualizado_recusado(): void
    {
        $lote=$this->criar(); $p=$this->servico->previaMaterial($this->ator(),$lote);
        $this->recusa(422,fn()=>$this->servico->revisarMaterial($this->ator(),$lote,$p['conteudo_digest'],false));
        $this->recusa(409,fn()=>$this->servico->revisarMaterial($this->ator(),$lote,str_repeat('a',64),true));
        $id=$this->servico->revisarMaterial($this->ator(),$lote,$p['conteudo_digest'],true);
        $this->assertSame($id,$this->servico->revisarMaterial($this->ator(),$lote,$p['conteudo_digest'],true));
        $this->assertDatabaseCount('copias_revisoes',1); $this->assertDatabaseCount('copias_outbox',2);
        $this->assertStringNotContainsString('Material fictício',json_encode(DB::table('copias_revisoes')->get()));
        DB::table('provas')->where('id',1)->update(['titulo'=>'Nova versão']);
        $this->recusa(409,fn()=>$this->servico->revisarMaterial($this->ator(),$lote,$p['conteudo_digest'],true));
        $this->assertDatabaseCount('copias_revisoes',1);
    }

    public function test_configuracoes_incompletas_nao_sao_corrigidas_silenciosamente(): void
    {
        $this->recusa(422,fn()=>$this->servico->previaMaterial($this->ator(),$this->criar([2])));
        foreach ([['alternativa'=>'E'],['numero_questao'=>2],['peso'=>-1]] as $mudanca) {
            DB::table('gabarito_questoes')->where('id',1)->update($mudanca);
            $id=$this->criar();
            $this->recusa(422,fn()=>$this->servico->previaMaterial($this->ator(),$id));
            DB::table('gabarito_questoes')->where('id',1)->update(['alternativa'=>'A','numero_questao'=>1,'peso'=>1]);
        }
        DB::table('provas')->where('id',1)->update(['tipo_pontuacao'=>'personalizado']);
        DB::table('gabarito_questoes')->where('id',1)->update(['peso'=>0]);
        $this->recusa(422,fn()=>$this->servico->previaMaterial($this->ator(),$this->criar()));
        $this->assertDatabaseCount('copias_revisoes',0); $this->assertDatabaseCount('registros_origem',0);
    }

    public function test_opcao_lingua_exige_alternativa_valida_e_nao_carrega_valor_oculto(): void
    {
        DB::table('provas')->where('id',1)->update(['opcao_lingua'=>true]);
        $this->recusa(422,fn()=>$this->servico->previaMaterial($this->ator(),$this->criar()));
        DB::table('gabarito_questoes')->where('id',1)->update(['alternativa_espanhol'=>'B']);
        $p=$this->servico->previaMaterial($this->ator(),$this->criar());
        $this->assertSame('B',$p['itens'][0]['material']['questoes'][0]['alternativa_espanhol']);
        DB::table('provas')->where('id',1)->update(['opcao_lingua'=>false]);
        $this->recusa(422,fn()=>$this->servico->previaMaterial($this->ator(),$this->criar()));
    }

    public function test_revisao_nao_pode_ser_fabricada_depois_das_decisoes(): void
    {
        $id=$this->criar(); $this->aprovar($id);
        // Simula lote do pacote anterior, cujas decisões não tiveram revisão de conteúdo.
        DB::table('copias_revisoes')->where('lote_id',$id)->delete();
        $previa=$this->servico->previaMaterial($this->ator(),$id);
        $this->recusa(409,fn()=>$this->servico->revisarMaterial($this->ator(),$id,$previa['conteudo_digest'],true));
        $this->assertFalse($this->servico->revalidar($this->ator(2),$id,'destino'));
        $this->assertDatabaseHas('copias_lotes',['id'=>$id,'estado'=>'bloqueado']);
    }

    public function test_revisao_perde_validade_quando_muda_versao_do_revisor(): void
    {
        $id=$this->criar(); $p=$this->servico->previaMaterial($this->ator(),$id);
        $this->servico->revisarMaterial($this->ator(),$id,$p['conteudo_digest'],true);
        DB::table('usuario_vinculos')->where('id',1)->increment('versao_autorizacao');
        $this->recusa(409,fn()=>$this->servico->aprovar($this->ator(2),$id,'destino',CarbonImmutable::now()->addHour()));
        $this->recusa(409,fn()=>$this->servico->revisarMaterial($this->ator(),$id,$p['conteudo_digest'],true));
        $this->assertDatabaseCount('copias_autorizacoes',0);
    }

    public function test_falha_no_recibo_reverte_revisao(): void
    {
        $id=$this->criar(); $p=$this->servico->previaMaterial($this->ator(),$id);
        DB::listen(function ($q) {
            if (preg_match('/^insert into ["`]copias_outbox["`]/',$q->sql)) throw new \RuntimeException('recibo-revisao-falhou');
        });
        try { $this->servico->revisarMaterial($this->ator(),$id,$p['conteudo_digest'],true); $this->fail('Deveria reverter.'); }
        catch (\RuntimeException $e) { $this->assertSame('recibo-revisao-falhou',$e->getMessage()); }
        $this->assertDatabaseCount('copias_revisoes',0); $this->assertDatabaseCount('copias_outbox',1);
    }

    public function test_escola_da_prova_divergente_do_espaco_nao_revela_previa(): void
    {
        $id=$this->criar();
        DB::table('escolas')->insert(['id'=>2,'nucleo_id'=>1,'nome'=>'Outra escola','inep'=>'00000002','tipo_rede'=>'estadual']);
        DB::table('provas')->where('id',1)->update(['escola_id'=>2]);
        $this->recusa(404,fn()=>$this->servico->previaMaterial($this->ator(),$id));
        $this->recusa(404,fn()=>$this->criar());
        $this->assertDatabaseCount('copias_revisoes',0);
    }
}
