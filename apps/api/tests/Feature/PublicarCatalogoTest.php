<?php

namespace Tests\Feature;

use App\Services\ConsultarCatalogoInstituicoes;
use App\Services\PrepararCatalogoInstituicoes;
use App\Services\PublicarCatalogoInstituicoes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\Support\ArquivosCatalogo;
use Tests\TestCase;

class PublicarCatalogoTest extends TestCase
{
    use RefreshDatabase, ArquivosCatalogo;

    protected function tearDown(): void { $this->limparArquivosCatalogo(); parent::tearDown(); }

    private function preparar(string $fonte = 'basica', string $prefixo = 'Escola fictícia'): array
    {
        $entrada = $this->fonteCatalogo($fonte);
        if ($prefixo !== 'Escola fictícia') {
            file_put_contents($entrada['arquivo'],str_replace('Escola fictícia',$prefixo,file_get_contents($entrada['arquivo'])));
            $entrada['sha256'] = hash_file('sha256',$entrada['arquivo']);
        }
        $r = app(PrepararCatalogoInstituicoes::class)->executar(...$entrada);
        return ['importacaoId'=>$r['importacao_id'],'arquivoUfs'=>$entrada['ufs']];
    }

    private function publicar(array $entrada, array $opcoes = []): array
    {
        return app(PublicarCatalogoInstituicoes::class)->executar(...array_replace($entrada,$opcoes));
    }

    private function busca(array $filtros): \Illuminate\Pagination\LengthAwarePaginator
    {
        return app(ConsultarCatalogoInstituicoes::class)->buscar($filtros);
    }

    public function test_simulacao_e_pausa_nao_expoem_edicao_incompleta(): void
    {
        $entrada = $this->preparar();
        self::assertSame('simulacao_sem_publicacao',$this->publicar($entrada,['simular'=>true])['modo']);
        $this->assertDatabaseCount('catalogo_publicacoes',0); $this->assertDatabaseCount('catalogo_ufs',0);
        $p = $this->publicar($entrada,['lote'=>5,'maxLotes'=>1]);
        self::assertSame('preparando',$p['estado']); self::assertSame(5,$p['cursor']); self::assertFalse($p['vigente']);
        self::assertSame(0,$this->busca(['uf'=>'RO'])->total());
        $ids = DB::table('catalogo_entradas')->orderBy('ordem')->pluck('unidade_id')->all();
        $final = $this->publicar($entrada,['lote'=>7]);
        self::assertSame($p['publicacao_id'],$final['publicacao_id']); self::assertTrue($final['vigente']);
        self::assertSame('publicada',$final['estado']); self::assertSame(27,$final['cursor']);
        self::assertSame($final,$this->publicar($entrada));
        self::assertSame($ids,DB::table('catalogo_entradas')->orderBy('ordem')->limit(5)->pluck('unidade_id')->all());
        self::assertSame(1,$this->busca(['uf'=>'RO'])->total());
        foreach (['catalogo_entradas','catalogo_instituicoes','catalogo_identificadores','catalogo_unidades'] as $t) $this->assertDatabaseCount($t,27);
        $this->assertDatabaseCount('catalogo_publicacao_eventos',1);
        $this->assertDatabaseHas('catalogo_ufs',['codigo_ibge'=>29,'sigla'=>'BA','nome'=>'Estado fictício BA']);
    }

