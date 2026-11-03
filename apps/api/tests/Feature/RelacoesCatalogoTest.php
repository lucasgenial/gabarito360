<?php

namespace Tests\Feature;

use App\Services\ConsultarRelacoesCatalogo;
use App\Services\PrepararCatalogoInstituicoes;
use App\Services\PrepararRelacoesCatalogo;
use App\Services\PublicarCatalogoInstituicoes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\Support\ArquivosCatalogo;
use Tests\TestCase;

class RelacoesCatalogoTest extends TestCase
{
    use RefreshDatabase, ArquivosCatalogo;
    private array $basica;
    private array $parametros;
    private array $publicacaoBase;
    private array $publicacaoSuperior;
    protected function tearDown(): void { $this->limparArquivosCatalogo(); parent::tearDown(); }

    private function preparar(?array $vinculos = null): void
    {
        $vinculos ??= ['11000001'=>['12000001','00000000000001'],'12000001'=>['99000001','99999999999999']];
        $this->basica = $this->fonteCatalogoComRelacoes($vinculos);
        $superior = $this->fonteCatalogo('superior');
        $i = app(PrepararCatalogoInstituicoes::class)->executar(...$this->basica);
        $s = app(PrepararCatalogoInstituicoes::class)->executar(...$superior);
        $this->publicacaoBase = app(PublicarCatalogoInstituicoes::class)->executar($i['importacao_id'],$this->basica['ufs']);
        $this->publicacaoSuperior = app(PublicarCatalogoInstituicoes::class)->executar($s['importacao_id'],$superior['ufs']);
        $this->parametros = ['arquivo'=>$this->basica['arquivo'],'basicaId'=>$this->publicacaoBase['publicacao_id'],
            'superiorId'=>$this->publicacaoSuperior['publicacao_id'],'sha256'=>$this->basica['sha256']];
    }

    private function executar(array $opcoes = []): array { return app(PrepararRelacoesCatalogo::class)->executar(...array_replace($this->parametros,$opcoes)); }

    private function novaBase(): array
    {
        $entrada = $this->basica;
        $entrada['arquivo'] = $this->arquivoCatalogo(str_replace('Escola fictícia','Escola revisada',file_get_contents($entrada['arquivo'])));
        $entrada['sha256'] = hash_file('sha256',$entrada['arquivo']);
        $i = app(PrepararCatalogoInstituicoes::class)->executar(...$entrada);
        $p = app(PublicarCatalogoInstituicoes::class)->executar($i['importacao_id'],$entrada['ufs'],substituir:$this->publicacaoBase['publicacao_id'],motivo:'Revisão fictícia.');
        return ['arquivo'=>$entrada['arquivo'],'basicaId'=>$p['publicacao_id'],'sha256'=>$entrada['sha256']];
    }

