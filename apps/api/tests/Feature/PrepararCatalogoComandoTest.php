<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\Support\ArquivosCatalogo;
use Tests\TestCase;

class PrepararCatalogoComandoTest extends TestCase
{
    use ArquivosCatalogo;

    protected function tearDown(): void
    {
        $this->limparArquivosCatalogo();
        parent::tearDown();
    }

    public function test_simulacao_nacional_nao_acessa_banco(): void
    {
        $entrada = $this->fonteCatalogo();
        DB::shouldReceive('connection')->never();
        $this->artisan('g360:catalogo-preparar',[
            'arquivo'=>$entrada['arquivo'],'--ufs'=>$entrada['ufs'],'--municipios'=>$entrada['municipios'],
            '--sha256'=>$entrada['sha256'],'--encoding'=>'UTF-8','--todas-ufs'=>true,'--dry-run'=>true,
        ])->expectsOutputToContain('validacao_sem_gravacao')->assertSuccessful();
    }

    public function test_exige_cobertura_nacional_e_hash_explicitos(): void
    {
        DB::shouldReceive('connection')->never();
        $this->artisan('g360:catalogo-preparar',['arquivo'=>'arquivo-inexistente.csv'])
            ->expectsOutputToContain('Exige --todas-ufs e --sha256 esperado')->assertFailed();
    }
}