    public function test_substituicao_explicita_mantem_edicao_anterior_ate_fechar_e_preserva_identidades(): void
    {
        $a = $this->preparar(); $pa = $this->publicar($a);
        $antes = $this->busca(['uf'=>'RO'])->items()[0];
        $b = $this->preparar('basica','Escola revisada');
        try { $this->publicar($b,['simular'=>true]); $this->fail('Simulação precisa conferir substituição.'); }
        catch (RuntimeException $e) { self::assertStringContainsString('mesmo ano',$e->getMessage()); }
        try { $this->publicar($b); $this->fail('Esperava revisão explícita.'); }
        catch (RuntimeException $e) { self::assertStringContainsString('mesmo ano',$e->getMessage()); }
        $opcoes = ['substituir'=>$pa['publicacao_id'],'motivo'=>'Revisão fictícia conferida.'];
        $pb = $this->publicar($b,$opcoes+['lote'=>5,'maxLotes'=>1]);
        self::assertSame('Escola fictícia RO',$this->busca(['uf'=>'RO'])->items()[0]->nome);
        $this->publicar($b,$opcoes);
        $depois = $this->busca(['uf'=>'RO'])->items()[0];
        self::assertSame('Escola revisada RO',$depois->nome);
        self::assertSame($antes->unidade_id,$depois->unidade_id); self::assertSame($antes->instituicao_id,$depois->instituicao_id);
        self::assertFalse($this->publicar($a)['vigente']);
        self::assertSame($pb['publicacao_id'],DB::table('catalogo_fontes')->where('nome','basica')->value('publicacao_id'));
        $this->assertDatabaseCount('catalogo_instituicoes',27); $this->assertDatabaseCount('catalogo_entradas',54);
        $this->assertDatabaseHas('catalogo_publicacao_eventos',['publicacao_id'=>$pb['publicacao_id'],'anterior_id'=>$pa['publicacao_id'],'motivo'=>$opcoes['motivo']]);
    }

    public function test_basica_e_ies_tem_vigencias_independentes_e_filtros_publicos(): void
    {
        $this->publicar($this->preparar()); $this->publicar($this->preparar('superior'));
        self::assertSame(2,$this->busca(['uf'=>'RO'])->total());
        $ies = $this->busca(['uf'=>'RO','tipo'=>'centro_universitario','busca'=>'iesro'])->items()[0];
        self::assertSame('IESRO',$ies->sigla); self::assertSame('sede_administrativa',$ies->natureza);
        self::assertSame('privada',$ies->dependencia); self::assertSame('inep_ies',$ies->namespace);
        self::assertNull($ies->situacao_fonte);
        self::assertSame(0,$this->busca(['uf'=>'RO','tipo'=>'escola','dependencia'=>'privada'])->total());
        self::assertSame(1,$this->busca(['namespace'=>'inep_escola','codigo'=>'11000001'])->total());
        self::assertSame(1,$this->busca(['namespace'=>'inep_ies','codigo'=>'1'])->total());
        $this->assertDatabaseCount('catalogo_ufs',27); $this->assertDatabaseCount('catalogo_municipios',27);
        $this->assertDatabaseCount('catalogo_instituicoes',54);
    }

    public function test_normalizacao_acentos_e_curingas_literais(): void
    {
        $this->publicar($this->preparar('basica','Colégio São José 100%_!'));
        self::assertSame(1,$this->busca(['uf'=>'RO','busca'=>'são josé'])->total());
        self::assertSame(1,$this->busca(['uf'=>'RO','busca'=>'%_!'])->total());
        self::assertSame(0,$this->busca(['uf'=>'RO','busca'=>'%__'])->total());
        self::assertSame(0,$this->busca(['uf'=>'RO','busca'=>'inexistente'])->total());
        $pagina = $this->busca(['busca'=>'colegio','per_page'=>5,'page'=>2]);
        self::assertSame(27,$pagina->total()); self::assertCount(5,$pagina->items());
        self::assertNotSame($this->busca(['busca'=>'colegio','per_page'=>5])->items()[0]->unidade_id,$pagina->items()[0]->unidade_id);
    }

    public function test_filtro_geografico_incompativel_e_recusado(): void
    {
        $this->publicar($this->preparar());
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->busca(['uf'=>'BA','municipio_ibge'=>'1100001']);
    }

