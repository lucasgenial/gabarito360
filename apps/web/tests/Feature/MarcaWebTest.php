<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MarcaWebTest extends TestCase
{
    public function test_paginas_publicas_e_acesso_renderizam_marca_sem_depender_da_api(): void
    {
        Http::preventStrayRequests();
        Http::fake(['*' => Http::response([],503)]);
        foreach (['/','/login'] as $pagina) {
            $this->get($pagina)->assertOk()->assertSee('css/marca.css',false)
                ->assertSee('favicon.svg',false)->assertSee('site.webmanifest',false)
                ->assertSee('brand/gabarito360-og.png',false)
                ->assertSee('class="g360-six">6',false)
                ->assertDontSee('<span class="logo">G360</span>',false);
        }
    }

    public function test_icone_recolhido_tem_um_nome_acessivel_sem_wordmark_ou_animacao_padrao(): void
    {
        $html=Blade::render('<x-marca variant="icon" size="sm" theme="dark" />');
        $this->assertSame(1,substr_count($html,'aria-label="Gabarito360"'));
        $this->assertStringContainsString('aria-hidden="true"',$html);
        $this->assertStringNotContainsString('class="g360-wordmark"',$html);
        $this->assertStringNotContainsString('g360-marca-animada',$html);
        $this->assertStringNotContainsString('DOCTYPE',$html);
        $this->assertSame(2,substr_count($html,'<path'));
    }

    public function test_animacao_e_opt_in_e_marca_horizontal_preserva_os_dois_estilos_de_fonte(): void
    {
        $html=Blade::render('<x-marca size="lg" theme="light" :animated="true" />');
        $this->assertStringContainsString('g360-marca-animada',$html);
        $this->assertStringContainsString('data-marca-theme="light"',$html);
        $this->assertStringContainsString('Gabarito<span class="g360-numeros">3<span class="g360-six">6</span>0',$html);
    }
}