    public function test_simulacao_pausa_retomada_lacunas_e_idempotencia_sem_fundir_entidades(): void
    {
        $this->preparar();
        $antes = [];
        foreach (['catalogo_importacoes','catalogo_observacoes','catalogo_publicacoes','catalogo_entradas','catalogo_identificadores','catalogo_instituicoes','catalogo_unidades','escolas','usuarios','provas','gabaritos','gabarito_questoes'] as $t) $antes[$t] = DB::table($t)->get()->toJson();
        self::assertSame('simulacao_sem_gravacao',$this->executar(['simular'=>true])['modo']);
        $this->assertDatabaseCount('catalogo_relacao_conjuntos',0);
        $r = $this->executar(['lote'=>2,'maxLotes'=>1]);
        self::assertSame('preparando',$r['estado']); self::assertSame(2,$r['cursor']); self::assertFalse($r['vigente']);
        self::assertSame('nao_preparadas_para_edicao',app(ConsultarRelacoesCatalogo::class)->cobertura()['situacao_relacoes']);
        $ids = DB::table('catalogo_relacoes')->orderBy('ordem')->pluck('id')->all();
        $final = $this->executar();
        self::assertSame($r['conjunto_id'],$final['conjunto_id']); self::assertTrue($final['vigente']); self::assertSame(4,$final['total']); self::assertSame(2,$final['pendencias']);
        self::assertSame($ids,DB::table('catalogo_relacoes')->orderBy('ordem')->limit(2)->pluck('id')->all());
        self::assertSame($final,$this->executar());
        $this->assertDatabaseCount('catalogo_relacoes',4); $this->assertDatabaseCount('catalogo_relacao_lacunas',2); $this->assertDatabaseCount('catalogo_relacao_eventos',1);
        $this->assertDatabaseHas('catalogo_relacoes',['tipo'=>'ies_vinculada','alvo_codigo_bruto'=>'00000000000001','alvo_codigo'=>'1','estado'=>'referenciada']);
        $this->assertDatabaseHas('catalogo_relacoes',['tipo'=>'sede_escolar','alvo_codigo'=>'12000001','uf_diferente'=>true]);
        foreach ($antes as $t=>$json) self::assertSame($json,DB::table($t)->get()->toJson(),$t);
        $q = app(ConsultarRelacoesCatalogo::class); $c = $q->cobertura();
        self::assertSame(27,$c['escolas_referencia']); self::assertSame(27,$c['sedes_administrativas_ies']); self::assertSame(0,$c['unidades_adicionais_comprovadas']);
        self::assertSame(2,$q->lacunas()['dados']['total']);
        $unidade = DB::table('catalogo_entradas')->where('publicacao_id',$this->publicacaoBase['publicacao_id'])->where('codigo','11000001')->value('unidade_id');
        self::assertSame(2,$q->paraUnidade($unidade,$this->publicacaoBase['publicacao_id'])['dados']['total']);
        $this->artisan('g360:catalogo-relacoes-status',['--lacunas'=>true])->expectsOutputToContain('lacunas_publicas_da_fonte')->assertSuccessful();
    }

    public function test_ciclo_e_autorreferencia_entram_em_revisao_sem_desaparecer_da_fonte(): void
    {
        $this->preparar(['11000001'=>['12000001','1'],'12000001'=>['11000001',''],'13000001'=>['13000001','']]);
        $r = $this->executar(); self::assertSame(4,$r['total']); self::assertSame(3,$r['pendencias']);
        self::assertSame(2,DB::table('catalogo_relacao_lacunas')->where('motivo','ciclo')->count());
        $this->assertDatabaseHas('catalogo_relacao_lacunas',['motivo'=>'autorreferencia']);
        $this->assertDatabaseCount('catalogo_instituicoes',54); $this->assertDatabaseCount('catalogo_unidades',54);
    }

    public function test_nova_edicao_b3_invalida_relacoes_anteriores_sem_reescrever_historico(): void
    {
        $this->preparar(); $a = $this->executar(); $antes = DB::table('catalogo_relacoes')->get()->toJson();
        $nova = $this->novaBase();
        self::assertFalse($this->executar()['vigente']);
        self::assertSame('edicao_sem_relacoes',app(ConsultarRelacoesCatalogo::class)->lacunas()['situacao']);
        $novo = $this->executar($nova);
        self::assertNotSame($a['conjunto_id'],$novo['conjunto_id']); self::assertTrue($novo['vigente']);
        self::assertSame($antes,DB::table('catalogo_relacoes')->where('conjunto_id',$a['conjunto_id'])->get()->toJson());
        self::assertSame('edicao_sem_relacoes',app(ConsultarRelacoesCatalogo::class)->paraUnidade('inexistente',$this->publicacaoBase['publicacao_id'])['situacao']);
    }

