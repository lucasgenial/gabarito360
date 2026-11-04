<?php
namespace Tests\Feature;
use App\Support\ContextoWeb;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImagemAlunoWebTest extends TestCase
{
    private function contexto(bool $editar=true,bool $limite=false): string
    {
        config(['services.api.base_url'=>'http://api.test/api']);Http::preventStrayRequests();
        $this->withSession(['auth_token'=>'identidade','auth_vinculos'=>true,'auth_nome'=>'Pessoa fictícia']);
        $this->withCookie(config('session.cookie'),session()->getId())->withCredentials();
        $d=['token'=>'escolar','perfil'=>'secretario_escolar','permissoes'=>$editar?['aluno.ver','aluno.gerir']:['aluno.ver'],
            'id'=>1,'nome'=>'Pessoa fictícia','espaco'=>['id'=>5,'tipo'=>'escola','nome'=>'Escola fictícia']];
        Http::fake(function($r)use($d,$limite){
            if(str_ends_with($r->url(),'/auth/me'))return Http::response(['data'=>$d]);
            if($limite&&str_ends_with($r->url(),'/alunos/7/imagem'))return Http::response(['message'=>'Too Many Requests.'],429,['Retry-After'=>'9']);
            if(str_ends_with($r->url(),'/alunos/7/imagem'))return Http::response(['data'=>['avatar_id'=>null,'tem_foto'=>true,'imagem_versao'=>2,'token'=>'nao-devolver','foto_path'=>'privado.webp']]);
            if(str_ends_with($r->url(),'/alunos/7/foto'))return Http::response('webp-ficticio',200,['Content-Type'=>'image/webp']);
            if(str_ends_with($r->url(),'/alunos/7'))return Http::response(['data'=>['id'=>7,'nome'=>'Aluno fictício','matricula'=>'IMG-7','ativo'=>true,'turma_id'=>1,'avatar_id'=>8,'tem_foto'=>false]]);
            if(str_ends_with($r->url(),'/turmas'))return Http::response(['data'=>['turmas'=>[]]]);
            return Http::response(['data'=>[]]);
        });
        return ContextoWeb::criar($d);
    }
    public function test_imagem_aluno_so_pode_ser_alterada_por_quem_edita_o_cadastro(): void
    {
        $id=$this->contexto(false);
        $this->get('/alunos/7?_ctx='.$id)->assertOk()->assertDontSee('data-abrir-imagem-aluno',false)->assertDontSee('id="imagem-aluno-modal"',false);
        $this->postJson('/alunos/7/imagem',['_ctx'=>$id,'tipo'=>'avatar','avatar_id'=>8])->assertForbidden();
        Http::assertNotSent(fn($r)=>str_ends_with($r->url(),'/alunos/7/imagem'));
    }
    public function test_modal_existente_tem_camera_e_arquivo_e_retorno_json_privado(): void
    {
        $id=$this->contexto();
        $this->get('/alunos/7?_ctx='.$id)->assertOk()->assertSee('Imagem de Aluno fictício')->assertSee('Salvar imagem')->assertSee('capture="user"',false)->assertSee('Escolher arquivo');
        $this->postJson('/alunos/7/imagem',['_ctx'=>$id,'tipo'=>'foto','foto'=>UploadedFile::fake()->image('ficticio.jpg')])
            ->assertOk()->assertJsonPath('data.tem_foto',true)->assertJsonMissingPath('data.token')->assertJsonMissingPath('data.foto_path');
        $this->get('/alunos/7/foto?_ctx='.$id)->assertOk()->assertHeader('Cache-Control','no-store, private');
    }
    public function test_foto_do_novo_aluno_e_encaminhada_junto_ao_cadastro(): void
    {
        $id=$this->contexto();
        $this->post('/alunos',['_ctx'=>$id,'nome'=>'Aluno fictício','matricula'=>'IMG-7','turma_id'=>1,'data_nascimento'=>'2010-01-01','foto'=>UploadedFile::fake()->image('ficticio.jpg')])->assertRedirect();
        Http::assertSent(fn($r)=>str_ends_with($r->url(),'/alunos')&&$r->method()==='POST'&&str_contains($r->body(),'name="foto"'));
    }
    public function test_limite_real_da_imagem_mantem_prazo_e_erro_em_portugues():void
    {
        $id=$this->contexto(true,true);
        $this->postJson('/alunos/7/imagem',['_ctx'=>$id,'tipo'=>'avatar','avatar_id'=>8])->assertStatus(429)->assertHeader('Retry-After','9')->assertJsonPath('retry_after',9)->assertJsonMissingPath('data.foto_url')->assertJsonPath('message','Muitas tentativas seguidas. Aguarde alguns instantes e tente novamente.');
    }
}
