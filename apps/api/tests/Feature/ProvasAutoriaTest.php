<?php
namespace Tests\Feature;

use App\Models\Usuario;
use App\Support\Permissao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProvasAutoriaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['espacos.ativo'=>true, 'vinculos.obrigatorio'=>true]);
        Permissao::limparCache();
        foreach ([1=>'professor', 2=>'professor', 3=>'coordenador'] as $id=>$perfil) {
            Usuario::forceCreate(['id'=>$id, 'nome'=>'Autor fictício '.$id, 'perfil'=>$perfil,
                'email'=>"autoria$id@example.test", 'cpf'=>str_pad((string)$id,11,'0',STR_PAD_LEFT),
                'password'=>'ficticio', 'ativo'=>true]);
        }
        DB::table('clientes')->insert(['id'=>1,'tipo'=>'rede','nome'=>'Cliente fictício','modalidade'=>'interno','situacao'=>'ativo']);
        foreach ([1,2] as $id) {
            DB::table('redes')->insert(['id'=>$id,'nome'=>'Rede','tipo'=>'estadual','uf'=>'BA']);
            DB::table('nucleos')->insert(['id'=>$id,'rede_id'=>$id,'nome'=>'Núcleo']);
            DB::table('escolas')->insert(['id'=>$id,'nucleo_id'=>$id,'nome'=>'Escola','inep'=>'0000000'.$id,'tipo_rede'=>'estadual']);
            foreach (['rede'=>0,'nucleo'=>2,'escola'=>4] as $tipo=>$offset) {
                DB::table('espacos')->insert(['id'=>$id+$offset,'cliente_id'=>1,'tipo'=>$tipo,'nome'=>'Espaço','situacao'=>'ativo',$tipo.'_id'=>$id]);
            }
            DB::table('turmas')->insert(['id'=>$id,'escola_id'=>$id,'espaco_id'=>$id+4,'nome'=>'Turma','serie'=>'1','turno'=>'tarde','ano_letivo'=>2026]);
        }
        foreach ([1=>'professor',2=>'professor',3=>'coordenador'] as $id=>$perfil) {
            DB::table('usuario_vinculos')->insert(['id'=>$id,'usuario_id'=>$id,'espaco_id'=>5,'perfil'=>$perfil,'situacao'=>'ativo','inicio_em'=>now()->subDay()]);
            if ($perfil==='professor') DB::table('vinculo_turmas')->insert(['vinculo_id'=>$id,'espaco_id'=>5,'turma_id'=>1]);
        }
        foreach ([1=>1,2=>2,3=>3,4=>1] as $id=>$autor) {
            $fora=$id===4;
            DB::table('provas')->insert(['id'=>$id,'escola_id'=>$fora?2:1,'espaco_id'=>$fora?6:5,'criado_por'=>$autor,
                'titulo'=>'Prova fictícia '.$id,'disciplina'=>'Matemática','serie'=>'1','num_questoes'=>1,'num_alternativas'=>4,'status'=>'rascunho']);
            DB::table('prova_turmas')->insert(['prova_id'=>$id,'turma_id'=>$fora?2:1,'espaco_id'=>$fora?6:5]);
            DB::table('gabaritos')->insert(['id'=>$id,'prova_id'=>$id,'espaco_id'=>$fora?6:5]);
        }
    }

    private function chamar(string $token,string $metodo,string $url,array $dados=[])
    {
        app('auth')->forgetGuards();
        return $this->json($metodo,'/api/v1/'.$url,$dados,['Authorization'=>'Bearer '.$token]);
    }

    private function token(int $id): string
    {
        $t=Usuario::findOrFail($id)->createToken('identidade')->plainTextToken;
        return $this->chamar($t,'POST','auth/vinculos/selecionar',['vinculo_id'=>$id])->assertCreated()->json('data.token');
    }

    public function test_turma_compartilhada_nao_concede_acesso_a_prova_de_colega_ou_autoria_nao_conciliada(): void
    {
        $t=$this->token(1);
        $this->chamar($t,'GET','provas')->assertOk()->assertJsonCount(1,'data.provas')
            ->assertJsonPath('data.provas.0.id',1)->assertJsonPath('data.provas.0.criado_por_nome','Autor fictício 1')
            ->assertJsonPath('data.provas.0.pode_copiar',true)->assertJsonPath('data.kpis.total',1)
            ->assertJsonMissingPath('data.provas.0.autor')->assertJsonMissingPath('data.provas.0.email');
        $this->chamar($t,'GET','provas/1')->assertOk()->assertJsonPath('data.criado_por_nome','Autor fictício 1');
        foreach ([2,3,4] as $id) {
            foreach (["provas/$id","provas/$id/gabarito","relatorios/prova/$id","provas/$id/cartoes",
                "alunos/1/resultados/$id","alunos/1/resultados/$id/pdf"] as $url) {
                $this->chamar($t,'GET',$url)->assertNotFound();
            }
            $this->chamar($t,'PUT',"provas/$id",['titulo'=>'Não alterar'])->assertNotFound();
            $this->chamar($t,'POST',"provas/$id/copiar",['titulo'=>'Não copiar','chave'=>(string)\Illuminate\Support\Str::uuid()])->assertNotFound();
            $this->chamar($t,'DELETE',"provas/$id")->assertNotFound();
        }
        $this->chamar($t,'GET','turmas/1')->assertOk()->assertJsonCount(1,'data.provas')->assertJsonPath('data.provas.0.id',1);
        $this->assertDatabaseCount('provas',4);
        $this->assertDatabaseHas('provas',['id'=>2,'titulo'=>'Prova fictícia 2','criado_por'=>2]);
        $outro=$this->token(2);
        $this->chamar($outro,'GET','provas')->assertOk()->assertJsonCount(1,'data.provas')->assertJsonPath('data.provas.0.id',2);
        $this->chamar($outro,'GET','provas/1')->assertNotFound();
    }

    public function test_coordenador_mantem_visao_institucional_e_nome_do_criador_sem_expor_conta(): void
    {
        $t=$this->token(3);
        $this->chamar($t,'GET','provas')->assertOk()->assertJsonCount(3,'data.provas')->assertJsonPath('data.kpis.total',3);
        $this->chamar($t,'GET','provas/2')->assertOk()->assertJsonPath('data.criado_por_nome','Autor fictício 2')
            ->assertJsonMissingPath('data.autor')->assertJsonMissingPath('data.email');
        $this->chamar($t,'GET','provas/3')->assertOk()->assertJsonPath('data.criado_por_nome','Autor fictício 3');
        $this->chamar($t,'GET','provas/4')->assertNotFound();
    }

    public function test_diretor_ve_todas_as_provas_e_turmas_da_escola_sem_acesso_a_outra_escola(): void
    {
        DB::table('usuario_vinculos')->where('id',3)->update(['perfil'=>'dir_escolar']);
        $t=$this->token(3);
        $this->chamar($t,'GET','provas')->assertOk()->assertJsonCount(3,'data.provas');
        $this->chamar($t,'GET','provas/2')->assertOk()->assertJsonPath('data.criado_por_nome','Autor fictício 2');
        $this->chamar($t,'GET','turmas')->assertOk()->assertJsonCount(1,'data.turmas');
        $this->chamar($t,'GET','provas/4')->assertNotFound();
        $this->chamar($t,'GET','turmas/2')->assertNotFound();
    }

    public function test_institucional_depende_da_turma_e_perfil_registrado_sem_permitir_copia(): void
    {
        DB::table('provas')->where('id',3)->update(['perfil_criacao'=>'coordenador']);
        $t=$this->token(1);
        $this->chamar($t,'GET','provas')->assertOk()->assertJsonCount(2,'data.provas');
        $this->chamar($t,'GET','provas/3')->assertOk()->assertJsonPath('data.pode_copiar',false);
        $this->chamar($t,'GET','provas/3')->assertOk()->assertJsonPath('data.pode_editar',false);
        $this->chamar($t,'PUT','provas/3',['titulo'=>'Não editar institucional'])->assertNotFound();
        $this->chamar($t,'POST','provas/3/publicar')->assertNotFound();
        $this->chamar($t,'POST','provas/3/gabarito',['questoes'=>[]])->assertNotFound();
        $this->chamar($t,'POST','provas/3/gabarito/publicar')->assertNotFound();
        $this->chamar($t,'POST','provas/3/copiar',['titulo'=>'Não copiar','chave'=>(string)\Illuminate\Support\Str::uuid()])->assertNotFound();
        // Mudar a função atual do autor não muda a função registrada na criação.
        DB::table('usuarios')->where('id',3)->update(['perfil'=>'professor']);
        $this->chamar($t,'GET','provas/3')->assertOk();
        DB::table('vinculo_turmas')->where('vinculo_id',1)->delete();
        $this->chamar($t,'GET','provas/3')->assertNotFound();
        $this->chamar($t,'GET','provas/1')->assertOk();
        DB::table('provas')->where('id',3)->update(['perfil_criacao'=>'dir_escolar']);
        DB::table('vinculo_turmas')->insert(['vinculo_id'=>1,'espaco_id'=>5,'turma_id'=>1]);
        $this->chamar($t,'GET','provas/3')->assertOk()->assertJsonPath('data.pode_copiar',false);
        $novo=$this->chamar($t,'POST','provas',['escola_id'=>1,'titulo'=>'Nova própria','disciplina'=>'Teste','serie'=>'1','num_questoes'=>1,'num_alternativas'=>4,'perfil_criacao'=>'coordenador'])->assertCreated()->json('data.id');
        $this->assertDatabaseHas('provas',['id'=>$novo,'perfil_criacao'=>'professor','criado_por'=>1]);
        $this->chamar($t,'PUT',"provas/$novo",['titulo'=>'Editada','perfil_criacao'=>'dir_escolar'])->assertOk();
        $this->assertDatabaseHas('provas',['id'=>$novo,'perfil_criacao'=>'professor']);
        // Ser a mesma pessoa não autoriza copiar uma prova criada sob função de gestão.
        DB::table('provas')->where('id',1)->update(['perfil_criacao'=>'coordenador']);
        $this->chamar($t,'GET','provas/1')->assertOk()->assertJsonPath('data.pode_copiar',false)->assertJsonPath('data.pode_editar',false);
        DB::table('vinculo_turmas')->where('vinculo_id',1)->delete();
        $this->chamar($t,'GET','provas/1')->assertNotFound();
        $this->chamar($t,'GET',"provas/$novo")->assertOk();
        DB::table('vinculo_turmas')->insert(['vinculo_id'=>1,'espaco_id'=>5,'turma_id'=>1]);
        $coord=$this->token(3);
        $institucional=$this->chamar($coord,'POST','provas',['escola_id'=>1,'titulo'=>'Nova institucional','disciplina'=>'Teste','serie'=>'1','num_questoes'=>1,'num_alternativas'=>4,'turma_ids'=>[1]])->assertCreated()->json('data.id');
        $this->assertDatabaseHas('provas',['id'=>$institucional,'perfil_criacao'=>'coordenador']);
        $this->chamar($t,'GET',"provas/$institucional")->assertOk()->assertJsonPath('data.pode_copiar',false);
    }

    public function test_mesma_pessoa_diretor_e_professor_na_mesma_escola_nao_soma_funcoes(): void
    {
        DB::table('usuario_vinculos')->insert(['id'=>4,'usuario_id'=>1,'espaco_id'=>5,'perfil'=>'dir_escolar','situacao'=>'ativo','inicio_em'=>now()->subDay()]);
        DB::table('provas')->where('id',3)->update(['criado_por'=>1,'perfil_criacao'=>'dir_escolar']);
        $identidade=Usuario::findOrFail(1)->createToken('identidade')->plainTextToken;
        $diretor=$this->chamar($identidade,'POST','auth/vinculos/selecionar',['vinculo_id'=>4])->assertCreated()->json('data.token');
        $professor=$this->chamar($identidade,'POST','auth/vinculos/selecionar',['vinculo_id'=>1])->assertCreated()->json('data.token');
        $this->chamar($diretor,'GET','provas')->assertOk()->assertJsonCount(3,'data.provas');
        $this->chamar($professor,'GET','provas')->assertOk()->assertJsonCount(2,'data.provas');
        $this->chamar($professor,'GET','provas/2')->assertNotFound();
        $this->chamar($professor,'GET','provas/3')->assertOk()->assertJsonPath('data.pode_copiar',false)->assertJsonPath('data.pode_editar',false);
        DB::table('vinculo_turmas')->where('vinculo_id',1)->delete();
        $this->chamar($professor,'GET','provas/3')->assertNotFound();
        $this->chamar($diretor,'GET','provas/3')->assertOk();
        $this->chamar($diretor,'GET','provas')->assertOk()->assertJsonCount(3,'data.provas');
        $this->chamar($professor,'GET','provas/1')->assertOk();
        $this->chamar($diretor,'GET','provas/4')->assertNotFound();
        $this->assertDatabaseHas('usuarios',['id'=>1,'perfil'=>'professor']);
    }

    public function test_criacao_real_com_professor_e_coordenador_guarda_contextos_distintos(): void
    {
        DB::table('usuario_vinculos')->insert(['id'=>4,'usuario_id'=>1,'espaco_id'=>5,'perfil'=>'coordenador','situacao'=>'ativo','inicio_em'=>now()->subDay()]);
        $professor=$this->token(1);
        $identidade=Usuario::findOrFail(1)->createToken('identidade')->plainTextToken;
        $coordenador=$this->chamar($identidade,'POST','auth/vinculos/selecionar',['vinculo_id'=>4])->assertCreated()->json('data.token');
        $dados=['escola_id'=>1,'titulo'=>'Mesmo autor em funções diferentes','disciplina'=>'Matemática','serie'=>'1','num_questoes'=>1,'num_alternativas'=>4,'turma_ids'=>[1]];
        $particular=$this->chamar($professor,'POST','provas',$dados+['perfil_criacao'=>'coordenador','vinculo_criacao_id'=>4,'autoria_origem'=>'forjada'])->assertCreated()->json('data.id');
        $institucional=$this->chamar($coordenador,'POST','provas',$dados+['perfil_criacao'=>'professor','vinculo_criacao_id'=>1])->assertCreated()->json('data.id');
        $this->assertDatabaseHas('provas',['id'=>$particular,'criado_por'=>1,'perfil_criacao'=>'professor','vinculo_criacao_id'=>1,'autoria_origem'=>'registrada']);
        $this->assertDatabaseHas('provas',['id'=>$institucional,'criado_por'=>1,'perfil_criacao'=>'coordenador','vinculo_criacao_id'=>4,'autoria_origem'=>'registrada']);
        $this->chamar($professor,'GET',"provas/$particular")->assertOk()->assertJsonPath('data.pode_editar',true);
        $this->chamar($professor,'GET',"provas/$institucional")->assertOk()->assertJsonPath('data.pode_editar',false)->assertJsonPath('data.pode_copiar',false);
        $this->chamar($professor,'PUT',"provas/$particular",['titulo'=>'Título revisto','perfil_criacao'=>'coordenador','vinculo_criacao_id'=>4,'autoria_origem'=>null])->assertOk();
        $this->assertDatabaseHas('provas',['id'=>$particular,'perfil_criacao'=>'professor','vinculo_criacao_id'=>1,'autoria_origem'=>'registrada']);
        // Trocar a função da conta e do vínculo não transforma a origem das provas anteriores.
        DB::table('usuarios')->where('id',1)->update(['perfil'=>'coordenador']);
        DB::table('usuario_vinculos')->where('id',1)->update(['perfil'=>'dir_escolar','versao_autorizacao'=>2]);
        $colega=$this->token(2);
        $this->chamar($colega,'GET',"provas/$particular")->assertNotFound();
        $this->chamar($colega,'GET',"provas/$institucional")->assertOk()->assertJsonPath('data.perfil_criacao','coordenador');
        $this->assertDatabaseHas('provas',['id'=>$particular,'perfil_criacao'=>'professor','vinculo_criacao_id'=>1]);
    }

    public function test_banco_recusa_reescrever_autoria_capturada_sem_bloquear_edicao_de_titulo(): void
    {
        $professor=$this->token(1);
        $id=$this->chamar($professor,'POST','provas',['escola_id'=>1,'titulo'=>'Origem preservada','disciplina'=>'Matemática','serie'=>'1','num_questoes'=>1,'num_alternativas'=>4])->assertCreated()->json('data.id');
        DB::table('provas')->where('id',$id)->update(['titulo'=>'Revisão permitida']);
        $this->assertDatabaseHas('provas',['id'=>$id,'titulo'=>'Revisão permitida']);
        $this->expectException(\Illuminate\Database\QueryException::class);
        DB::table('provas')->where('id',$id)->update(['perfil_criacao'=>'coordenador','autoria_origem'=>null]);
    }
}