    public function test_lease_vigente_e_token_substituido_nao_avancam_publicacao(): void
    {
        $entrada = $this->preparar(); $p = $this->publicar($entrada,['lote'=>5,'maxLotes'=>1]);
        $token = (string)Str::uuid();
        DB::table('catalogo_publicacoes')->where('id',$p['publicacao_id'])->update(['lease_token'=>$token,'lease_ate'=>now()->addMinutes(30)]);
        try { $this->publicar($entrada); $this->fail('Esperava recusa de concorrência.'); }
        catch (RuntimeException $e) { self::assertStringContainsString('já está em execução',$e->getMessage()); }
        $this->assertDatabaseHas('catalogo_publicacoes',['id'=>$p['publicacao_id'],'lease_token'=>$token,'cursor'=>5]);
        DB::table('catalogo_publicacoes')->where('id',$p['publicacao_id'])->update(['lease_ate'=>now()->subMinute()]);
        self::assertTrue($this->publicar($entrada)['vigente']);
    }

    public function test_falha_no_evento_reverte_troca_de_ponteiro_e_retoma(): void
    {
        $a = $this->publicar($this->preparar()); $entrada = $this->preparar('basica','Escola revisada'); $falhou = false;
        DB::listen(function($q) use (&$falhou) {
            if (!$falhou && str_starts_with(strtolower($q->sql),'insert') && str_contains($q->sql,'catalogo_publicacao_eventos')) { $falhou = true; throw new RuntimeException('Falha fictícia no evento.'); }
        });
        $opcoes = ['substituir'=>$a['publicacao_id'],'motivo'=>'Revisão fictícia.'];
        try { $this->publicar($entrada,$opcoes); $this->fail('Esperava falha.'); }
        catch (RuntimeException $e) { self::assertSame('Falha fictícia no evento.',$e->getMessage()); }
        self::assertSame($a['publicacao_id'],DB::table('catalogo_fontes')->where('nome','basica')->value('publicacao_id'));
        self::assertSame('Escola fictícia RO',$this->busca(['uf'=>'RO'])->items()[0]->nome);
        $this->assertDatabaseHas('catalogo_publicacoes',['importacao_id'=>$entrada['importacaoId'],'estado'=>'falhou','cursor'=>27]);
        self::assertTrue($this->publicar($entrada,$opcoes)['vigente']);
    }

    public function test_ponteiro_alterado_exige_nova_revisao_sem_sobrescrever_concorrente(): void
    {
        $a = $this->publicar($this->preparar()); $b = $this->preparar('basica','Escola B'); $c = $this->preparar('basica','Escola C');
        $this->publicar($b,['substituir'=>$a['publicacao_id'],'motivo'=>'Revisão B.','lote'=>5,'maxLotes'=>1]);
        $pc = $this->publicar($c,['substituir'=>$a['publicacao_id'],'motivo'=>'Revisão C.']);
        try { $this->publicar($b,['substituir'=>$a['publicacao_id'],'motivo'=>'Revisão antiga.']); $this->fail('Esperava recusa do alvo antigo.'); }
        catch (RuntimeException $e) { self::assertStringContainsString('publicação vigente',$e->getMessage()); }
        self::assertSame('Escola C RO',$this->busca(['uf'=>'RO'])->items()[0]->nome);
        self::assertTrue($this->publicar($b,['substituir'=>$pc['publicacao_id'],'motivo'=>'Nova revisão após concorrência.'])['vigente']);
        self::assertSame('Escola B RO',$this->busca(['uf'=>'RO'])->items()[0]->nome);
    }

