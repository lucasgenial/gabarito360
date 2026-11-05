<?php
namespace Tests\Feature;

use App\Models\{Prova, Usuario};
use App\Services\{CurriculoProva, GestaoDisciplinas};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CurriculoProvaConcorrenciaMariaDbTest extends TestCase
{
    use \Tests\Support\BancoCurriculoDescartavel, \Tests\Support\CenarioDisciplinas {
        \Tests\Support\CenarioDisciplinas::setUp as prepararCenario;
    }

    protected function setUp(): void
    {
        $this->prepararCenario();
        if (!in_array(DB::connection()->getDriverName(),['mysql','mariadb'],true)) $this->markTestSkipped('Exige MariaDB descartável.');
        if (!str_ends_with(DB::connection()->getDatabaseName(),'_test')) throw new \RuntimeException('Use banco descartável _test.');
        config(['database.connections.curriculo_concorrente'=>DB::connection()->getConfig()]);
    }

    protected function tearDown(): void
    {
        DB::purge('curriculo_concorrente');
        parent::tearDown();
    }

    public function test_inativacao_confirmada_apos_primeira_leitura_impede_configuracao_e_nao_muda_isolamento_da_sessao(): void
    {
        $u=Usuario::findOrFail(1)->noVinculo(1);$c=app(CurriculoProva::class);$g=app(GestaoDisciplinas::class);
        $d=$g->salvar($u,['nome'=>'Disciplina concorrente','ativo'=>true]);$g->adicionarComponente($u,1,$d->id);
        $outra=DB::connection('curriculo_concorrente');
        $isolamento=DB::selectOne('SELECT @@session.tx_isolation AS nivel')->nivel;
        $antes=DB::table('provas')->where('id',1)->first();
        try {
            $c->transacionar($u,function() use($c,$u,$d,$outra) {
                $this->assertTrue((bool)DB::table('disciplinas')->where('id',$d->id)->value('ativo'));
                $outra->table('disciplinas')->where('id',$d->id)->update(['ativo'=>false]);
                $c->travar($u);
                $c->configurar($u,Prova::findOrFail(1),[1],[(int)$d->id],null,'editar');
                $this->fail('A disciplina inativada deve ser rejeitada.');
            });
            $this->fail('Era esperada a revalidação curricular.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('disciplina_ids',$e->errors());
        }
        $this->assertDatabaseCount('prova_curriculos',0);$this->assertDatabaseCount('prova_disciplinas',0);
        $this->assertEquals($antes,DB::table('provas')->where('id',1)->first());
        $this->assertSame(0,DB::transactionLevel());
        $this->assertSame($isolamento,DB::selectOne('SELECT @@session.tx_isolation AS nivel')->nivel);
        // Uma transação posterior recupera o padrão da sessão (RR), sem herdar READ COMMITTED.
        if ($isolamento==='REPEATABLE-READ') DB::transaction(function() use($d,$outra) {
            $this->assertFalse((bool)DB::table('disciplinas')->where('id',$d->id)->value('ativo'));
            $outra->table('disciplinas')->where('id',$d->id)->update(['ativo'=>true]);
            $this->assertFalse((bool)DB::table('disciplinas')->where('id',$d->id)->value('ativo'));
        });
    }

    public function test_repeticao_por_deadlock_reaplica_isolamento_e_limita_tentativas(): void
    {
        $u=Usuario::findOrFail(1)->noVinculo(1);$c=app(CurriculoProva::class);$tentativas=0;
        $d=app(GestaoDisciplinas::class)->salvar($u,['nome'=>'Repetição concorrente','ativo'=>true]);
        $outra=DB::connection('curriculo_concorrente');
        $c->transacionar($u,function() use(&$tentativas,$d,$outra) {
            $tentativas++;
            DB::table('disciplinas')->where('id',$d->id)->first();
            if ($tentativas===1) throw new \PDOException('Deadlock found when trying to get lock',40001);
            $outra->table('disciplinas')->where('id',$d->id)->update(['ativo'=>false]);
            $this->assertFalse((bool)DB::table('disciplinas')->where('id',$d->id)->value('ativo'));
        });
        $this->assertSame(2,$tentativas);
        $tentativas=0;
        try {
            $c->transacionar($u,function() use(&$tentativas) { $tentativas++;throw new \PDOException('Deadlock found when trying to get lock',40001); });
            $this->fail('Deve propagar a falha após três tentativas.');
        } catch (\PDOException $e) { $this->assertSame(3,$tentativas); }
        $this->assertSame(0,DB::transactionLevel());
    }

    public function test_recusa_transacao_curricular_aninhada_com_isolamento_desconhecido(): void
    {
        $u=Usuario::findOrFail(1)->noVinculo(1);$executada=false;
        try {
            DB::transaction(function() use($u,&$executada) {
                app(CurriculoProva::class)->transacionar($u,function() use(&$executada){$executada=true;});
            });
            $this->fail('Deve exigir transação própria.');
        } catch (\LogicException $e) { $this->assertFalse($executada); }
        $this->assertSame(0,DB::transactionLevel());
    }
}
