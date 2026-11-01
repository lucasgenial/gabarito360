<?php
namespace Tests\Feature;

use App\Models\Usuario;
use App\Support\{ContextoInterno, Permissao, TokenInterno, TokenVinculo};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class SelecaoInternaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['acessos_internos.ativo'=>true, 'espacos.ativo'=>true, 'espacos.pessoal'=>true, 'vinculos.obrigatorio'=>true]);
        Permissao::limparCache();
        foreach ([1,2] as $id) Usuario::forceCreate(['id'=>$id, 'perfil'=>'admin_rede', 'nome'=>'Operador fictício', 'email'=>"a1b-$id@example.test", 'cpf'=>str_pad((string)$id, 11, '0', STR_PAD_LEFT), 'password'=>'ficticio', 'ativo'=>true]);
        foreach (ContextoInterno::PERFIS as $i=>$perfil) DB::table('usuario_acessos_internos')->insert(['id'=>$i+1,'usuario_id'=>1,'perfil'=>$perfil,'situacao'=>'ativo','inicio_em'=>now()->subDay(),'versao_autorizacao'=>1]);
        DB::table('usuario_acessos_internos')->insert(['id'=>4,'usuario_id'=>2,'perfil'=>'financeiro','situacao'=>'ativo','inicio_em'=>now()->subDay(),'versao_autorizacao'=>1]);
        DB::table('clientes')->insert(['id'=>1,'tipo'=>'professor','nome'=>'Fictício','modalidade'=>'interno','situacao'=>'ativo']);
        DB::table('espacos')->insert(['id'=>1,'cliente_id'=>1,'tipo'=>'pessoal','nome'=>'Pessoal','situacao'=>'ativo','usuario_proprietario_id'=>1]);
        DB::table('usuario_vinculos')->insert(['id'=>1,'usuario_id'=>1,'espaco_id'=>1,'perfil'=>'professor_autonomo','situacao'=>'ativo','inicio_em'=>now()->subDay()]);
    }

    private function identidade(): string { return Usuario::find(1)->createToken('api-token')->plainTextToken; }
    private function chamar(string $token, string $metodo, string $url, array $dados = [])
    {
        app('auth')->forgetGuards();
        return $this->json($metodo, '/api/v1/'.$url, $dados, ['Authorization'=>'Bearer '.$token, 'X-Acesso-Interno-Id'=>'4']);
    }
    private function selecionar(string $token, int $id = 3): string
    {
        return $this->chamar($token, 'POST', 'auth/acessos-internos/selecionar', ['acesso_interno_id'=>$id])->assertCreated()->json('data.token');
    }

    public function test_lista_apenas_atribuicoes_proprias_validas_e_recusa_alheias(): void
    {
        $t = $this->identidade();
        $this->chamar($t,'GET','auth/acessos-internos')->assertOk()->assertJsonCount(3,'data');
        foreach ([4,999] as $id) $this->chamar($t,'POST','auth/acessos-internos/selecionar',['acesso_interno_id'=>$id])->assertForbidden();
        $this->assertDatabaseCount('personal_access_tokens',1);
        DB::table('usuario_acessos_internos')->where('id',1)->update(['situacao'=>'suspenso']);
        DB::table('usuario_acessos_internos')->where('id',2)->update(['fim_em'=>now()]);
        $this->chamar($t,'GET','auth/acessos-internos')->assertOk()->assertJsonCount(1,'data')->assertJsonPath('data.0.id',3);
        config(['acessos_internos.ativo'=>false]);
        $this->chamar($t,'GET','auth/acessos-internos')->assertNotFound();
    }

    public function test_contextos_independentes_nao_somam_poderes_nem_geram_filhos(): void
    {
        $t = $this->identidade(); $a = $this->selecionar($t); $b = $this->selecionar($t,1);
        $escolar = $this->chamar($t,'POST','auth/vinculos/selecionar',['vinculo_id'=>1])->assertCreated()->json('data.token');
        foreach ([[$a,'financeiro'],[$escolar,'professor_autonomo'],[$b,'desenvolvedor'],[$a,'financeiro']] as [$token,$perfil]) {
            $this->chamar($token,'GET','auth/me?acesso_interno_id=4')->assertOk()->assertJsonPath('data.perfil',$perfil);
        }
        $this->chamar($a,'GET','auth/me')->assertJsonPath('data.tipo_contexto','interno')->assertJsonPath('data.escola_nome',null);
        $this->chamar($t,'GET','auth/me')->assertJsonPath('data.perfil',null);
        foreach ([$a,$escolar] as $token) {
            $this->chamar($token,'POST','auth/acessos-internos/selecionar',['acesso_interno_id'=>1])->assertForbidden();
            $this->chamar($token,'GET','auth/acessos-internos')->assertForbidden();
        }
        $this->chamar($a,'POST','auth/vinculos/selecionar',['vinculo_id'=>1])->assertForbidden();
        foreach (['turmas','alunos','provas','cartoes/1','dashboard/admin','relatorios/prova/1'] as $url) $this->chamar($a,'GET',$url)->assertForbidden();
        $this->chamar($escolar,'GET','turmas')->assertOk();
        $this->assertSame('admin_rede',Usuario::find(1)->perfil);
    }

    public function test_versao_revogacao_conta_e_habilitacao_invalidam_token_sem_fallback(): void
    {
        $t = $this->identidade(); $a = $this->selecionar($t);
        foreach ([['versao_autorizacao'=>2],['situacao'=>'suspenso'],['fim_em'=>now()],['inicio_em'=>now()->addDay()]] as $alteracao) {
            DB::table('usuario_acessos_internos')->where('id',3)->update($alteracao);
            $this->chamar($a,'GET','auth/me')->assertForbidden();
            DB::table('usuario_acessos_internos')->where('id',3)->update(['versao_autorizacao'=>1,'situacao'=>'ativo','inicio_em'=>now()->subDay(),'fim_em'=>null]);
        }
        DB::table('usuarios')->where('id',1)->update(['ativo'=>false]);
        $this->chamar($a,'GET','auth/me')->assertForbidden();
        DB::table('usuarios')->where('id',1)->update(['ativo'=>true]);
        foreach (['acessos_internos.ativo','vinculos.obrigatorio'] as $chave) {
            config([$chave=>false]);
            $this->chamar($a,'GET','auth/me')->assertForbidden();
            config([$chave=>true]);
        }
    }

    public function test_logout_contextual_preserva_irmao_e_logout_pai_encerra_todos(): void
    {
        $t = $this->identidade(); $a = $this->selecionar($t); $b = $this->selecionar($t,1);
        $this->chamar($a,'POST','auth/logout')->assertOk();
        $this->chamar($a,'GET','auth/me')->assertUnauthorized();
        $this->chamar($b,'GET','auth/me')->assertOk();
        $this->chamar($t,'POST','auth/logout')->assertOk();
        $this->chamar($b,'GET','auth/me')->assertForbidden();
        $this->chamar($b,'POST','auth/logout')->assertOk();
    }

    public function test_rejeita_metadados_pais_contextuais_alheios_e_expirados(): void
    {
        $t = $this->identidade(); $a = $this->selecionar($t);
        $modelo = PersonalAccessToken::findToken($a); $original = $modelo->abilities;
        foreach ([['*'],['interno:3','versao:0','identidade:1'],['interno:3','versao:1','identidade:1','*']] as $dados) {
            $modelo->forceFill(['abilities'=>$dados])->save();
            $this->chamar($a,'GET','auth/me')->assertForbidden();
        }
        $alheio = Usuario::find(2)->createToken('api-token')->accessToken;
        $interno = PersonalAccessToken::findToken($this->selecionar($t,1));
        $vinculo = Usuario::find(1)->createToken(TokenVinculo::NOME,['vinculo:1','identidade:'.PersonalAccessToken::findToken($t)->id])->accessToken;
        foreach ([$alheio,$interno,$vinculo] as $pai) {
            $modelo->forceFill(['abilities'=>['interno:3','versao:1','identidade:'.$pai->id]])->save();
            $this->chamar($a,'GET','auth/me')->assertForbidden();
        }
        $modelo->forceFill(['abilities'=>$original])->save();
        $pai = PersonalAccessToken::findToken($t);
        $pai->forceFill(['expires_at'=>now()->subMinute()])->save();
        $this->chamar($a,'GET','auth/me')->assertForbidden();
        $pai->forceFill(['expires_at'=>null,'created_at'=>now()->subHours(2)])->save();
        config(['sanctum.expiration'=>60]);
        $this->chamar($a,'GET','auth/me')->assertForbidden();
    }

    public function test_token_interno_nao_pode_ser_pai_escolar_e_troca_obrigatoria_bloqueia_emissao(): void
    {
        $t = $this->identidade(); $a = $this->selecionar($t);
        $falso = Usuario::find(1)->createToken(TokenVinculo::NOME,['vinculo:1','identidade:'.PersonalAccessToken::findToken($a)->id])->plainTextToken;
        $this->chamar($falso,'GET','auth/me')->assertForbidden();
        Usuario::find(1)->update(['forcar_troca_senha'=>true]);
        $restrito = Usuario::find(1)->createToken('troca-senha')->plainTextToken;
        $this->chamar($restrito,'GET','auth/acessos-internos')->assertForbidden();
        $this->chamar($restrito,'POST','auth/acessos-internos/selecionar',['acesso_interno_id'=>3])->assertForbidden();
    }
}
