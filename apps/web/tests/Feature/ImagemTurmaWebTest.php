<?php
namespace Tests\Feature;

use App\Support\{ContextoWeb,NavegacaoWeb};
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\Support\{ContextoSelecionado,NavegacaoHttp};
use Tests\TestCase;

/** Ensaios independentes do consumidor BFF, sem API ou dados reais. */
class ImagemTurmaWebTest extends TestCase
{
    use ContextoSelecionado,NavegacaoHttp;
    private array $me;
    private mixed $estado=['avatar_id'=>null,'tem_foto'=>true,'imagem_versao'=>2,'token'=>'segredo-ficticio','foto_path'=>'privado.webp'];
    private int $status=200;
    private bool $falha=false;
    private string $mime='image/webp';

    private function contexto(bool $editar=true): void
    {
        config(['services.api.base_url'=>'http://api.test/api']);Http::preventStrayRequests();
        $this->withSession(['auth_token'=>'identidade-ficticia','auth_vinculos'=>true,'auth_nome'=>'Pessoa fictícia']);
        $this->me=['vinculo_id'=>7,'tipo_contexto'=>'vinculo','token'=>'contexto-ficticio','perfil'=>'secretario_escolar',
            'permissoes'=>$editar?['turma.ver','turma.gerir']:['turma.ver'],'id'=>1,'nome'=>'Pessoa fictícia','espaco'=>['id'=>5,'tipo'=>'escola','nome'=>'Escola fictícia']];
        Http::fake(function($r){
            $p=parse_url($r->url(),PHP_URL_PATH);
            if($p==='/api/v1/auth/me')return Http::response(['data'=>$this->me]);
            if($p==='/api/v1/turmas/14/imagem'){
                if($this->falha)throw new ConnectionException('diagnostico-privado');
                return Http::response(['data'=>$this->estado,'message'=>'Falha fictícia'],$this->status,['Retry-After'=>'9']);
            }
            if($p==='/api/v1/turmas/14/foto')return Http::response('webp-ficticio',$this->status,['Content-Type'=>$this->mime]);
            if($p==='/api/v1/turmas/14')return Http::response(['data'=>['id'=>14,'nome'=>'Turma <fictícia>','escola_nome'=>'Escola fictícia','serie'=>'3','ano_letivo'=>2026,'ativo'=>true,'alunos'=>[],'total_alunos'=>0,'avatar_id'=>null,'tem_foto'=>true,'imagem_versao'=>1]]);
            return Http::response(['data'=>[]]);
        });
        $this->selecionarContextoFixture($this->me);
    }

    public function test_leitor_nao_recebe_editor_nem_envia_mutacao(): void
    {
        $this->contexto(false);
        $this->consultarTela('/app/turmas/14')->assertOk()->assertDontSee('data-abrir-imagem-turma',false)->assertDontSee('id="imagem-turma-modal"',false);
        $this->alterarRecursoJson('POST','/app/turmas/14/imagem',['tipo'=>'avatar','avatar_id'=>1])->assertForbidden();
        Http::assertNotSent(fn($r)=>str_ends_with($r->url(),'/turmas/14/imagem'));
    }

    public function test_editor_ssr_preserva_formulario_contextual_e_dados_escapados(): void
    {
        $this->contexto();
        $r=$this->consultarTela('/app/turmas/14')->assertOk()->assertSee('id="imagem-turma-modal"',false)
            ->assertSee('capture="user"',false)->assertSee('brasão')->assertDontSee('Turma <fictícia>',false);
        $f=$this->extrairFormularioRecurso($r->getContent(),'turmas.imagem',['id'=>14]);
        $this->assertSame('turmas.imagem',NavegacaoWeb::obter($f['destino']['referencia'])['nome']);
        $this->assertStringNotContainsString('/14',$f['url']);$this->assertStringNotContainsString('?',$f['url']);
    }

