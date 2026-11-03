<?php
namespace Tests\Feature;

use App\Models\Usuario;
use App\Support\Permissao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class DuplicarProvaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['espacos.ativo'=>true,'espacos.pessoal'=>true,'vinculos.obrigatorio'=>true]);
        Permissao::limparCache();
        foreach ([1,2] as $id) {
            Usuario::forceCreate(['id'=>$id,'nome'=>'Professor fictício','perfil'=>'professor','email'=>"cp$id@example.test",'cpf'=>str_pad((string)$id,11,'0',STR_PAD_LEFT),'password'=>'ficticio','ativo'=>true]);
            DB::table('clientes')->insert(['id'=>$id,'tipo'=>'professor','nome'=>'Cliente','modalidade'=>'interno','situacao'=>'ativo']);
            DB::table('espacos')->insert(['id'=>$id,'cliente_id'=>$id,'tipo'=>'pessoal','nome'=>"Pessoal $id",'situacao'=>'ativo','usuario_proprietario_id'=>$id]);
            DB::table('usuario_vinculos')->insert(['id'=>$id,'usuario_id'=>$id,'espaco_id'=>$id,'perfil'=>'professor_autonomo','situacao'=>'ativo','inicio_em'=>now()->subDay()]);
            DB::table('turmas')->insert(['id'=>$id,'escola_id'=>null,'espaco_id'=>$id,'espaco_tipo'=>'pessoal','nome'=>"Turma $id",'serie'=>'1','turno'=>'tarde','ano_letivo'=>2026]);
            DB::table('provas')->insert(['id'=>$id,'escola_id'=>null,'espaco_id'=>$id,'espaco_tipo'=>'pessoal','criado_por'=>$id,'titulo'=>"Prova $id",'disciplina'=>'Matemática','serie'=>'1','num_questoes'=>1,'num_alternativas'=>5,'status'=>'corrigida','data_aplicacao'=>'2026-10-01','correcao_fechada_em'=>now(),'correcao_fechada_por'=>$id]);
            DB::table('prova_turmas')->insert(['prova_id'=>$id,'turma_id'=>$id,'espaco_id'=>$id]);
            DB::table('gabaritos')->insert(['id'=>$id,'prova_id'=>$id,'espaco_id'=>$id,'publicado_em'=>now(),'publicado_por'=>$id]);
            DB::table('gabarito_questoes')->insert(['gabarito_id'=>$id,'espaco_id'=>$id,'numero_questao'=>1,'alternativa'=>'B','alternativa_espanhol'=>'C','peso'=>2.5,'anulada'=>true]);
        }
    }

    private function chamar(string $token,string $metodo,string $url,array $dados=[])
    {
        app('auth')->forgetGuards();
        return $this->json($metodo,'/api/v1/'.$url,$dados,['Authorization'=>'Bearer '.$token]);
    }

    private function token(int $id=1): string
    {
        $t=Usuario::findOrFail($id)->createToken('identidade')->plainTextToken;
        return $this->chamar($t,'POST','auth/vinculos/selecionar',['vinculo_id'=>$id])->assertCreated()->json('data.token');
    }

    public function test_copia_limpa_e_independente_com_procedencia_e_reenvio(): void
    {
        $t=$this->token(); $original=(array)DB::table('provas')->find(1);
        $this->chamar($t,'GET','provas')->assertOk()->assertJsonPath('data.provas.0.pode_copiar',true);
        $d=['titulo'=>'Versão 1B','chave'=>(string)Str::uuid()];
        $id=$this->chamar($t,'POST','provas/1/copiar',$d)->assertCreated()->json('data.id');
        $this->chamar($t,'POST','provas/1/copiar',$d)->assertCreated()->assertJsonPath('data.id',$id);
        $this->assertSame($original,(array)DB::table('provas')->find(1));
        $p=DB::table('provas')->find($id);
        $this->assertSame('rascunho',$p->status); $this->assertSame('gabarito',$p->formato_avaliacao);
        $this->assertNull($p->data_aplicacao); $this->assertNull($p->correcao_fechada_em); $this->assertNull($p->correcao_fechada_por);
        foreach (['prova_turmas','cartoes','notas'] as $tabela) $this->assertDatabaseMissing($tabela,['prova_id'=>$id]);
        $g=DB::table('gabaritos')->where('prova_id',$id)->first();
        $this->assertNull($g->publicado_em); $this->assertNull($g->publicado_por);
        $this->assertDatabaseHas('gabarito_questoes',['gabarito_id'=>$g->id,'alternativa'=>'B','alternativa_espanhol'=>'C','peso'=>2.5,'anulada'=>true]);
        $this->chamar($t,'PUT',"provas/$id",['titulo'=>'Alterada'])->assertOk();
        $this->chamar($t,'POST',"provas/$id/gabarito",['questoes'=>[['numero_questao'=>1,'alternativa'=>'D','peso'=>1]]])->assertOk();
        $this->assertDatabaseHas('gabarito_questoes',['gabarito_id'=>1,'alternativa'=>'B']);
        $this->chamar($t,'GET',"provas/$id")->assertOk()->assertJsonPath('data.procedencia.origem_id',1)->assertJsonPath('data.pode_copiar',true);
        $this->chamar($t,'POST','provas/1/copiar',$d)->assertCreated()->assertJsonPath('data.id',$id);
        $this->chamar($t,'POST','provas/1/copiar',['titulo'=>'Conflito','chave'=>$d['chave']])->assertStatus(409);
        $this->chamar($t,'DELETE',"provas/$id")->assertStatus(409);
        $outro=$this->chamar($t,'POST',"provas/$id/copiar",['titulo'=>'Versão C','chave'=>(string)Str::uuid()])->assertCreated()->json('data.id');
        $this->assertDatabaseHas('prova_duplicacoes',['prova_id'=>$outro,'origem_id'=>$id,'raiz_id'=>1,'autor_original_id'=>1]);
    }

    public function test_outro_espaco_revogacao_e_formato_desconhecido_nao_copiam(): void
    {
        $t=$this->token(); $d=['titulo'=>'Não criar','chave'=>(string)Str::uuid()];
        $this->chamar($t,'POST','provas/2/copiar',$d)->assertNotFound();
        $this->chamar($t,'POST','provas/1/copiar',['titulo'=>'','chave'=>'x'])->assertUnprocessable();
        DB::table('provas')->where('id',1)->update(['formato_avaliacao'=>'futuro']);
        $this->chamar($t,'POST','provas/1/copiar',$d)->assertNotFound();
        DB::table('usuario_vinculos')->where('id',1)->update(['situacao'=>'suspenso']);
        $this->chamar($t,'POST','provas/1/copiar',$d)->assertForbidden();
        $this->assertDatabaseCount('prova_duplicacoes',0); $this->assertDatabaseCount('provas',2);
    }

    public function test_rollback_quando_registro_da_procedencia_falha(): void
    {
        $t=$this->token();
        DB::listen(function ($evento) {
            if (str_starts_with(strtolower($evento->sql),'insert') && str_contains($evento->sql,'prova_duplicacoes')) throw new \RuntimeException('Falha ensaiada após gravar procedência.');
        });
        $this->chamar($t,'POST','provas/1/copiar',['titulo'=>'Falha','chave'=>(string)Str::uuid()])->assertStatus(500);
        $this->assertDatabaseCount('provas',2); $this->assertDatabaseCount('gabaritos',2); $this->assertDatabaseCount('gabarito_questoes',2);
    }

    public function test_sem_gabarito_cria_rascunho_sem_inventar_respostas(): void
    {
        $t=$this->token(); DB::table('gabaritos')->where('id',1)->delete();
        $id=$this->chamar($t,'POST','provas/1/copiar',['titulo'=>'Sem respostas','chave'=>(string)Str::uuid()])->assertCreated()->json('data.id');
        $this->assertDatabaseMissing('gabaritos',['prova_id'=>$id]);
    }

    public function test_professor_escolar_copia_apenas_autoria_propria_e_permissao_vigente(): void
    {
        DB::table('redes')->insert(['id'=>1,'nome'=>'Rede','tipo'=>'estadual','uf'=>'BA']);
        DB::table('nucleos')->insert(['id'=>1,'rede_id'=>1,'nome'=>'Núcleo']);
        DB::table('escolas')->insert(['id'=>1,'nucleo_id'=>1,'nome'=>'Escola','inep'=>'00000001','tipo_rede'=>'estadual']);
        foreach (['rede'=>3,'nucleo'=>4,'escola'=>5] as $tipo=>$id) DB::table('espacos')->insert(['id'=>$id,'cliente_id'=>1,'tipo'=>$tipo,'nome'=>$tipo,'situacao'=>'ativo',$tipo.'_id'=>1]);
        DB::table('usuario_vinculos')->where('id',1)->update(['espaco_id'=>5,'perfil'=>'professor']);
        // Prova própria em contexto escolar, sem transportar os filhos pessoais.
        $p=DB::table('provas')->insertGetId(['escola_id'=>1,'espaco_id'=>5,'criado_por'=>1,'titulo'=>'Escolar','disciplina'=>'Matemática','serie'=>'1','num_questoes'=>1,'num_alternativas'=>4]);
        $t=$this->token(); $d=['titulo'=>'Cópia escolar','chave'=>(string)Str::uuid()];
        $id=$this->chamar($t,'POST',"provas/$p/copiar",$d)->assertCreated()->json('data.id');
        $this->assertDatabaseHas('provas',['id'=>$id,'escola_id'=>1,'espaco_id'=>5,'criado_por'=>1]);
        DB::table('provas')->where('id',$p)->update(['criado_por'=>2]);
        $this->chamar($t,'POST',"provas/$p/copiar",['titulo'=>'Colega','chave'=>(string)Str::uuid()])->assertNotFound();
        DB::table('usuario_vinculos')->where('id',1)->update(['perfil'=>'coordenador']);
        $this->chamar($t,'POST',"provas/$id/copiar",['titulo'=>'Não','chave'=>(string)Str::uuid()])->assertForbidden();
    }

    public function test_gabarito_com_filho_de_outro_espaco_bloqueia_copia(): void
    {
        DB::table('gabarito_questoes')->where('gabarito_id',1)->update(['espaco_id'=>2]);
        $this->chamar($this->token(),'POST','provas/1/copiar',['titulo'=>'Não','chave'=>(string)Str::uuid()])->assertStatus(409);
        $this->assertDatabaseCount('provas',2);
    }

    public function test_cadastro_classifica_formato_e_recusa_editor_nao_implementado(): void
    {
        $t=$this->token();
        $d=['titulo'=>'Nova','disciplina'=>'Matemática','serie'=>'1','num_questoes'=>1,'num_alternativas'=>4];
        $this->chamar($t,'POST','provas',$d+['formato_avaliacao'=>'editor'])->assertUnprocessable();
        $this->chamar($t,'POST','provas',$d+['formato_avaliacao'=>'gabarito'])->assertCreated()->assertJsonPath('data.formato_avaliacao','gabarito');
        $this->chamar($t,'PUT','provas/1',['formato_avaliacao'=>'editor'])->assertUnprocessable();
    }

    public function test_copia_exige_novas_turmas_e_publicacao_do_gabarito_antes_dos_cartoes(): void
    {
        $t=$this->token();
        $id=$this->chamar($t,'POST','provas/1/copiar',['titulo'=>'Aplicação','chave'=>(string)Str::uuid()])->assertCreated()->json('data.id');
        $this->chamar($t,'GET',"provas/$id/cartoes/pdf?brancos=5")->assertUnprocessable();
        $this->chamar($t,'POST',"provas/$id/publicar")->assertUnprocessable();
        $this->chamar($t,'PUT',"provas/$id",['status'=>'publicada'])->assertUnprocessable();
        $this->chamar($t,'POST',"provas/$id/gabarito/publicar")->assertUnprocessable();
        $this->chamar($t,'PUT',"provas/$id",['turma_ids'=>[1]])->assertOk();
        $this->chamar($t,'GET',"provas/$id/cartoes/pdf?brancos=5")->assertUnprocessable();
        $this->chamar($t,'POST',"provas/$id/gabarito/publicar")->assertOk();
        $this->assertDatabaseHas('provas',['id'=>$id,'status'=>'publicada']);
    }
}
