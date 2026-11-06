<?php
namespace Tests\Feature;

use App\Models\Usuario;
use App\Support\Permissao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class DashboardDesempenhoContratoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-06 12:00:00'));
        config(['espacos.ativo'=>true,'vinculos.obrigatorio'=>true]);
        Permissao::limparCache();
        foreach ([1,2] as $id) {
            DB::table('clientes')->insert(['id'=>$id,'nome'=>'Cliente fictício','tipo'=>'rede','modalidade'=>'interno','situacao'=>'ativo']);
            DB::table('redes')->insert(['id'=>$id,'nome'=>'Rede fictícia','tipo'=>'estadual','uf'=>'BA']);
            DB::table('nucleos')->insert(['id'=>$id,'rede_id'=>$id,'nome'=>'Núcleo fictício']);
            DB::table('escolas')->insert(['id'=>$id,'nucleo_id'=>$id,'nome'=>'Escola fictícia '.$id,'inep'=>'0000000'.$id,'tipo_rede'=>'estadual']);
            foreach (['rede'=>0,'nucleo'=>2,'escola'=>4] as $tipo=>$offset) DB::table('espacos')->insert([
                'id'=>$id+$offset,'cliente_id'=>$id,'tipo'=>$tipo,'nome'=>'Espaço fictício','situacao'=>'ativo',$tipo.'_id'=>$id]);
            DB::table('turmas')->insert(['id'=>$id,'escola_id'=>$id,'espaco_id'=>$id+4,'nome'=>'Z','serie'=>'3','turno'=>'tarde','ano_letivo'=>2026]);
        }
        DB::table('turmas')->insert(['id'=>3,'escola_id'=>1,'espaco_id'=>5,'nome'=>'A','serie'=>'3','turno'=>'tarde','ano_letivo'=>2026]);
        foreach ([1=>'coordenador',2=>'professor'] as $id=>$perfil) {
            Usuario::forceCreate(['id'=>$id,'nome'=>'Conta fictícia '.$id,'perfil'=>$perfil,'email'=>'painel'.$id.'@example.test','cpf'=>str_pad((string)$id,11,'0',STR_PAD_LEFT),'password'=>'ficticio','ativo'=>true]);
            DB::table('usuario_vinculos')->insert(['id'=>$id,'usuario_id'=>$id,'espaco_id'=>5,'perfil'=>$perfil,'situacao'=>'ativo','inicio_em'=>now()->subDay(),'versao_autorizacao'=>1]);
        }
        $this->aluno(1,3); $this->aluno(2,1,false); $this->aluno(3);
        $this->prova(1); $this->prova(900,6);
        $this->ator();
    }

    private function ator(int $id=1): void { Sanctum::actingAs(Usuario::findOrFail($id)->noVinculo($id)); }
    private function aluno(int $id,int $turma=1,bool $ativo=true,int $espaco=5): void
    {
        DB::table('alunos')->insert(['id'=>$id,'turma_id'=>$turma,'espaco_id'=>$espaco,'nome'=>sprintf('Fictício %03d',$id),'matricula'=>'FICTICIO-'.$id,'ativo'=>$ativo]);
    }
    private function prova(int $id,int $espaco=5,string $status='publicada'): void
    {
        DB::table('provas')->insert(['id'=>$id,'escola_id'=>$espaco===5?1:2,'espaco_id'=>$espaco,'criado_por'=>1,'perfil_criacao'=>'coordenador',
            'titulo'=>'Prova fictícia '.$id,'disciplina'=>'Teste','serie'=>'3','num_questoes'=>1,'num_alternativas'=>4,'nota_maxima'=>10,
            'status'=>$status,'data_aplicacao'=>now()->addDays(min($id,9))->toDateString(),'updated_at'=>now()->addMinutes($id)]);
        DB::table('prova_turmas')->insert(['prova_id'=>$id,'turma_id'=>$espaco===5?1:2,'espaco_id'=>$espaco]);
    }
    private function cartao(?int $aluno,string $status='lido',int $espaco=5): int
    {
        return DB::table('cartoes')->insertGetId(['prova_id'=>1,'aluno_id'=>$aluno,'turma_aplicacao_id'=>1,'espaco_id'=>$espaco,'status'=>$status]);
    }
    private function nota(int $aluno,float $nota): void
    {
        $cartao=$this->cartao($aluno);
        DB::table('notas')->insert(['cartao_id'=>$cartao,'prova_id'=>1,'aluno_id'=>$aluno,'turma_id'=>1,'espaco_id'=>5,'acertos'=>0,'total_questoes'=>1,'nota_final'=>$nota,'status_aprovacao'=>'recuperacao']);
    }
    private function painel(): \Illuminate\Testing\TestResponse { return $this->getJson('/api/v1/dashboard/coordenador'); }
    private function contar(): int
    {
        DB::enableQueryLog();DB::flushQueryLog();
        try { $this->painel()->assertOk();return count(DB::getQueryLog()); }
        finally { DB::disableQueryLog();DB::flushQueryLog(); }
    }

    public function test_contrato_preserva_campos_tipos_historico_zero_inativo_e_integridade(): void
    {
        DB::table('prova_turmas')->insert(['prova_id'=>1,'turma_id'=>3,'espaco_id'=>5]);
        $this->nota(1,0);$this->nota(2,10);$this->cartao(null,'pendente');$this->cartao(null,'ambiguo');
        $this->aluno(4,2,true,6);$this->cartao(4); // Relação incoerente não entra nos contadores.
        $r=$this->painel()->assertOk()->assertJsonPath('data.escola.id',1)
            ->assertJsonPath('data.kpis.provas_andamento',1)->assertJsonPath('data.kpis.cartoes_pendentes',2)
            ->assertJsonPath('data.kpis.alunos_baixa_media',1)->assertJsonPath('data.kpis.proximas_provas',1)
            ->assertJsonPath('data.provas.0.total_alunos',2)->assertJsonPath('data.provas.0.cartoes_lidos',2);
        $d=$r->json('data');$this->assertEqualsCanonicalizing(['escola','kpis','provas','alunos_atencao','agenda'],array_keys($d));
        $p=$d['provas'][0];$this->assertEqualsCanonicalizing(['id','disciplina','titulo','professor','turmas','turmas_lista','total_alunos','cartoes_lidos','status','data_aplicacao'],array_keys($p));
        $this->assertSame('Conta fictícia 1',$p['professor']);$this->assertSame([['id'=>3,'nome'=>'A'],['id'=>1,'nome'=>'Z']],$p['turmas_lista']);
        $this->assertEqualsCanonicalizing(['A','Z'],explode(', ',$p['turmas']));
        foreach (['id','total_alunos','cartoes_lidos'] as $key) $this->assertIsInt($p[$key]);
        foreach ($d['kpis'] as $value) $this->assertIsInt($value);
        $this->assertSame(1,count($d['alunos_atencao']));$this->assertSame(0.0,(float)$d['alunos_atencao'][0]['media']);
        $this->assertSame('Z',$d['alunos_atencao'][0]['turma']); // Turma da aplicação, não a turma atual A.
        $this->assertSame('2026-11-07',$p['data_aplicacao']);
    }

    public function test_uma_quatro_oito_provas_e_260_alunos_nao_aumentam_consultas_por_item(): void
    {
        $uma=$this->contar();
        for($i=2;$i<=4;$i++)$this->prova($i);
        $quatro=$this->contar();
        for($i=5;$i<=8;$i++)$this->prova($i);
        for($i=5;$i<=261;$i++)$this->aluno($i);
        $oito=$this->contar();
        $this->assertLessThanOrEqual($uma+2,$quatro,'Quatro provas não devem reconstruir escopos por prova.');
        $this->assertLessThanOrEqual($uma+2,$oito,'Oito provas e260 alunos não devem acrescentar consultas por registro.');
        $r=$this->painel()->assertOk()->assertJsonCount(8,'data.provas')->assertJsonCount(4,'data.agenda');
        $this->assertSame(range(8,1),array_column($r->json('data.provas'),'id'));
    }

    public function test_prova_sem_turma_e_sem_leitura_preserva_vazio_zero_e_limite(): void
    {
        DB::table('prova_turmas')->where('prova_id',1)->delete();
        $this->painel()->assertOk()->assertJsonPath('data.provas.0.turmas','—')->assertJsonPath('data.provas.0.turmas_lista',[])
            ->assertJsonPath('data.provas.0.total_alunos',0)->assertJsonPath('data.provas.0.cartoes_lidos',0)->assertJsonPath('data.alunos_atencao',[]);
        for($i=2;$i<=10;$i++)$this->prova($i);
        $r=$this->painel()->assertOk()->assertJsonCount(8,'data.provas');$this->assertSame(range(10,3),array_column($r->json('data.provas'),'id'));
        DB::table('provas')->where('escola_id',1)->update(['status'=>'rascunho']);
        $this->painel()->assertOk()->assertJsonPath('data.provas',[])->assertJsonPath('data.kpis.provas_andamento',0);
    }

    public function test_selecao_alheia_professor_e_revogacao_continuam_recusados(): void
    {
        $this->getJson('/api/v1/dashboard/coordenador?escola_id=2')->assertNotFound();
        $this->ator(2);$this->painel()->assertForbidden();
        $this->ator();DB::table('usuario_vinculos')->where('id',1)->update(['situacao'=>'suspenso']);
        $this->painel()->assertForbidden();
    }
}
