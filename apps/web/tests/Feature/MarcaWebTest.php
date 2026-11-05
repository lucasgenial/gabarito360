<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MarcaWebTest extends TestCase
{
    private function sociais(string $html): array
    {
        $doc=new \DOMDocument(); @$doc->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        $xpath=new \DOMXPath($doc); $dados=[];
        foreach ($xpath->query('//head/meta[starts-with(@property,"og:") or starts-with(@name,"twitter:")]') as $meta) {
            $chave=$meta->getAttribute('property')?:$meta->getAttribute('name');
            $this->assertArrayNotHasKey($chave,$dados,'Metadado social duplicado.');
            $dados[$chave]=$meta->getAttribute('content');
        }
        $dados['canonical']=$xpath->evaluate('string(//head/link[@rel="canonical"]/@href)');
        return $dados;
    }

    public function test_previa_social_publica_tem_url_limpa_imagem_absoluta_e_contexto_da_pagina(): void
    {
        Http::preventStrayRequests(); Http::fake(['*'=>Http::response(['data'=>[]])]);
        foreach (['/'=>'Avaliações, correção e resultados','/planos'=>'Modalidades de atendimento','/fale-conosco'=>'Fale com o Gabarito360'] as $path=>$titulo) {
            $res=$this->get('https://homologa.gabarito360.com.br'.$path.'?utm_source=campanha&referencia=nao-publicar')->assertOk();
            $m=$this->sociais($res->getContent());
            $this->assertSame('https://homologa.gabarito360.com.br'.$path,$m['og:url']);
            $this->assertSame($m['og:url'],$m['canonical']);
            $this->assertStringContainsString($titulo,$m['og:title']);
            $this->assertSame('https://homologa.gabarito360.com.br/brand/gabarito360-og.png',$m['og:image']);
            $this->assertSame($m['og:image'],$m['og:image:secure_url']);
            $this->assertSame('image/png',$m['og:image:type']);
            $this->assertSame('pt_BR',$m['og:locale']);
            $this->assertSame('1200',$m['og:image:width']); $this->assertSame('630',$m['og:image:height']);
            $this->assertSame('summary_large_image',$m['twitter:card']);
            $this->assertStringNotContainsString('nao-publicar',implode(' ',$m));
            $this->assertStringNotContainsString('utm_source',implode(' ',$m));
        }
    }

    public function test_rota_privada_nao_expoe_identificadores_ou_parametros_nos_metadados_sociais(): void
    {
        \Illuminate\Support\Facades\Route::get('/qa-privado/{aluno}',fn()=>view('layouts._marca-head',['tituloSocial'=>'Pessoa privada','descricaoSocial'=>'Nota privada']))->name('qa.privado');
        $m=$this->sociais('<html><head>'.$this->get('https://homologa.gabarito360.com.br/qa-privado/91827?aluno_id=91827&referencia=nao-publicar&_ctx=contexto')->assertOk()->getContent().'</head></html>');
        $this->assertSame('https://homologa.gabarito360.com.br/',$m['og:url']);
        $this->assertSame($m['og:url'],$m['canonical']);
        foreach (['91827','nao-publicar','contexto','Pessoa privada','Nota privada'] as $dado) $this->assertStringNotContainsString($dado,implode(' ',$m));
    }

    public function test_credencial_na_url_e_rejeitada_antes_da_renderizacao_social_sem_refletir_segredo(): void
    {
        Http::preventStrayRequests();
        foreach (['/','/planos','/fale-conosco'] as $path) {
            $this->get($path.'?token=segredo-ficticio-nao-refletir')->assertStatus(400)
                ->assertDontSee('segredo-ficticio-nao-refletir')->assertDontSee('property="og:',false)
                ->assertHeader('Cache-Control','no-store, private');
        }
        Http::assertNothingSent();
    }

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

    public function test_rodape_preserva_redes_oficiais_incluindo_youtube_sem_requisicoes_externas(): void
    {
        Http::preventStrayRequests();
        $html=Blade::render('<x-rodape-publico />');
        foreach ([
            'Instagram'=>'https://www.instagram.com/gabarito360/',
            'Facebook'=>'https://www.facebook.com/gabarito360/',
            'TikTok'=>'https://www.tiktok.com/@gabarito360',
            'YouTube'=>'https://www.youtube.com/@gabarito360',
        ] as $rede=>$url) {
            $this->assertStringContainsString('href="'.$url.'" target="_blank" rel="noopener noreferrer" aria-label="'.$rede.' @gabarito360 (abre em nova aba)"',$html);
            $this->assertStringContainsString($rede.' · @gabarito360',$html);
        }
        $doc=new \DOMDocument(); @$doc->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        $linhas=(new \DOMXPath($doc))->query('//ul[contains(@class,"rodape-redes")]/li[contains(.,"YouTube")]');
        $this->assertSame(1,$linhas->length,'Canal ativo não deve repetir um placeholder Em breve.');
        Http::assertNothingSent();
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
