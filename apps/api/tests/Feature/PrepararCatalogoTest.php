<?php

namespace Tests\Feature;

use App\Services\PrepararCatalogoInstituicoes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\Support\ArquivosCatalogo;
use Tests\TestCase;

class PrepararCatalogoTest extends TestCase
{
    use RefreshDatabase, ArquivosCatalogo;

    protected function tearDown(): void
    {
        $this->limparArquivosCatalogo();
        parent::tearDown();
    }

    private function executar(array $entrada, array $opcoes = []): array
    {
        return app(PrepararCatalogoInstituicoes::class)->executar(...array_replace($entrada,$opcoes));
    }

    public function test_pausa_retoma_e_repeticao_preservam_ordem_ids_e_geografia(): void
    {
        $fonte = $this->fonteCatalogo();
        $r = $this->executar($fonte,['tamanhoLote'=>5,'maxLotes'=>2]);
        self::assertSame('validando',$r['estado']); self::assertSame(10,$r['cursor']);
        $ids = DB::table('catalogo_observacoes')->pluck('id')->all();
        $r2 = $this->executar($fonte,['tamanhoLote'=>7]);
        self::assertSame($r['importacao_id'],$r2['importacao_id']);
        self::assertSame('validado',$r2['estado']); self::assertSame(27,$r2['cursor']);
        self::assertSame($r2,$this->executar($fonte));
        self::assertSame($ids,DB::table('catalogo_observacoes')->orderBy('ordem')->limit(10)->pluck('id')->all());
        $this->assertDatabaseCount('catalogo_geografias',1);
        $this->assertDatabaseCount('catalogo_importacoes',1);
        $this->assertDatabaseCount('catalogo_observacoes',27);
        $this->assertDatabaseHas('catalogo_importacoes',['id'=>$r['importacao_id'],'lease_token'=>null,'lease_ate'=>null]);
    }

    public function test_superior_preserva_sigla_tipo_sede_e_nao_cria_dados_operacionais(): void
    {
        $tabelas = ['escolas','censo_escolas','espacos','usuarios','usuario_vinculos','turmas','alunos','provas','gabaritos','gabarito_questoes','cartoes','cartao_respostas','notas'];
        $antes = [];
        foreach ($tabelas as $t) $antes[$t] = DB::table($t)->get()->toJson();
        $this->executar($this->fonteCatalogo('superior'));
        $r = json_decode(DB::table('catalogo_observacoes')->orderBy('ordem')->value('payload'),true);
        self::assertSame('IESRO',$r['sigla']); self::assertSame('centro_universitario',$r['tipo']);
        self::assertSame('sede_administrativa',$r['natureza_localizacao']);
        self::assertArrayNotHasKey('campos_presentes',$r);
        foreach ($tabelas as $t) self::assertSame($antes[$t],DB::table($t)->get()->toJson(),$t);
    }

    public function test_fonte_nova_ou_geografia_nova_nao_sobrescrevem_observacoes(): void
    {
        $entrada = $this->fonteCatalogo(); $r = $this->executar($entrada);
        $antes = DB::table('catalogo_observacoes')->where('importacao_id',$r['importacao_id'])->get()->toJson();
        file_put_contents($entrada['arquivo'],str_replace('Escola fictícia','Escola revisada',file_get_contents($entrada['arquivo'])));
        $entrada['sha256'] = hash_file('sha256',$entrada['arquivo']);
        $novo = $this->executar($entrada);
        self::assertNotSame($r['importacao_id'],$novo['importacao_id']);
        $municipios = json_decode(file_get_contents($entrada['municipios']),true);
        $municipios[0]['nome'] = 'Município revisado';
        file_put_contents($entrada['municipios'],json_encode($municipios));
        $outro = $this->executar($entrada);
        self::assertNotSame($novo['importacao_id'],$outro['importacao_id']);
        self::assertSame($antes,DB::table('catalogo_observacoes')->where('importacao_id',$r['importacao_id'])->get()->toJson());
        $this->assertDatabaseCount('catalogo_geografias',2);
    }

    public function test_lease_vigente_bloqueia_segundo_executor_sem_alterar_primeiro(): void
    {
        $entrada = $this->fonteCatalogo(); $r = $this->executar($entrada,['tamanhoLote'=>5,'maxLotes'=>1]);
        $token = (string)Str::uuid();
        DB::table('catalogo_importacoes')->where('id',$r['importacao_id'])->update(['lease_token'=>$token,'lease_ate'=>now()->addMinutes(30)]);
        try { $this->executar($entrada); $this->fail('Deveria recusar executor concorrente.'); }
        catch (RuntimeException $e) { self::assertStringContainsString('já está em execução',$e->getMessage()); }
        $this->assertDatabaseHas('catalogo_importacoes',['id'=>$r['importacao_id'],'estado'=>'validando','lease_token'=>$token,'cursor'=>5]);
        DB::table('catalogo_importacoes')->where('id',$r['importacao_id'])->update(['lease_ate'=>now()->subMinute()]);
        self::assertSame('validado',$this->executar($entrada)['estado']);
    }

