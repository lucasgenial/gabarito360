<?php
namespace Tests\Feature;

use App\Models\Usuario;
use App\Support\Permissao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TurmaResumoDesempenhoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['espacos.ativo'=>true,'vinculos.obrigatorio'=>true]);
        Permissao::limparCache();
        foreach ([1,2] as $id) {
            DB::table('clientes')->insert(['id'=>$id,'tipo'=>'rede','nome'=>'Cliente fictício','modalidade'=>'interno','situacao'=>'ativo']);
            DB::table('redes')->insert(['id'=>$id,'nome'=>'Rede fictícia','tipo'=>'estadual','uf'=>'BA']);
            DB::table('nucleos')->insert(['id'=>$id,'rede_id'=>$id,'nome'=>'Núcleo fictício']);
            DB::table('escolas')->insert(['id'=>$id,'nucleo_id'=>$id,'nome'=>'Escola fictícia','inep'=>'0000000'.$id,'tipo_rede'=>'estadual']);
            foreach (['rede'=>0,'nucleo'=>2,'escola'=>4] as $tipo=>$offset) DB::table('espacos')->insert([
                'id'=>$id+$offset,'cliente_id'=>$id,'tipo'=>$tipo,'nome'=>'Espaço fictício','situacao'=>'ativo',$tipo.'_id'=>$id]);
            $this->turma($id,$id,$id+4);
        }
        $this->turma(3,1,5);
        foreach ([1=>'coordenador',2=>'professor',3=>'secretario_escolar',4=>'aluno'] as $id=>$perfil) {
            Usuario::forceCreate(['id'=>$id,'perfil'=>$perfil,'nome'=>'Conta fictícia','email'=>'turma'.$id.'@example.test','cpf'=>str_pad((string)$id,11,'0',STR_PAD_LEFT),'password'=>'ficticio','ativo'=>true]);
            DB::table('usuario_vinculos')->insert(['id'=>$id,'usuario_id'=>$id,'espaco_id'=>5,'perfil'=>$perfil,'situacao'=>'ativo','inicio_em'=>now()->subDay(),'versao_autorizacao'=>1]);
        }
        DB::table('vinculo_turmas')->insert(['vinculo_id'=>2,'espaco_id'=>5,'turma_id'=>1]);
        $this->aluno(1); $this->aluno(2,false); $this->aluno(3);
        DB::table('vinculo_alunos')->insert(['vinculo_id'=>4,'espaco_id'=>5,'aluno_id'=>1]);
        $this->prova(1); $this->prova(2);
        $this->ator(1);
    }

    private function turma(int $id,int $escola,int $espaco): void
    {
        DB::table('turmas')->insert(['id'=>$id,'escola_id'=>$escola,'espaco_id'=>$espaco,'nome'=>'Turma fictícia','serie'=>'3','turno'=>'tarde','ano_letivo'=>2026]);
    }

    private function aluno(int $id,bool $ativo=true,int $turma=1,int $espaco=5): void
    {
        DB::table('alunos')->insert(['id'=>$id,'turma_id'=>$turma,'espaco_id'=>$espaco,'nome'=>sprintf('Aluno fictício %03d',$id),'matricula'=>'FICTICIO-'.$id,'ativo'=>$ativo]);
    }

    private function prova(int $id,int $turma=1,int $espaco=5): void
    {
        DB::table('provas')->insert(['id'=>$id,'escola_id'=>$espaco===5?1:2,'espaco_id'=>$espaco,'criado_por'=>1,'perfil_criacao'=>'coordenador',
            'titulo'=>'Prova fictícia '.$id,'disciplina'=>'Teste','serie'=>'3','num_questoes'=>1,'num_alternativas'=>4,'status'=>'publicada','data_aplicacao'=>'2026-11-01']);
        DB::table('prova_turmas')->insert(['prova_id'=>$id,'turma_id'=>$turma,'espaco_id'=>$espaco]);
    }

    private function nota(int $aluno,int $prova,float $nota,int $turma=1,int $espaco=5): int
    {
        $cartao=DB::table('cartoes')->insertGetId(['prova_id'=>$prova,'aluno_id'=>$aluno,'turma_aplicacao_id'=>$turma,'espaco_id'=>$espaco,'status'=>'lido']);
        return DB::table('notas')->insertGetId(['cartao_id'=>$cartao,'prova_id'=>$prova,'aluno_id'=>$aluno,'turma_id'=>$turma,'espaco_id'=>$espaco,'acertos'=>1,'total_questoes'=>1,'nota_final'=>$nota,'status_aprovacao'=>'aprovado']);
    }

    private function ator(int $id): void
    {
        Sanctum::actingAs(Usuario::findOrFail($id)->noVinculo($id));
    }

    public function test_metricas_preservam_historico_ausencia_de_notas_e_alunos_inativos(): void
    {
        $this->nota(1,1,8); $this->nota(1,2,6); $this->nota(2,1,4);
        $this->prova(3,3); $this->nota(1,3,10,3);
        $r=$this->getJson('/api/v1/turmas/1')->assertOk()->assertJsonPath('data.total_alunos',2)->assertJsonPath('data.total_provas',2)->assertJsonPath('data.media_turma',6);
        $alunos=collect($r->json('data.alunos'))->keyBy('id');
        $this->assertSame(8.0,(float)$alunos[1]['media_geral']); $this->assertSame(3,$alunos[1]['total_provas']);
        $this->assertSame(4.0,(float)$alunos[2]['media_geral']); $this->assertSame(1,$alunos[2]['total_provas']);
        $this->assertNull($alunos[3]['media_geral']); $this->assertSame(0,$alunos[3]['total_provas']);
        foreach ($r->json('data.provas') as $prova) $this->assertSame(6.0,(float)$prova['media']);
    }

    public function test_crescer_alunos_e_provas_nao_acrescenta_consultas_por_registro(): void
    {
        $this->nota(1,1,8);
        $contar=function():int {
            DB::enableQueryLog(); DB::flushQueryLog();
            try { $this->getJson('/api/v1/turmas/1')->assertOk(); return count(DB::getQueryLog()); }
            finally { DB::disableQueryLog(); DB::flushQueryLog(); }
        };
        $pequena=$contar();
        for ($id=4;$id<=28;$id++) $this->aluno($id);
        for ($id=3;$id<=9;$id++) {
            $this->prova($id);
            foreach ([1,4,5,6,7] as $aluno) $this->nota($aluno,$id,7);
        }
        $grande=$contar();
        $this->assertLessThanOrEqual($pequena+2,$grande,'A quantidade de SQL não pode crescer por aluno/prova.');
        $this->assertLessThan(1500,$grande,'A leitura deve manter um orçamento finito de consultas com todos os filtros de escopo.');
    }

    public function test_agregados_recusam_espaco_cruzado_e_professor_nao_ganha_outra_turma(): void
    {
        $this->nota(1,1,8); $this->aluno(4,true,2,6); $this->prova(3,2,6); $this->nota(4,3,1,2,6);
        $corrompida=$this->nota(1,2,2); DB::table('notas')->where('id',$corrompida)->update(['espaco_id'=>6]);
        $this->getJson('/api/v1/turmas/2')->assertNotFound();
        $r=$this->getJson('/api/v1/turmas/1')->assertOk()->assertJsonPath('data.media_turma',8);
        $alunos=collect($r->json('data.alunos'))->keyBy('id');$this->assertSame(1,$alunos[1]['total_provas']);
        $this->ator(2);
        $this->getJson('/api/v1/turmas/1')->assertOk()->assertJsonPath('data.media_turma',8);
        $this->getJson('/api/v1/turmas/3')->assertNotFound();
        $this->getJson('/api/v1/turmas/2')->assertNotFound();
    }

    public function test_secretaria_nao_recebe_metricas_e_aluno_nao_ganha_lista_da_turma(): void
    {
        $this->nota(1,1,8); $this->ator(3);
        $r=$this->getJson('/api/v1/turmas/1')->assertOk()->assertJsonMissingPath('data.media_turma')->assertJsonMissingPath('data.provas');
        foreach ($r->json('data.alunos') as $aluno) { $this->assertArrayNotHasKey('media_geral',$aluno);$this->assertArrayNotHasKey('total_provas',$aluno); }
        $this->ator(4);$this->getJson('/api/v1/turmas/1')->assertForbidden();
    }
}