    public function test_arquivo_de_ufs_divergente_e_carga_parcial_nao_criam_publicacao(): void
    {
        $entrada = $this->fonteCatalogo(); $r = app(PrepararCatalogoInstituicoes::class)->executar(...($entrada+['tamanhoLote'=>5,'maxLotes'=>1]));
        try { $this->publicar(['importacaoId'=>$r['importacao_id'],'arquivoUfs'=>$entrada['ufs']]); $this->fail('Esperava recusa da carga parcial.'); }
        catch (RuntimeException $e) { self::assertStringContainsString('integralmente validada',$e->getMessage()); }
        app(PrepararCatalogoInstituicoes::class)->executar(...$entrada);
        file_put_contents($entrada['ufs'],"\n",FILE_APPEND);
        try { $this->publicar(['importacaoId'=>$r['importacao_id'],'arquivoUfs'=>$entrada['ufs']]); $this->fail('Esperava recusa de UF alterada.'); }
        catch (RuntimeException $e) { self::assertStringContainsString('UFs divergente',$e->getMessage()); }
        $this->assertDatabaseCount('catalogo_publicacoes',0);
    }

    public function test_troca_durante_conferencia_final_nao_sobrescreve_publicacao_concorrente(): void
    {
        $a = $this->publicar($this->preparar());
        $b = $this->preparar('basica','Escola B'); $c = $this->preparar('basica','Escola C'); $trocou = false; $pc = null;
        DB::listen(function($q) use (&$trocou, &$pc, $c, $a) {
            if (!$trocou && str_starts_with(strtolower($q->sql),'select') && str_contains($q->sql,'fonte_payload')) {
                $trocou = true;
                $pc = $this->publicar($c,['substituir'=>$a['publicacao_id'],'motivo'=>'Publicação concorrente revisada.']);
            }
        });
        try { $this->publicar($b,['substituir'=>$a['publicacao_id'],'motivo'=>'Publicação B.']); $this->fail('Esperava conflito de ponteiro.'); }
        catch (RuntimeException $e) { self::assertStringContainsString('Ponteiro ou cursor mudou',$e->getMessage()); }
        self::assertNotNull($pc); self::assertSame($pc['publicacao_id'],DB::table('catalogo_fontes')->where('nome','basica')->value('publicacao_id'));
        self::assertSame('Escola C RO',$this->busca(['uf'=>'RO'])->items()[0]->nome);
        $this->assertDatabaseHas('catalogo_publicacoes',['importacao_id'=>$b['importacaoId'],'estado'=>'falhou','publicado_em'=>null]);
    }

    public function test_consulta_fixa_edicao_entre_contagem_e_itens_da_pagina(): void
    {
        $a = $this->publicar($this->preparar()); $b = $this->preparar('basica','Escola revisada'); $trocou = false;
        DB::listen(function($q) use (&$trocou, $a, $b) {
            if (!$trocou && str_starts_with(strtolower($q->sql),'select') && str_contains($q->sql,'aggregate') && str_contains($q->sql,'catalogo_entradas')) {
                $trocou = true;
                $this->publicar($b,['substituir'=>$a['publicacao_id'],'motivo'=>'Edição trocada durante consulta.']);
            }
        });
        $p = $this->busca(['uf'=>'RO']);
        self::assertTrue($trocou); self::assertSame(1,$p->total());
        self::assertSame('Escola fictícia RO',$p->items()[0]->nome);
        self::assertSame($a['publicacao_id'],$p->items()[0]->edicao_id);
        self::assertSame('Escola revisada RO',$this->busca(['uf'=>'RO'])->items()[0]->nome);
    }

    public function test_token_substituido_nao_grava_nem_encerra_lease_do_novo_executor(): void
    {
        $entrada = $this->preparar(); $trocou = false; $novo = (string)Str::uuid();
        DB::listen(function($q) use (&$trocou, $novo) {
            if (!$trocou && str_starts_with(strtolower($q->sql),'update') && str_contains($q->sql,'catalogo_publicacoes') && str_contains($q->sql,'lease_token')) {
                $trocou = true;
                DB::table('catalogo_publicacoes')->update(['lease_token'=>$novo,'lease_ate'=>now()->addMinutes(30)]);
            }
        });
        try { $this->publicar($entrada); $this->fail('Esperava recusa de token antigo.'); }
        catch (RuntimeException $e) { self::assertStringContainsString('Lease da publicação',$e->getMessage()); }
        $this->assertDatabaseCount('catalogo_entradas',0);
        $this->assertDatabaseHas('catalogo_publicacoes',['estado'=>'preparando','cursor'=>0,'lease_token'=>$novo]);
    }