    public function test_falha_em_evento_reverte_ativacao_e_retoma_mesmo_checkpoint(): void
    {
        $this->preparar(); $falhou = false;
        DB::listen(function($q) use (&$falhou) { if (!$falhou && str_starts_with(strtolower($q->sql),'insert') && str_contains($q->sql,'catalogo_relacao_eventos')) { $falhou=true; throw new RuntimeException('Falha fictícia no evento.'); } });
        try { $this->executar(); $this->fail('Esperava falha no evento.'); } catch (RuntimeException $e) { self::assertSame('Falha fictícia no evento.',$e->getMessage()); }
        self::assertNull(DB::table('catalogo_relacao_vigencia')->value('conjunto_id'));
        $this->assertDatabaseHas('catalogo_relacao_conjuntos',['estado'=>'falhou','cursor'=>4,'lease_token'=>null]);
        self::assertTrue($this->executar()['vigente']); $this->assertDatabaseCount('catalogo_relacoes',4);
    }

    public function test_edicao_muda_durante_conferencia_e_impede_ativacao(): void
    {
        $this->preparar(); $trocou = false;
        DB::listen(function($q) use (&$trocou) {
            if (!$trocou && str_starts_with(strtolower($q->sql),'select *') && str_contains($q->sql,'catalogo_relacoes')) {
                $trocou = true; $this->novaBase();
            }
        });
        try { $this->executar(); $this->fail('Esperava recusa por edição alterada.'); }
        catch (RuntimeException $e) { self::assertStringContainsString('deixaram de ser vigentes',$e->getMessage()); }
        self::assertTrue($trocou); self::assertNull(DB::table('catalogo_relacao_vigencia')->value('conjunto_id'));
        $this->assertDatabaseHas('catalogo_relacao_conjuntos',['estado'=>'falhou','publicado_em'=>null]);
    }

    public function test_lease_concorrente_expiracao_e_token_substituido(): void
    {
        $this->preparar(); $r = $this->executar(['lote'=>1,'maxLotes'=>1]); $token = (string)Str::uuid();
        DB::table('catalogo_relacao_conjuntos')->where('id',$r['conjunto_id'])->update(['lease_token'=>$token,'lease_ate'=>now()->addMinutes(30)]);
        try { $this->executar(); $this->fail('Esperava recusa concorrente.'); } catch (RuntimeException $e) { self::assertStringContainsString('já estão em execução',$e->getMessage()); }
        $this->assertDatabaseHas('catalogo_relacao_conjuntos',['lease_token'=>$token,'cursor'=>1]);
        DB::table('catalogo_relacao_conjuntos')->where('id',$r['conjunto_id'])->update(['lease_ate'=>now()->subMinute()]);
        $trocou = false; $novo = (string)Str::uuid();
        DB::listen(function($q) use (&$trocou,$novo) {
            if (!$trocou && str_starts_with(strtolower($q->sql),'update') && str_contains($q->sql,'catalogo_relacao_conjuntos') && str_contains($q->sql,'lease_token')) {
                $trocou = true; DB::table('catalogo_relacao_conjuntos')->update(['lease_token'=>$novo,'lease_ate'=>now()->addMinutes(30)]);
            }
        });
        try { $this->executar(); $this->fail('Esperava recusa do token antigo.'); } catch (RuntimeException $e) { self::assertStringContainsString('Lease de relações',$e->getMessage()); }
        $this->assertDatabaseHas('catalogo_relacao_conjuntos',['lease_token'=>$novo,'cursor'=>1,'estado'=>'preparando']);
    }

    public function test_arquivo_modificado_no_lote_nao_ativa_relacoes(): void
    {
        $this->preparar(); $alterou = false;
        DB::listen(function($q) use (&$alterou) { if (!$alterou && str_starts_with(strtolower($q->sql),'insert') && str_contains($q->sql,'catalogo_relacoes')) { $alterou=true; file_put_contents($this->basica['arquivo'],"\n",FILE_APPEND); } });
        try { $this->executar(); $this->fail('Esperava recusa de integridade.'); } catch (RuntimeException $e) { self::assertStringContainsString('SHA-256',$e->getMessage()); }
        self::assertNull(DB::table('catalogo_relacao_vigencia')->value('conjunto_id'));
        $this->assertDatabaseHas('catalogo_relacao_conjuntos',['estado'=>'falhou','publicado_em'=>null]);
    }

