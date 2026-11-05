<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ValidacaoDocumentoWebTest extends TestCase
{
    public function test_pagina_e_consulta_sao_publicas_e_nao_encaminham_token_da_sessao(): void
    {
        config(['services.api.base_url'=>'http://api.test/api']);
        Http::preventStrayRequests();
        Http::fake(['api.test/api/v1/documentos/validar'=>Http::response(['data'=>['estado'=>'emissao_encontrada']])]);
        $this->get('/validar-documento')->assertOk()->assertSee('Conferir documento')->assertSee('50 MB')->assertSee('validacao-documento.js');
        $this->withSession(['auth_token'=>'privado-nao-encaminhar'])->postJson('/validar-documento',['codigo'=>'G360-'.str_repeat('A',32)])
            ->assertOk()->assertJsonPath('data.estado','emissao_encontrada');
        Http::assertSent(fn($r)=>!$r->hasHeader('Authorization') && !isset($r['arquivo']));
        $this->postJson('/validar-documento',['codigo'=>'invalido'])->assertUnprocessable();
    }
}
