<?php
namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AutoriaCabecalhoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withSession(['auth_token'=>'ficticio','auth_nome'=>'Pessoa fictícia', 'auth_vinculos'=>true]);
        Route::middleware('web')->get('/teste-cabecalho', function () {
            $interno=request('tipo')==='interno';
            $perfil=$interno?'suporte':(request('tipo')==='pessoal'?'professor_autonomo':(request('tipo')==='professor'?'professor':'coordenador'));
            request()->attributes->set('vinculo_handle',str_repeat('a',48));
            request()->attributes->set('vinculo_contexto',['perfil'=>$perfil,'tipo_contexto'=>$interno?'interno':'vinculo',
                'permissoes'=>['prova.ver','prova.editar','gabarito.editar'], 'espaco'=>['nome'=>request('tipo')==='pessoal'?'Meu espaço pessoal':'Instituição fictícia com nome longo','tipo'=>request('tipo')==='pessoal'?'pessoal':'escola']]);
            $p=['id'=>1,'titulo'=>'Prova fictícia','disciplina'=>'Matemática','serie'=>'1','num_questoes'=>1,'num_alternativas'=>4,
                'nota_maxima'=>10,'tipo_pontuacao'=>'igual','anular_se_todas'=>false,'opcao_lingua'=>false,'gerar_cartao_pdf'=>false,
                'status'=>request('rascunho')?'rascunho':'publicada','pode_editar'=>$perfil!=='professor','pode_copiar'=>false,'data_aplicacao'=>null,'turmas'=>[],'escola_nome'=>'Escola fictícia','criado_por_nome'=>request('sem_autor')?null:'Autor <fictício>'];
            return request('detalhe')?view('provas.show',['prova'=>$p,'turmas'=>[]]):view('provas.index',['provas'=>[$p],'kpis'=>['total'=>1],'haFiltros'=>false,'erroLista'=>false,'busca'=>'','disciplina'=>'','status'=>'']);
        });
    }

    public function test_layout_compartilhado_exibe_contexto_uma_vez_e_preserva_troca_e_menu_acessivel(): void
    {
        foreach (['escola','pessoal','interno'] as $tipo) {
            $r=$this->get('/teste-cabecalho?tipo='.$tipo)->assertOk()->assertSee($tipo==='interno'?'Trocar acesso':'Trocar vínculo')
                ->assertSee('data-sem-contexto',false)->assertSee('aria-controls="user-menu"',false)
                ->assertSee('aria-expanded="false"',false)->assertSee('id="user-menu" hidden',false)
                ->assertDontSee('badge badge-info badge-dot',false)->assertSee('type="submit" class="logout"',false);
            $this->assertSame(2,substr_count($r->getContent(),'contexto-ativo-link'));
            if ($tipo==='interno') $r->assertSee('Equipe Gabarito360')->assertDontSee('Instituição fictícia com nome longo');
            if ($tipo==='pessoal') $r->assertSee('Meu espaço pessoal');
        }
    }

    public function test_criador_e_escapado_em_lista_card_e_detalhe_com_fallback_explicito(): void
    {
        foreach (['','&detalhe=1'] as $detalhe) {
            $this->get('/teste-cabecalho?tipo=escola'.$detalhe)->assertOk()->assertSee('Criada por')
                ->assertSee('Autor &lt;fictício&gt;',false)->assertDontSee('Autor <fictício>',false);
            $this->get('/teste-cabecalho?tipo=escola&sem_autor=1'.$detalhe)->assertOk()->assertSee('Autoria não registrada');
        }
    }

    public function test_professor_nao_recebe_acoes_de_edicao_da_prova_institucional(): void
    {
        $this->get('/teste-cabecalho?tipo=professor&rascunho=1')->assertOk()->assertSee('Criada por:')
            ->assertDontSee('title="Publicar"',false)->assertDontSee('class="btn btn-sm btn-secondary" data-copiar-prova',false);
        $this->get('/teste-cabecalho?tipo=professor&detalhe=1&rascunho=1')->assertOk()
            ->assertDontSee('onclick="abrirModal()"',false)->assertDontSee('Editar gabarito →')
            ->assertSee('Aguarde a configuração do gabarito pela coordenação ou direção.');
    }
}
