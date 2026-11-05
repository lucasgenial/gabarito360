<?php
namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CabecalhoDocumentoWebTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withSession(['auth_vinculos'=>true,'auth_token'=>'ficticio']);
        Route::middleware('web')->get('/teste-identidade-documento', function () {
            $auto=request('tipo')==='autonomo';$rede=request('tipo')==='rede';
            request()->attributes->set('vinculo_contexto',['perfil'=>$auto?'professor_autonomo':'coordenador',
                'espaco'=>['tipo'=>$auto?'pessoal':'escola']]);
            return view('relatorios._cabecalho',['titulo'=>'Título fictício','cabecalho'=>[
                'nome'=>'Nome fictício','escola_id'=>8,'rede_id'=>$rede?2:null,'rede'=>$rede?'Rede fictícia':null,
                'tem_logo'=>true,'tem_logo_rede'=>false]]);
        });
    }

    public function test_rede_sem_logo_nao_e_substituida_pela_escola_e_escola_sem_rede_nao_duplica_logo(): void
    {
        $r=$this->get('/teste-identidade-documento?tipo=rede')->assertOk();
        $html=$r->getContent();
        $this->assertLessThan(strpos($html,'class="ic-textos"'),strpos($html,'class="ic-logo ic-espaco"'));
        $this->assertGreaterThan(strpos($html,'class="ic-textos"'),strpos($html,'alt="Logo da escola"'));
        $r=$this->get('/teste-identidade-documento?tipo=escola')->assertOk();
        $this->assertSame(1,substr_count($r->getContent(),'alt="Logo da escola"'));
        $this->assertLessThan(strpos($r->getContent(),'class="ic-textos"'),strpos($r->getContent(),'alt="Logo da escola"'));
    }

    public function test_uso_pessoal_nao_assume_identidade_institucional(): void
    {
        $this->get('/teste-identidade-documento?tipo=autonomo')->assertOk()
            ->assertSee('Uso autônomo')->assertSee('Espaço pessoal: ')
            ->assertSee('aria-label="Gabarito360"',false)->assertDontSee('alt="Logo da escola"',false);
    }
}