    public function test_conjunto_vazio_e_valido_e_nao_cria_pendencias_ficticias(): void
    {
        $this->preparar([]); $r = $this->executar();
        self::assertTrue($r['vigente']); self::assertSame(0,$r['total']); self::assertSame(0,$r['pendencias']);
        $this->assertDatabaseCount('catalogo_relacoes',0); $this->assertDatabaseCount('catalogo_relacao_lacunas',0);
    }

    public function test_selagem_bloqueia_inserts_tardios_e_update_delete_no_banco(): void
    {
        $this->preparar(); $this->executar();
        $inserts = [];
        foreach (['catalogo_observacoes','catalogo_entradas','catalogo_relacoes','catalogo_relacao_lacunas','catalogo_relacao_eventos'] as $t) {
            $r = (array)DB::table($t)->first(); unset($r['id']);
            if (isset($r['ordem'])) $r['ordem'] = 999;
            if (isset($r['chave_fonte'])) $r['chave_fonte'] = 'inep_escola:99000001';
            $inserts[$t] = $r;
        }
        foreach ($inserts as $t=>$r) {
            try { DB::transaction(fn()=>DB::table($t)->insert($r)); $this->fail('Esperava bloqueio de insert após selagem.'); }
            catch (\Illuminate\Database\QueryException $e) { self::assertStringContainsString('Pai do catalogo selado',$e->getMessage()); }
        }
        foreach (['catalogo_relacoes'=>'tipo','catalogo_relacao_lacunas'=>'motivo','catalogo_relacao_eventos'=>'total','catalogo_relacao_conjuntos'=>'estado'] as $t=>$campo) {
            foreach (['update','delete'] as $acao) {
                try { DB::transaction(fn()=> $acao === 'delete' ? DB::table($t)->delete() : DB::table($t)->update([$campo=>DB::raw($campo)])); $this->fail('Esperava imutabilidade.'); }
                catch (\Illuminate\Database\QueryException $e) { self::assertStringContainsString('Relacao publica imutavel',$e->getMessage()); }
            }
        }
    }

    public function test_rollback_do_checkpoint_reverte_relacoes_e_lacunas_do_lote(): void
    {
        $this->preparar(); $falhou = false;
        DB::listen(function($q) use (&$falhou) {
            if (!$falhou && str_starts_with(strtolower($q->sql),'update') && str_contains($q->sql,'catalogo_relacao_conjuntos') && str_contains($q->sql,'cursor')) {
                $falhou = true; throw new RuntimeException('Falha fictícia no checkpoint.');
            }
        });
        try { $this->executar(); $this->fail('Esperava falha.'); } catch (RuntimeException $e) { self::assertSame('Falha fictícia no checkpoint.',$e->getMessage()); }
        $this->assertDatabaseCount('catalogo_relacoes',0); $this->assertDatabaseCount('catalogo_relacao_lacunas',0);
        $this->assertDatabaseHas('catalogo_relacao_conjuntos',['estado'=>'falhou','cursor'=>0]);
        self::assertTrue($this->executar()['vigente']);
    }

    public function test_simulacao_com_par_de_edicoes_superado_e_recusada_sem_gravar(): void
    {
        $this->preparar(); $this->novaBase();
        try { $this->executar(['simular'=>true]); $this->fail('Esperava recusa de edição superada.'); }
        catch (RuntimeException $e) { self::assertStringContainsString('deixaram de ser vigentes',$e->getMessage()); }
        $this->assertDatabaseCount('catalogo_relacao_conjuntos',0); $this->assertDatabaseCount('catalogo_relacoes',0);
    }
}
