<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CredenciaisNaUrlTest extends TestCase
{
    public function test_credenciais_na_query_sao_recusadas_sem_refletir_segredo(): void
    {
        foreach (['token','access_token','api_token','password','password_confirmation','senha','senha_atual','senha_confirmation','current_password','_token','dados[token]','TOKEN'] as $campo) {
            $this->postJson('/api/v1/auth/login?'.http_build_query([$campo=>'segredo-ficticio']), ['email'=>'conta@example.test','password'=>'corpo-ficticio'])
                ->assertStatus(400)->assertDontSee('segredo-ficticio')
                ->assertHeader('Cache-Control','no-store, private');
        }
    }

    public function test_codigo_publico_e_parametros_de_consulta_nao_sao_confundidos_com_senha(): void
    {
        Route::get('/teste-ur1-consulta', fn()=>response()->json(['ok'=>true]));
        $this->getJson('/teste-ur1-consulta?codigo=G360-EXEMPLO&pagina=2&busca=turma')->assertOk();
    }

    public function test_credenciais_no_corpo_continuam_possiveis(): void
    {
        Route::post('/teste-ur1-corpo', fn()=>response()->json(['ok'=>true]));
        $this->postJson('/teste-ur1-corpo', ['token'=>'ficticio','password'=>'senha-ficticia'])->assertOk();
    }
}
