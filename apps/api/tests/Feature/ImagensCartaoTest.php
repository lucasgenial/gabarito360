<?php

namespace Tests\Feature;

use App\Models\Cartao;
use App\Services\Omr\ImagensCartao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Arquivo: apps/api/tests/Feature/ImagensCartaoTest.php
 * Função: Endereços de fotos não concedem acesso; legado assinado permanece desativado.
 */
class ImagensCartaoTest extends TestCase
{
    use RefreshDatabase;

    private Cartao $cartao;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::disk('public')->put('cartoes/1/foto.jpg', 'original');
        Storage::disk('public')->put(ImagensCartao::caminho(7, 'miniatura'), 'leve');

        DB::statement('PRAGMA defer_foreign_keys = ON'); // a transação do teste nunca confirma
        DB::table('cartoes')->insert(['id' => 7, 'prova_id' => 1, 'imagem_url' => 'cartoes/1/foto.jpg', 'status' => 'lido']);
        $this->cartao = Cartao::find(7);
    }

    public function test_endereco_do_arquivo_nao_contem_segredo_e_exige_autenticacao(): void
    {
        $url = app(ImagensCartao::class)->url(7, 'miniatura');

        $this->assertSame('/api/v1/cartoes/7/imagens/miniatura', $url);
        $this->getJson($url)->assertUnauthorized();
    }

    public function test_endereco_antigo_nao_abre_imagem_nem_com_assinatura_valida(): void
    {
        $url = \Illuminate\Support\Facades\URL::temporarySignedRoute('cartoes.arquivo', now()->addHour(), ['id'=>7, 'tipo'=>'miniatura'], false);
        $this->get('/api/v1/cartoes/7/arquivo/miniatura')->assertNotFound();
        $this->get($url)->assertNotFound();
        $this->get(str_replace('/7/', '/8/', $url))->assertNotFound();
    }

    public function test_copiar_o_endereco_nao_concede_acesso_em_outro_momento(): void
    {
        $url = app(ImagensCartao::class)->url(7, 'miniatura');

        $this->travel(3)->hours();

        $this->getJson($url)->assertUnauthorized();
    }

    public function test_link_fica_igual_dentro_da_mesma_hora(): void
    {
        $this->travelTo(now()->startOfHour()->addMinutes(5));
        $antes = app(ImagensCartao::class)->url(7, 'miniatura');

        $this->travel(20)->minutes();

        $this->assertSame($antes, app(ImagensCartao::class)->url(7, 'miniatura'));
    }

    public function test_excluir_o_cartao_apaga_as_versoes_leves(): void
    {
        $this->cartao->delete();

        Storage::disk('public')->assertMissing(ImagensCartao::caminho(7, 'miniatura'));
    }
}