    public function test_falha_no_checkpoint_reverte_lote_e_permite_retoma(): void
    {
        $entrada = $this->fonteCatalogo(); $falhou = false;
        DB::listen(function ($q) use (&$falhou) {
            if (!$falhou && str_starts_with(strtolower($q->sql),'update') && str_contains($q->sql,'catalogo_importacoes') && str_contains($q->sql,'cursor')) {
                $falhou = true;
                throw new RuntimeException('Falha fictícia no checkpoint.');
            }
        });
        try { $this->executar($entrada,['tamanhoLote'=>5]); $this->fail('Esperava falha.'); }
        catch (RuntimeException $e) { self::assertSame('Falha fictícia no checkpoint.',$e->getMessage()); }
        $this->assertDatabaseCount('catalogo_observacoes',0);
        $this->assertDatabaseHas('catalogo_importacoes',['estado'=>'falhou','cursor'=>0,'lease_token'=>null]);
        self::assertSame('validado',$this->executar($entrada)['estado']);
        $this->assertDatabaseCount('catalogo_observacoes',27);
    }

    public function test_observacao_adicional_incompativel_impede_validacao_final(): void
    {
        $entrada = $this->fonteCatalogo(); $r = $this->executar($entrada,['tamanhoLote'=>5,'maxLotes'=>1]);
        DB::table('catalogo_observacoes')->insert(['importacao_id'=>$r['importacao_id'],'ordem'=>6,'chave_fonte'=>'inep_escola:16000001','payload'=>'{}','payload_sha256'=>hash('sha256','{}'),'created_at'=>now()]);
        DB::table('catalogo_importacoes')->where('id',$r['importacao_id'])->update(['cursor'=>6]);
        try { $this->executar($entrada); $this->fail('Esperava recusa de integridade.'); }
        catch (RuntimeException $e) { self::assertStringContainsString('Manifesto das observações divergente',$e->getMessage()); }
        $this->assertDatabaseHas('catalogo_importacoes',['id'=>$r['importacao_id'],'estado'=>'falhou','validado_em'=>null]);
    }

    public function test_geografia_e_observacoes_recusam_update_delete_no_banco(): void
    {
        $r = $this->executar($this->fonteCatalogo());
        foreach (['catalogo_geografias','catalogo_observacoes'] as $tabela) {
            foreach (['update','delete'] as $acao) {
                try {
                    DB::transaction(function () use ($tabela, $acao) {
                        $q = DB::table($tabela);
                        $acao === 'delete' ? $q->delete() : $q->update(['created_at'=>now()]);
                    });
                    $this->fail('Esperava proteção de imutabilidade.');
                } catch (\Illuminate\Database\QueryException $e) {
                    self::assertStringContainsString('Observacao publica imutavel',$e->getMessage());
                }
            }
        }
        $this->assertDatabaseHas('catalogo_importacoes',['id'=>$r['importacao_id'],'estado'=>'validado']);
        $this->assertDatabaseCount('catalogo_observacoes',27);
    }

    public function test_executor_com_token_substituido_nao_grava_nem_encerra_novo_lease(): void
    {
        $entrada = $this->fonteCatalogo(); $trocou = false; $tokenNovo = (string)Str::uuid();
        DB::listen(function ($q) use (&$trocou, $tokenNovo) {
            if (!$trocou && str_starts_with(strtolower($q->sql),'update') && str_contains($q->sql,'catalogo_importacoes') && str_contains($q->sql,'lease_token')) {
                $trocou = true;
                DB::table('catalogo_importacoes')->update(['lease_token'=>$tokenNovo,'lease_ate'=>now()->addMinutes(30)]);
            }
        });
        try { $this->executar($entrada); $this->fail('Esperava recusa do token antigo.'); }
        catch (RuntimeException $e) { self::assertStringContainsString('Lease da importação',$e->getMessage()); }
        $this->assertDatabaseCount('catalogo_observacoes',0);
        $this->assertDatabaseHas('catalogo_importacoes',['estado'=>'validando','lease_token'=>$tokenNovo,'cursor'=>0]);
    }

    public function test_arquivo_modificado_durante_lote_nao_e_marcado_validado(): void
    {
        $entrada = $this->fonteCatalogo(); $alterou = false;
        DB::listen(function ($q) use (&$alterou, $entrada) {
            if (!$alterou && str_starts_with(strtolower($q->sql),'insert') && str_contains($q->sql,'catalogo_observacoes')) {
                $alterou = true;
                file_put_contents($entrada['arquivo'],"\n",FILE_APPEND);
            }
        });
        try { $this->executar($entrada); $this->fail('Esperava recusa do arquivo alterado.'); }
        catch (RuntimeException $e) { self::assertTrue(str_contains($e->getMessage(),'Arquivo mudou') || str_contains($e->getMessage(),'quantidade de campos')); }
        $this->assertDatabaseHas('catalogo_importacoes',['estado'=>'falhou','validado_em'=>null,'lease_token'=>null]);
    }

    public function test_hash_errado_ou_cobertura_incompleta_nao_gravam_staging(): void
    {
        $entrada = $this->fonteCatalogo();
        try { $this->executar($entrada,['sha256'=>str_repeat('0',64)]); $this->fail('Esperava recusa de hash.'); }
        catch (RuntimeException $e) { self::assertStringContainsString('SHA-256',$e->getMessage()); }
        $linhas = file($entrada['arquivo']); array_pop($linhas); file_put_contents($entrada['arquivo'],implode('',$linhas));
        $entrada['sha256'] = hash_file('sha256',$entrada['arquivo']);
        try { $this->executar($entrada); $this->fail('Esperava recusa nacional.'); }
        catch (RuntimeException $e) { self::assertStringContainsString('cobertura nacional incompleta',$e->getMessage()); }
        $this->assertDatabaseCount('catalogo_importacoes',0);
        $this->assertDatabaseCount('catalogo_geografias',0);
    }
}
