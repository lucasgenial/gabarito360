<?php

namespace Tests\Unit;

use App\Support\Catalogo\LeitorInstituicoes;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class LeitorInstituicoesTest extends TestCase
{
    private array $arquivos = [];

    protected function tearDown(): void
    {
        foreach ($this->arquivos as $arquivo) unlink($arquivo);
        parent::tearDown();
    }

    private function arquivo(string $conteudo): string
    {
        $arquivo = tempnam(sys_get_temp_dir(), 'ci1-');
        $this->arquivos[] = $arquivo;
        file_put_contents($arquivo, $conteudo);
        return $arquivo;
    }

    private function ler(string $fonte, array $dados): array
    {
        $cabecalho = LeitorInstituicoes::COLUNAS[$fonte];
        if ($fonte === 'superior') $cabecalho[] = 'SG_IES';
        $arquivo = $this->arquivo(implode(';', $cabecalho)."\n".implode(';', $dados)."\n");
        $leitor = new LeitorInstituicoes(['2925303'=>['nome'=>'Porto Seguro','uf'=>'BA','uf_codigo'=>29]]);
        return iterator_to_array($leitor->ler($arquivo, $fonte, $fonte === 'basica' ? 2025 : 2024, 'UTF-8'))[0];
    }

    public function test_uf_ausente_exige_codigo_oficial_coerente_e_preserva_proveniencia(): void
    {
        $r = $this->ler('basica', [2025,'29428254','Escola fictícia','','29','2925303','Porto Seguro','2','4']);
        self::assertSame('BA', $r['uf']);
        self::assertTrue($r['uf_derivada_ibge']);
        self::assertSame('', $r['uf_original']);
        self::assertSame('4', $r['situacao_fonte']);
        self::assertNull($r['sigla']);
        self::assertNotContains('DS_ENDERECO', $r['campos_presentes']);
    }

    public function test_uf_divergente_nao_e_corrigida_silenciosamente(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('UF e município divergentes');
        $this->ler('basica', [2025,'29428254','Escola fictícia','RS','29','2925303','Porto Seguro','2','1']);
    }

    public function test_superior_preserva_tipo_sigla_namespace_e_localizacao_da_sede(): void
    {
        foreach (LeitorInstituicoes::TIPOS_IES as $codigo => $tipo) {
            $r = $this->ler('superior', [2024,'123','Instituição fictícia','BA','29','2925303','Porto Seguro',$codigo,'5','IFICT']);
            self::assertSame($tipo, $r['tipo']);
            self::assertSame('IFICT', $r['sigla']);
            self::assertSame('inep_ies', $r['namespace']);
            self::assertSame('sede_administrativa', $r['natureza_localizacao']);
            self::assertSame('5', $r['dependencia_fonte']);
        }
    }

    public function test_tipo_desconhecido_nao_vira_faculdade_por_padrao(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('tipo de instituição não reconhecido');
        $this->ler('superior', [2024,'123','Instituição fictícia','BA','29','2925303','Porto Seguro','99','5','']);
    }

    public function test_geografia_admite_hierarquia_nova_do_ibge(): void
    {
        $uf = ['id'=>29,'sigla'=>'BA'];
        $ufs = $this->arquivo(json_encode([$uf]));
        $municipios = $this->arquivo(json_encode([['id'=>2925303,'nome'=>'Porto Seguro','microrregiao'=>null,'regiao-imediata'=>['regiao-intermediaria'=>['UF'=>$uf]]]]));
        $geo = LeitorInstituicoes::geografia($ufs, $municipios);
        self::assertSame('BA', $geo['municipios']['2925303']['uf']);
        self::assertSame(29, $geo['municipios']['2925303']['uf_codigo']);
    }
}
