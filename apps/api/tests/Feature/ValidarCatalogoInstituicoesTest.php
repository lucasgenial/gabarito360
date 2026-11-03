<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ValidarCatalogoInstituicoesTest extends TestCase
{
    private array $arquivos = [];

    protected function tearDown(): void
    {
        foreach ($this->arquivos as $arquivo) unlink($arquivo);
        parent::tearDown();
    }

    private function arquivo(string $conteudo): string
    {
        $arquivo = tempnam(sys_get_temp_dir(), 'ci1-cli-');
        $this->arquivos[] = $arquivo;
        file_put_contents($arquivo, $conteudo);
        return $arquivo;
    }

    private function argumentos(bool $duplicar = false): array
    {
        $linha = '2025;29428254;Escola fictícia;BA;29;2925303;Porto Seguro;2;1';
        $csv = "NU_ANO_CENSO;CO_ENTIDADE;NO_ENTIDADE;SG_UF;CO_UF;CO_MUNICIPIO;NO_MUNICIPIO;TP_DEPENDENCIA;TP_SITUACAO_FUNCIONAMENTO\n".$linha."\n".($duplicar ? $linha."\n" : '');
        $uf = ['id'=>29,'sigla'=>'BA'];
        return [
            'arquivo'=>$this->arquivo($csv), '--encoding'=>'UTF-8',
            '--ufs'=>$this->arquivo(json_encode([$uf])),
            '--municipios'=>$this->arquivo(json_encode([['id'=>2925303,'nome'=>'Porto Seguro','microrregiao'=>['mesorregiao'=>['UF'=>$uf]]]])),
        ];
    }

    public function test_valida_sem_consultar_ou_gravar_banco(): void
    {
        DB::shouldReceive('connection')->never();
        $this->withoutMockingConsoleOutput();
        self::assertSame(0, Artisan::call('g360:catalogo-validar', $this->argumentos()));
        $manifesto = json_decode(Artisan::output(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('validacao_sem_gravacao', $manifesto['modo']);
        self::assertSame(1, $manifesto['registros']);
    }

    public function test_rejeita_duplicidade_e_carga_parcial_declarada_nacional(): void
    {
        $this->artisan('g360:catalogo-validar', $this->argumentos(true))
            ->expectsOutputToContain('Identificador duplicado')->assertFailed();
        $this->artisan('g360:catalogo-validar', $this->argumentos()+['--nacional'=>true])
            ->expectsOutputToContain('27 UFs')->assertFailed();
    }

    public function test_hash_divergente_interrompe_antes_da_leitura(): void
    {
        $this->artisan('g360:catalogo-validar', $this->argumentos()+['--sha256'=>str_repeat('0',64)])
            ->expectsOutputToContain('SHA-256 da fonte divergente')->assertFailed();
    }
}
