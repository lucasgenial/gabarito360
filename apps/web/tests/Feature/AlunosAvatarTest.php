<?php
namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AlunosAvatarTest extends TestCase
{
    public function test_avatar_acentos_espacos_cor_estavel_e_escape(): void
    {
        $render=fn($nome,$id)=>Blade::render('<x-avatar :nome="$nome" :aluno-id="$id" />',compact('nome','id'));
        $html=$render("  ágata   érica  ",7);
        $this->assertStringContainsString('>ÁÉ</span>',$html);
        $this->assertStringContainsString('avatar-aluno--neutro',$html);
        $this->assertStringContainsString('avatar-aluno--neutro',$render('Outro Nome',7));
        $this->assertStringContainsString('avatar-aluno--azul',$render('Outro Nome',8));
        $this->assertStringContainsString('>?</span>',$render('',7));
        $this->assertStringContainsString('&lt;',$render('<script> teste',7));
        $this->assertStringNotContainsString('<script>',$render('<script> teste',7));
    }

    public function test_web_rejeita_upload_e_caminho_sem_enviar_para_api(): void
    {
        Http::preventStrayRequests();
        $this->withSession(['auth_token'=>'ficticio']);
        foreach (['POST'=>'/alunos','PUT'=>'/alunos/7'] as $metodo=>$url) {
            foreach (['foto'=>UploadedFile::fake()->image('foto.jpg'),'foto_path'=>'alunos/foto.jpg'] as $campo=>$valor) {
                $this->{strtolower($metodo)}($url,['nome'=>'Aluno fictício','matricula'=>'FICTICIO-7',
                    'turma_id'=>1,'data_nascimento'=>'2010-01-01',$campo=>$valor])
                    ->assertRedirect()->assertSessionHasErrors($campo);
            }
        }
        Http::assertNothingSent();
    }

    public function test_formulario_tem_avatar_e_nao_oferece_upload(): void
    {
        Http::preventStrayRequests();
        Http::fake(['*'=>Http::response(['data'=>['turmas'=>[]]])]);
        $this->withSession(['auth_token'=>'ficticio'])->get('/alunos/novo')->assertOk()
            ->assertSee('id="preview-avatar"',false)->assertDontSee('type="file"',false)
            ->assertDontSee('multipart/form-data',false);
    }

    public function test_galeria_tem_36_opcoes_e_recupera_selecao_apos_erro(): void
    {
        Http::preventStrayRequests();
        Http::fake(['*'=>Http::response(['data'=>['turmas'=>[]]])]);
        $html=$this->withSession(['auth_token'=>'ficticio','_old_input'=>['avatar_id'=>36]])
            ->get('/alunos/novo')->assertOk()->getContent();
        $this->assertSame(37,substr_count($html,'type="radio"'));
        $this->assertStringContainsString('value="36" aria-label="Avatar 36" checked',$html);
        $this->assertStringContainsString('Usar iniciais do nome',$html);
        $this->assertStringNotContainsString('type="file"',$html);
        $this->assertStringContainsString('avatar-aluno--imagem',Blade::render('<x-avatar :avatar-id="36" />'));
        $this->assertStringNotContainsString('avatar-aluno--imagem',Blade::render('<x-avatar :avatar-id="37" />'));
    }

    public function test_web_encaminha_selecao_e_retorno_as_iniciais(): void
    {
        config(['services.api.base_url'=>'http://api.test/api']);
        Http::preventStrayRequests();
        Http::fake(['*'=>Http::response(['data'=>['id'=>7]],200)]);
        $this->withSession(['auth_token'=>'ficticio']);
        $dados=['nome'=>'Aluno fictício','matricula'=>'FICTICIO-7','turma_id'=>1,'data_nascimento'=>'2010-01-01'];
        $this->post('/alunos',$dados+['avatar_id'=>'36'])->assertRedirect();
        Http::assertSent(fn($r)=>$r->method()==='POST' && $r['avatar_id']==='36');
        $this->put('/alunos/7',$dados+['avatar_id'=>''])->assertRedirect();
        Http::assertSent(fn($r)=>$r->method()==='PUT' && array_key_exists('avatar_id',$r->data()) && $r['avatar_id']===null);
    }

    public function test_web_rejeita_avatar_fora_do_catalogo(): void
    {
        Http::preventStrayRequests();
        $this->withSession(['auth_token'=>'ficticio'])->put('/alunos/7',[
            'nome'=>'Aluno fictício','matricula'=>'FICTICIO-7','avatar_id'=>37,
        ])->assertSessionHasErrors('avatar_id');
        Http::assertNothingSent();
    }
}
