<?php

namespace Tests\Unit;

use App\Support\Catalogo\LeitorRelacoesCatalogo;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class LeitorRelacoesCatalogoTest extends TestCase
{
    private array $arquivos = [];
    protected function tearDown(): void { foreach($this->arquivos as $f) unlink($f); parent::tearDown(); }

    private function arquivo(array $linhas): string
    {
        $f = tempnam(sys_get_temp_dir(),'ci1-rel-'); $this->arquivos[] = $f;
        file_put_contents($f,"NU_ANO_CENSO;CO_ENTIDADE;CO_ESCOLA_SEDE_VINCULADA;CO_IES_OFERTANTE\n".implode("\n",$linhas)."\n");
        return $f;
    }

    public function test_preserva_bruto_normaliza_numero_ies_e_nao_inventa_relacao_para_zero(): void
    {
        $f = $this->arquivo(['2025;11000001;12000001;00000000000001','2025;12000001;0;','2025;13000001;;12345678901234']);
        $p = (new LeitorRelacoesCatalogo)->preparar($f,hash_file('sha256',$f),3);
        $r = iterator_to_array($p->linhas());
        self::assertSame(3,$p->manifesto['total']); self::assertSame(3,$p->manifesto['registros_fonte']);
        self::assertSame('12000001',$r[0]['alvo_codigo']); self::assertSame('inep_escola',$r[0]['alvo_namespace']);
        self::assertSame('00000000000001',$r[1]['alvo_codigo_bruto']); self::assertSame('1',$r[1]['alvo_codigo']);
        self::assertSame('12345678901234',$r[2]['alvo_codigo']);
        self::assertSame($r,iterator_to_array($p->linhas()));
        self::assertCount(1,iterator_to_array($p->linhas(2)));
    }

    public function test_ciclos_e_autorreferencia_sao_preservados_com_alertas(): void
    {
        $f = $this->arquivo(['2025;11000001;12000001;1','2025;12000001;11000001;','2025;13000001;13000001;']);
        $p = (new LeitorRelacoesCatalogo)->preparar($f,hash_file('sha256',$f),3);
        self::assertSame(['ciclo',null,'ciclo','autorreferencia'],array_column(iterator_to_array($p->linhas()),'alerta'));
        self::assertSame(3,$p->manifesto['alertas_grafo']);
    }

    public function test_hash_e_contagem_incompativeis_sao_recusados(): void
    {
        $f = $this->arquivo(['2025;11000001;;']);
        try { (new LeitorRelacoesCatalogo)->preparar($f,str_repeat('0',64),1); $this->fail('Esperava recusa de hash.'); }
        catch (RuntimeException $e) { self::assertStringContainsString('SHA-256',$e->getMessage()); }
        $this->expectException(RuntimeException::class); $this->expectExceptionMessage('Contagem');
        (new LeitorRelacoesCatalogo)->preparar($f,hash_file('sha256',$f),2);
    }

    public function test_codigo_invalido_nao_e_completado_por_adivinhacao(): void
    {
        $f = $this->arquivo(['2025;11000001;123;']);
        $this->expectException(RuntimeException::class); $this->expectExceptionMessage('dicionário');
        (new LeitorRelacoesCatalogo)->preparar($f,hash_file('sha256',$f),1);
    }

    public function test_origem_duplicada_e_recusada(): void
    {
        $f = $this->arquivo(['2025;11000001;;','2025;11000001;;1']);
        $this->expectException(RuntimeException::class); $this->expectExceptionMessage('duplicidade');
        (new LeitorRelacoesCatalogo)->preparar($f,hash_file('sha256',$f),2);
    }

    public function test_cabecalho_ano_e_quantidade_de_campos_incompativeis_sao_recusados(): void
    {
        foreach (['cabecalho','ano','campos'] as $caso) {
            $f = $this->arquivo(['2025;11000001;;']);
            $texto = file_get_contents($f);
            $texto = match($caso) {
                'cabecalho'=>str_replace('CO_IES_OFERTANTE','COLUNA_INDEVIDA',$texto),
                'ano'=>str_replace('2025;','2024;',$texto),
                default=>str_replace('2025;11000001;;','2025;11000001;',$texto),
            };
            file_put_contents($f,$texto);
            try { (new LeitorRelacoesCatalogo)->preparar($f,hash_file('sha256',$f),1); $this->fail('Esperava recusa de '.$caso); }
            catch (RuntimeException $e) { self::assertNotSame('',$e->getMessage()); }
        }
    }
}