    public function test_manifesto_com_hash_incompativel_nao_e_promovido(): void
    {
        $entrada = $this->preparar(); $i = DB::table('catalogo_importacoes')->find($entrada['importacaoId']);
        $m = json_decode($i->manifesto,true); $m['hashes']['fonte'] = str_repeat('a',64);
        DB::table('catalogo_importacoes')->where('id',$i->id)->update(['manifesto'=>json_encode($m)]);
        try { $this->publicar($entrada); $this->fail('Esperava recusa do manifesto.'); }
        catch (RuntimeException $e) { self::assertStringContainsString('Manifesto da importação divergente',$e->getMessage()); }
        $this->assertDatabaseCount('catalogo_publicacoes',0); $this->assertDatabaseCount('catalogo_entradas',0);
    }

    public function test_projecao_inserida_com_valor_incompativel_nao_e_publicada(): void
    {
        $entrada = $this->preparar(); $r = $this->publicar($entrada,['lote'=>5,'maxLotes'=>1]);
        $p = DB::table('catalogo_publicacoes')->find($r['publicacao_id']);
        $o = DB::table('catalogo_observacoes')->where('importacao_id',$entrada['importacaoId'])->where('ordem',6)->first();
        $e = \App\Support\Catalogo\EntradaCatalogo::projetar($o,$p); $e['nome'] = 'Nome incompatível';
        DB::table('catalogo_instituicoes')->insert(['id'=>$e['instituicao_id'],'created_at'=>now()]);
        DB::table('catalogo_identificadores')->insert(['instituicao_id'=>$e['instituicao_id'],'namespace'=>$e['namespace'],'codigo'=>$e['codigo']]);
        DB::table('catalogo_unidades')->insert(['id'=>$e['unidade_id'],'instituicao_id'=>$e['instituicao_id'],'natureza'=>$e['natureza'],'chave_unidade'=>'principal']);
        DB::table('catalogo_entradas')->insert($e+['projecao_sha256'=>hash('sha256',\App\Support\Catalogo\EntradaCatalogo::serializar($e))]);
        DB::table('catalogo_publicacoes')->where('id',$p->id)->update(['cursor'=>6]);
        try { $this->publicar($entrada); $this->fail('Esperava recusa de projeção adulterada.'); }
        catch (RuntimeException $e) { self::assertStringContainsString('Projeção incompatível',$e->getMessage()); }
        self::assertNull(DB::table('catalogo_fontes')->where('nome','basica')->value('publicacao_id'));
        $this->assertDatabaseHas('catalogo_publicacoes',['id'=>$p->id,'estado'=>'falhou']);
    }

    public function test_projecoes_e_identificadores_publicados_sao_imutaveis(): void
    {
        $this->publicar($this->preparar());
        foreach (['catalogo_ufs','catalogo_municipios','catalogo_instituicoes','catalogo_identificadores','catalogo_unidades','catalogo_entradas','catalogo_publicacao_eventos','catalogo_publicacoes'] as $tabela) {
            foreach (['update','delete'] as $acao) {
                $campo = match($tabela) { 'catalogo_ufs','catalogo_municipios','catalogo_entradas'=>'nome','catalogo_instituicoes'=>'created_at','catalogo_identificadores'=>'codigo','catalogo_unidades'=>'natureza',default=>'motivo' };
                try {
                    DB::transaction(fn()=> $acao === 'delete' ? DB::table($tabela)->delete() : DB::table($tabela)->update([$campo=>DB::raw($campo)]));
                    $this->fail('Esperava bloqueio de imutabilidade.');
                } catch (\Illuminate\Database\QueryException $e) { self::assertStringContainsString('Projecao publica imutavel',$e->getMessage()); }
            }
        }
    }
}
