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
 * Função: Links assinados das fotos dos cartões: só abrem com assinatura válida e dentro do prazo.
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

        DB::statement('PRAGMA foreign_keys = OFF');
        DB::table('cartoes')->insert(['id' => 7, 'prova_id' => 1, 'imagem_url' => 'cartoes/1/foto.jpg', 'status' => 'lido']);
        $this->cartao = Cartao::find(7);
    }

    public function test_link_assinado_abre_a_imagem_sem_sessao(): void
    {
        $url = app(ImagensCartao::class)->url(7, 'miniatura');

        $this->assertStringStartsWith('/api/v1/cartoes/7/arquivo/miniatura?', $url);
        $this->get($url)->assertOk();
    }

    public function test_link_sem_assinatura_ou_alterado_e_recusado(): void
    {
        $url = app(ImagensCartao::class)->url(7, 'miniatura');

        $this->get('/api/v1/cartoes/7/arquivo/miniatura')->assertForbidden();
        $this->get(str_replace('/7/', '/8/', $url))->assertForbidden();
    }

    public function test_link_vencido_e_recusado(): void
    {
        $url = app(ImagensCartao::class)->url(7, 'miniatura');

        $this->travel(3)->hours();

        $this->get($url)->assertForbidden();
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
