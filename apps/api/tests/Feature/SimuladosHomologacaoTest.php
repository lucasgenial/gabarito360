<?php

namespace Tests\Feature;

use App\Models\Usuario;
use App\Services\SimuladosHomologacao;
use App\Support\Escopo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SimuladosHomologacaoTest extends TestCase
{
    use RefreshDatabase, \Tests\Support\CenarioDisciplinas { setUp as protected prepararBase; }

    protected function setUp(): void
    {
        $this->prepararBase();
        // Fixture corresponde aos IDs explicitamente autorizados, sem usar dados de pessoas reais.
        DB::table('escolas')->insert(array_replace((array)DB::table('escolas')->find(1),['id'=>8,'inep'=>'29428254']));
        DB::table('espacos')->insert(array_replace((array)DB::table('espacos')->find(5),['id'=>13,'escola_id'=>8]));
        foreach ([14,15] as $id) DB::table('turmas')->insert(array_replace((array)DB::table('turmas')->find(1),['id'=>$id,'nome'=>'Turma '.$id,'escola_id'=>8,'espaco_id'=>13]));
        Usuario::forceCreate(['id'=>10,'nome'=>'Coordenação fictícia','email'=>'coordenador@example.test','perfil'=>'coordenador','cpf'=>'12312312312','password'=>'segredo-ficticio','ativo'=>true]);
        DB::table('usuario_vinculos')->insert(['id'=>7,'usuario_id'=>10,'espaco_id'=>13,'perfil'=>'coordenador','situacao'=>'ativo','inicio_em'=>now()->subDay()]);
        $base=Usuario::find(2); $base->email='professor@gabarito360.dev'; $base->cpf='45645645645'; $base->save();
        foreach ([3=>'Linguagens',4=>'Ciências Humanas',5=>'Ciências da Natureza',6=>'Matemática'] as $id=>$disciplina) {
            DB::table('provas')->insert(array_replace((array)DB::table('provas')->find(1),['id'=>$id,'escola_id'=>8,'espaco_id'=>13,'criado_por'=>10,'perfil_criacao'=>'coordenador',
                'titulo'=>'Simulado ENEM 2026','disciplina'=>$disciplina,'status'=>'publicada']));
            foreach ([14,15] as $turma) DB::table('prova_turmas')->insert(['prova_id'=>$id,'turma_id'=>$turma,'espaco_id'=>13]);
        }
    }

    public function test_simulacao_nao_cria_usuarios_catalogo_ou_contexto(): void
    {
        $antes=DB::table('usuarios')->get()->toJson();
        $r=app(SimuladosHomologacao::class)->executar();
        $this->assertTrue($r['simulacao']); $this->assertSame($antes,DB::table('usuarios')->get()->toJson());
        $this->assertDatabaseCount('disciplinas',0); $this->assertDatabaseCount('disciplina_eventos',0);
        $this->assertNull(DB::table('provas')->find(3)->autoria_origem);
    }

    public function test_adapta_sem_apagar_e_cada_professor_so_ve_sua_area(): void
    {
        $g=DB::table('gabaritos')->insertGetId(['prova_id'=>4,'espaco_id'=>13,'publicado_em'=>now()->subDay()]);
        DB::table('gabarito_questoes')->insert(['gabarito_id'=>$g,'espaco_id'=>13,'numero_questao'=>1,'alternativa'=>'C','peso'=>1,'anulada'=>false]);
        DB::table('alunos')->insert(['id'=>20,'espaco_id'=>13,'turma_id'=>14,'nome'=>'Aluno fictício','matricula'=>'FICTICIO-20','ativo'=>true]);
        DB::table('cartoes')->insert(['id'=>20,'espaco_id'=>13,'prova_id'=>4,'aluno_id'=>20,'turma_aplicacao_id'=>14,'imagem_url'=>'cartoes/ficticio-20.jpg','status'=>'ambiguo']);
        DB::table('cartao_respostas')->insert(['cartao_id'=>20,'espaco_id'=>13,'numero_questao'=>1,'alternativa'=>'C','ambigua'=>true]);
        DB::table('notas')->insert(['cartao_id'=>20,'prova_id'=>4,'aluno_id'=>20,'turma_id'=>14,'espaco_id'=>13,'acertos'=>1,'total_questoes'=>1,'nota_final'=>10,'status_aprovacao'=>'aprovado']);
        $academico=[]; foreach (['alunos','gabaritos','gabarito_questoes','cartoes','cartao_respostas','notas','prova_turmas'] as $t) $academico[$t]=DB::table($t)->get()->toJson();
        $antes=DB::table('provas')->orderBy('id')->get()->map(fn($p)=>(array)$p)->all();
        $hash=Usuario::find(2)->getRawOriginal('password');
        $r=app(SimuladosHomologacao::class)->executar(true); $this->assertTrue($r['academico_preservado']);
        $this->assertDatabaseCount('provas',6); $this->assertDatabaseCount('disciplinas',13); $this->assertDatabaseCount('docente_atribuicoes',26);
        foreach ($academico as $t=>$json) $this->assertSame($json,DB::table($t)->get()->toJson());
        foreach ($antes as $original) {
            $atual=(array)DB::table('provas')->find($original['id']);
            foreach ($original as $chave=>$valor) if (!in_array($chave,['autoria_origem','vinculo_criacao_id'])) $this->assertSame($valor,$atual[$chave]);
        }
        for ($i=1;$i<=13;$i++) {
            $u=Usuario::porEmail('professor'.$i.'@gabarito360.dev')->firstOrFail();
            $this->assertSame($hash,$u->getRawOriginal('password'));
            $v=DB::table('usuario_vinculos')->where('usuario_id',$u->id)->where('espaco_id',13)->first();
            $esperada=$i<=5?3:($i<=9?4:($i<=12?5:6));
            $this->assertSame([$esperada],Escopo::provas($u->noVinculo($v->id))->orderBy('provas.id')->pluck('provas.id')->map(fn($id)=>(int)$id)->all());
        }
        $this->assertSame([3,4,5,6],Escopo::provas(Usuario::find(10)->noVinculo(7))->orderBy('provas.id')->pluck('provas.id')->map(fn($id)=>(int)$id)->all());
    }

    public function test_reexecucao_nao_duplica_nem_troca_senha(): void
    {
        app(SimuladosHomologacao::class)->executar(true);
        $antes=[]; foreach (['usuarios','usuario_vinculos','disciplinas','docente_atribuicoes','disciplina_eventos','prova_curriculos'] as $t) $antes[$t]=DB::table($t)->get()->toJson();
        $this->assertTrue(app(SimuladosHomologacao::class)->executar(true)['ja_preparado']);
        foreach ($antes as $t=>$json) $this->assertSame($json,DB::table($t)->get()->toJson());
    }

    public function test_recusa_escola_ou_conta_preexistente_sem_sobrescrever(): void
    {
        Usuario::forceCreate(['nome'=>'Outra conta','email'=>'professor13@gabarito360.dev','cpf'=>'45612312312','password'=>'outra','perfil'=>'professor','ativo'=>true]);
        $antes=DB::table('usuarios')->get()->toJson();
        try { app(SimuladosHomologacao::class)->executar(true); $this->fail('Deveria recusar colisão.'); } catch (\LogicException $e) { $this->assertStringContainsString('Identificador',$e->getMessage()); }
        $this->assertSame($antes,DB::table('usuarios')->get()->toJson()); $this->assertDatabaseCount('disciplinas',0);
    }

    public function test_falha_no_meio_reverte_usuarios_vinculos_e_curriculo(): void
    {
        DB::unprepared("CREATE TRIGGER falha_teste_dc1 BEFORE INSERT ON disciplinas WHEN NEW.nome='Física' BEGIN SELECT RAISE(ABORT,'falha controlada'); END");
        $antes=DB::table('usuarios')->get()->toJson();
        try { app(SimuladosHomologacao::class)->executar(true); $this->fail('Deveria reverter.'); } catch (\Illuminate\Database\QueryException $e) { $this->assertStringContainsString('falha controlada',$e->getMessage()); }
        $this->assertSame($antes,DB::table('usuarios')->get()->toJson()); $this->assertDatabaseCount('disciplinas',0); $this->assertDatabaseCount('prova_curriculos',0); $this->assertDatabaseCount('disciplina_eventos',0);
        $this->assertNull(DB::table('provas')->find(3)->autoria_origem);
    }

    public function test_recusa_producao_mesmo_com_opcao_aplicar(): void
    {
        app()->detectEnvironment(fn()=> 'production');
        try { $this->artisan('g360:preparar-simulados-homologacao',['--aplicar'=>true])->assertFailed(); }
        finally { app()->detectEnvironment(fn()=> 'testing'); }
        $this->assertDatabaseCount('disciplinas',0); $this->assertNull(DB::table('provas')->find(3)->autoria_origem);
    }

    public function test_revogacao_bloqueia_url_direta_e_reexecucao_nao_restaura_acesso(): void
    {
        app(SimuladosHomologacao::class)->executar(true);
        $p=Usuario::porEmail('professor13@gabarito360.dev')->firstOrFail();
        $v=DB::table('usuario_vinculos')->where('usuario_id',$p->id)->first();
        app('auth')->forgetGuards(); \Laravel\Sanctum\Sanctum::actingAs($p->noVinculo($v->id));
        $this->getJson('/api/v1/provas/6')->assertOk();
        $this->getJson('/api/v1/provas/3')->assertNotFound();
        $this->postJson('/api/v1/provas/6/copiar',['titulo'=>'Cópia indevida','chave'=>(string)\Illuminate\Support\Str::uuid()])->assertNotFound();
        DB::table('docente_atribuicoes')->where('vinculo_id',$v->id)->update(['situacao'=>'encerrada']);
        $this->getJson('/api/v1/provas/6')->assertNotFound();
        try { app(SimuladosHomologacao::class)->executar(true); $this->fail('Não reativar atribuição revogada.'); }
        catch (\LogicException $e) { $this->assertStringContainsString('estado preparado mudou',$e->getMessage()); }
        $this->assertSame(2,DB::table('docente_atribuicoes')->where('vinculo_id',$v->id)->where('situacao','encerrada')->count());
    }

    public function test_outro_vinculo_da_coordenacao_nao_herda_acesso_docente(): void
    {
        app(SimuladosHomologacao::class)->executar(true);
        $v=DB::table('usuario_vinculos')->insertGetId(['usuario_id'=>10,'espaco_id'=>13,'perfil'=>'professor','situacao'=>'ativo','inicio_em'=>now()]);
        DB::table('vinculo_turmas')->insert(['vinculo_id'=>$v,'espaco_id'=>13,'turma_id'=>14]);
        $u=Usuario::find(10);
        $this->assertSame(4,Escopo::provas($u->noVinculo(7))->count());
        $this->assertSame(0,Escopo::provas($u->noVinculo($v))->count());
        $this->assertDatabaseHas('provas',['id'=>6,'vinculo_criacao_id'=>7,'perfil_criacao'=>'coordenador','autoria_origem'=>'adaptacao_desenvolvimento']);
    }

    public function test_filtro_oferece_escopo_completo_mesmo_apos_filtrar_e_nao_expoe_outras_provas(): void
    {
        app(SimuladosHomologacao::class)->executar(true);
        app('auth')->forgetGuards(); \Laravel\Sanctum\Sanctum::actingAs(Usuario::find(10)->noVinculo(7));
        $resp=$this->getJson('/api/v1/provas?disciplina=Matem%C3%A1tica&busca=ENEM')->assertOk()->assertJsonCount(1,'data.provas');
        $this->assertEqualsCanonicalizing(['Linguagens','Ciências Humanas','Ciências da Natureza','Matemática'],$resp->json('data.filtros.disciplinas'));
        $p=Usuario::porEmail('professor13@gabarito360.dev')->firstOrFail();$v=DB::table('usuario_vinculos')->where('usuario_id',$p->id)->first();
        app('auth')->forgetGuards(); \Laravel\Sanctum\Sanctum::actingAs($p->noVinculo($v->id));
        $this->getJson('/api/v1/provas?disciplina=Linguagens')->assertOk()->assertJsonCount(0,'data.provas')->assertJsonPath('data.filtros.disciplinas',['Matemática']);
    }
}