    public function test_foto_encaminha_so_arquivo_e_tipo_com_bearer_servidor_e_json_projetado(): void
    {
        $this->contexto();
        $r=$this->alterarRecursoJson('POST','/app/turmas/14/imagem',['tipo'=>'foto','foto'=>UploadedFile::fake()->image('ficticia.png'),'token'=>'nao-encaminhar','espaco_id'=>99]);
        $r->assertOk()->assertJsonPath('data.tem_foto',true)->assertJsonMissingPath('data.token')->assertJsonMissingPath('data.foto_path');
        $d=$r->json('data.foto_url');$this->assertIsArray($d);$this->assertStringNotContainsString('14',$d['url']);
        $alvo=NavegacaoWeb::obter($d['referencia']);$this->assertSame('turmas.foto',$alvo['nome']);$this->assertSame(14,(int)$alvo['parametros']['id']);
        Http::assertSent(fn($r)=>str_ends_with($r->url(),'/turmas/14/imagem')&&$r->method()==='POST'&&$r->hasHeader('Authorization','Bearer contexto-ficticio')
            &&str_contains($r->body(),'name="foto"')&&!str_contains($r->body(),'nao-encaminhar')&&!str_contains($r->body(),'name="espaco_id"'));
    }

    public function test_shapes_invalidos_nao_confirmam_sucesso_e_limite_preserva_retry_after(): void
    {
        $this->contexto();
        foreach([null,[],['tem_foto'=>true,'avatar_id'=>null,'imagem_versao'=>'2'],['tem_foto'=>true,'avatar_id'=>37,'imagem_versao'=>2]]as$d){
            $this->estado=$d;$this->alterarRecursoJson('POST','/app/turmas/14/imagem',['tipo'=>'avatar'])->assertStatus(502)->assertJsonMissingPath('data');
        }
        $this->status=429;$this->alterarRecursoJson('POST','/app/turmas/14/imagem',['tipo'=>'avatar'])->assertStatus(429)->assertHeader('Retry-After','9');
        $this->status=200;$this->falha=true;
        $this->alterarRecursoJson('POST','/app/turmas/14/imagem',['tipo'=>'avatar'])->assertStatus(503)->assertDontSee('diagnostico-privado');
    }

    public function test_foto_binaria_nao_publica_tipo_indevido_nem_contorna_revisao(): void
    {
        $this->contexto(false);
        $this->consultarTela('/app/turmas/14/foto')->assertOk()->assertHeader('Content-Type','image/webp')->assertHeader('Cache-Control','no-store, private')->assertHeader('X-Content-Type-Options','nosniff');
        $this->mime='text/html';$this->consultarTela('/app/turmas/14/foto')->assertNotFound();
        $this->mime='image/webp';$this->status=403;$this->consultarTela('/app/turmas/14/foto')->assertForbidden()->assertSessionHas('auth_token','identidade-ficticia');
        $this->status=200;
        $desc=NavegacaoWeb::destino('turmas.foto',['id'=>14]);
        $antes=Http::recorded()->count();
        $this->postJson($desc['url'],['_recurso'=>$desc['referencia'],'_revisao'=>'revisao-antiga','_token'=>csrf_token()],['X-G360-Hidratar'=>'1'])->assertStatus(409);
        Http::assertSentCount($antes); // Revisão antiga não chama nem /me nem arquivo.
    }

    public function test_revogacao_entre_me_e_operacao_nao_e_mascarada_como_erro_de_formulario(): void
    {
        $this->contexto();$this->status=401;
        $this->alterarRecursoJson('POST','/app/turmas/14/imagem',['tipo'=>'avatar','avatar_id'=>1])
            ->assertUnauthorized()->assertJsonPath('login_necessario',true)->assertSessionMissing('auth_token');
        Http::assertSent(fn($r)=>str_ends_with($r->url(),'/auth/logout')&&$r->method()==='POST'&&$r->hasHeader('Authorization','Bearer identidade-ficticia'));
        $this->contexto();$this->status=401;
        $this->consultarJson('/app/turmas/14/foto')->assertUnauthorized()->assertJsonPath('login_necessario',true)->assertSessionMissing('auth_token');
    }
}
