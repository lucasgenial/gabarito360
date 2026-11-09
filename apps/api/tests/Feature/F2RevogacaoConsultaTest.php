<?php

namespace Tests\Feature;

use App\Models\{Nota, Usuario};
use App\Support\{ConsultasPedagogicas, Escopo};
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/** Revisor independente: usa fachadas públicas, nunca o novo módulo de predicados. */
final class F2RevogacaoConsultaTest extends TestCase
{
    use RefreshDatabase;
    use \Tests\Support\CenarioDisciplinas { setUp as private iniciarCenario; }

    protected function setUp(): void
    {
        $this->iniciarCenario();
        config(['vinculos.obrigatorio'=>true]);
        DB::table('provas')->where('id',1)->update(['perfil_criacao'=>'coordenador','status'=>'publicada','data_aplicacao'=>'2026-11-05']);
        DB::table('prova_turmas')->insert(['prova_id'=>1,'turma_id'=>1,'espaco_id'=>5]);
        DB::table('disciplinas')->insert(['id'=>1,'espaco_id'=>5,'nome'=>'Disciplina fictícia','nome_chave'=>'disciplina-ficticia']);
        DB::table('disciplina_professores')->insert(['id'=>1,'espaco_id'=>5,'disciplina_id'=>1,'vinculo_id'=>3]);
        DB::table('turma_disciplinas')->insert(['id'=>1,'espaco_id'=>5,'turma_id'=>1,'disciplina_id'=>1]);
        DB::table('docente_atribuicoes')->insert(['id'=>1,'espaco_id'=>5,'turma_disciplina_id'=>1,'vinculo_id'=>3,
            'inicio_em'=>'2026-11-01','fim_em'=>null,'papel'=>'titular','situacao'=>'ativa',
            'ator_vinculo_id'=>1,'chave'=>(string)Str::uuid(),'pedido_hash'=>str_repeat('a',64)]);
        DB::table('prova_curriculos')->insert(['prova_id'=>1,'espaco_id'=>5,'ator_vinculo_id'=>1]);
        DB::table('prova_disciplinas')->insert(['prova_id'=>1,'disciplina_id'=>1,'espaco_id'=>5,'nome'=>'Disciplina fictícia']);
        DB::table('alunos')->insert(['id'=>1,'espaco_id'=>5,'turma_id'=>1,'nome'=>'Cadastro fictício','matricula'=>'F2-1']);
        DB::table('cartoes')->insert(['id'=>1,'espaco_id'=>5,'prova_id'=>1,'aluno_id'=>1,'turma_aplicacao_id'=>1,'status'=>'lido']);
        DB::table('notas')->insert(['id'=>1,'espaco_id'=>5,'prova_id'=>1,'aluno_id'=>1,'turma_id'=>1,'cartao_id'=>1,
            'acertos'=>1,'total_questoes'=>1,'nota_final'=>10,'status_aprovacao'=>'aprovado']);
    }

    /** Cada query é montada/compilada antes da mudança; nenhum helper é refeito nos asserts. */
    private function consultas(Usuario $u, bool $embutir = false): array
    {
        $c=new ConsultasPedagogicas($u);
        $qs=[
            'escopo_turmas'=>[Escopo::turmas($u),'turmas.id'],
            'escopo_alunos'=>[Escopo::alunos($u),'alunos.id'],
            'escopo_provas'=>[Escopo::provas($u),'provas.id'],
            'consulta_turmas'=>[$c->tabela('turmas'),'turmas.id'],
            'consulta_notas'=>[$c->tabela('notas'),'notas.id'],
            'consulta_cartoes'=>[$c->tabela('cartoes'),'cartoes.id'],
            'modelo_notas'=>[$c->modelo(Nota::class),'notas.id'],
        ];
        foreach($qs as[$q])$q->toSql();
        if($embutir){
            foreach(['turmas','provas','cartoes','notas'] as$t){
                $q=$c->tabela($t);$q->toSql();$qs['clone_'.$t]=[clone$q,$t.'.id'];
                $sub=(clone$q)->select($t.'.id');
                $qs['join_'.$t]=[DB::table($t.' as origem')->joinSub(clone$sub,'restrito','restrito.id','=','origem.id'),'origem.id'];
                $qs['from_'.$t]=[DB::query()->fromSub(clone$sub,'restrito'),'restrito.id'];
                $qs['in_'.$t]=[DB::table($t.' as origem')->whereIn('origem.id',clone$sub),'origem.id'];
            }
        }
        return $qs;
    }

    private function resultados(array $qs, array $ids): void
    {
        foreach($qs as$nome=>[$q,$coluna]){
            // get/count/pluck/exists no MESMO objeto, antes e após alterações.
            foreach(['count','get','pluck','exists'] as$terminal){
                try{
                    $valor=match($terminal){
                        'count'=>$q->count(),
                        'exists'=>$q->exists(),
                        'get'=>$q->get([$coluna])->map(fn($x)=>(int)$x->id)->sort()->values()->all(),
                        'pluck'=>$q->pluck($coluna)->map(fn($x)=>(int)$x)->sort()->values()->all(),
                    };
                    $esperado=match($terminal){'count'=>count($ids),'exists'=>$ids!==[],default=>$ids};
                    $this->assertSame($esperado,$valor,$nome.' / '.$terminal);
                }catch(HttpException$e){
                    if($ids!==[])throw$e;
                    $this->assertSame(403,$e->getStatusCode(),$nome.' / '.$terminal);
                }
            }
        }
    }

    public static function revogacoes(): array
    {
        return [
            'conta'=>['usuarios',2,'ativo',false],
            'vinculo'=>['usuario_vinculos',3,'situacao','encerrado'],
            'cliente'=>['clientes',1,'situacao','encerrado'],
            'espaco'=>['espacos',5,'situacao','suspenso'],
            'nucleo'=>['espacos',3,'situacao','suspenso'],
            'rede'=>['espacos',1,'situacao','suspenso'],
            'pai_cliente'=>['espacos',3,'cliente_id',2],
        ];
    }

    #[DataProvider('revogacoes')]
    public function test_mesmo_ator_e_builder_revalidam_conta_contexto_e_pais(string$t,int$id,string$campo,mixed$valor): void
    {
        $u=Usuario::findOrFail(2)->noVinculo(3);$qs=$this->consultas($u,true);
        $this->resultados($qs,[1]);$antes=DB::table($t)->where('id',$id)->value($campo);
        DB::table($t)->where('id',$id)->update([$campo=>$valor]);$this->resultados($qs,[]);
        DB::table($t)->where('id',$id)->update([$campo=>$antes]);$this->resultados($qs,[1]);
        DB::table($t)->where('id',$id)->update([$campo=>$valor]);$this->resultados($qs,[]);
    }

    public function test_retirada_e_reentrada_da_turma_no_mesmo_builder_nao_conserva_colegas(): void
    {
        $u=Usuario::findOrFail(2)->noVinculo(3);$qs=$this->consultas($u,true);$this->resultados($qs,[1]);
        $vt=(array)DB::table('vinculo_turmas')->where('vinculo_id',3)->first();
        DB::table('vinculo_turmas')->where('vinculo_id',3)->delete();$this->resultados($qs,[]);
        DB::table('vinculo_turmas')->insert($vt);$this->resultados($qs,[1]);
    }

    public static function curriculoRevogado(): array
    {
        return [
            'indicacao'=>['disciplina_professores','ativo',false],
            'atribuicao'=>['docente_atribuicoes','situacao','encerrada'],
            'componente'=>['turma_disciplinas','ativo',false],
            'disciplina'=>['disciplinas','ativo',false],
        ];
    }

    #[DataProvider('curriculoRevogado')]
    public function test_curriculo_atual_limita_provas_cartoes_e_notas_sem_tirar_cadastro(string$t,string$campo,mixed$valor): void
    {
        $u=Usuario::findOrFail(2)->noVinculo(3);$qs=$this->consultas($u,true);$this->resultados($qs,[1]);
        $old=DB::table($t)->where('id',1)->value($campo);DB::table($t)->where('id',1)->update([$campo=>$valor]);
        $cadastro=array_filter($qs,fn($nome)=>str_contains($nome,'turmas')||$nome==='escopo_alunos',ARRAY_FILTER_USE_KEY);
        $pedagogia=array_diff_key($qs,$cadastro);$this->resultados($cadastro,[1]);$this->resultados($pedagogia,[]);
        DB::table($t)->where('id',1)->update([$campo=>$old]);$this->resultados($qs,[1]);
    }

    public function test_grupo_excecao_atual_e_revogacao_na_mesma_escola(): void
    {
        $u=Usuario::findOrFail(2)->noVinculo(3);$qs=$this->consultas($u,true);$this->resultados($qs,[1]);
        DB::table('usuario_vinculos')->insert(['id'=>8,'usuario_id'=>2,'espaco_id'=>5,'perfil'=>'secretario_escolar',
            'situacao'=>'ativo','inicio_em'=>now()->subDay(),'versao_autorizacao'=>1]);$this->resultados($qs,[]);
        DB::table('usuario_funcoes_excecoes')->insert(['usuario_id'=>2,'espaco_id'=>5,'permitir'=>true,'versao'=>1,
            'chamado'=>'QA-F2','motivo'=>'Exceção fictícia','definida_por'=>1]);$this->resultados($qs,[1]);
        DB::table('usuario_funcoes_excecoes')->where('usuario_id',2)->where('espaco_id',5)->update(['permitir'=>false]);$this->resultados($qs,[]);
    }

    public function test_query_de_outro_perfil_ou_espaco_nao_muda_de_ramo_em_silencio(): void
    {
        Usuario::forceCreate(['id'=>6,'perfil'=>'professor','nome'=>'Fictício','email'=>'6@example.test','cpf'=>'00000000006','password'=>'ficticio','ativo'=>true]);
        DB::table('usuario_vinculos')->insert(['id'=>10,'usuario_id'=>6,'espaco_id'=>5,'perfil'=>'coordenador','situacao'=>'ativo','inicio_em'=>now()->subDay()]);
        $u=Usuario::findOrFail(6)->noVinculo(10);$q=Escopo::turmas($u);$q->toSql();$clone=clone$q;
        $this->resultados([[$q,'turmas.id'],[$clone,'turmas.id']],[1]);
        DB::table('usuario_vinculos')->where('id',10)->update(['espaco_id'=>6]);
        $this->resultados([[$q,'turmas.id'],[$clone,'turmas.id']],[]);
        $this->resultados([[Escopo::turmas($u),'turmas.id']],[2]);
        DB::table('usuario_vinculos')->where('id',10)->update(['espaco_id'=>5]);
        DB::table('vinculo_turmas')->insert(['vinculo_id'=>10,'espaco_id'=>5,'turma_id'=>1]);
        DB::table('usuario_vinculos')->where('id',10)->update(['perfil'=>'professor']);
        $this->resultados([[$q,'turmas.id'],[$clone,'turmas.id']],[]);
        $this->resultados([[Escopo::turmas($u),'turmas.id']],[1]);
    }

    public function test_pessoal_nao_usa_espaco_alheio_e_revalida_proprietario_suspenso(): void
    {
        config(['espacos.pessoal'=>true]);
        DB::table('clientes')->insert(['id'=>9,'tipo'=>'professor','nome'=>'Cliente fictício','modalidade'=>'interno','situacao'=>'ativo']);
        DB::table('espacos')->insert(['id'=>9,'cliente_id'=>9,'tipo'=>'pessoal','nome'=>'Pessoal fictício','situacao'=>'ativo','usuario_proprietario_id'=>3]);
        DB::table('usuario_vinculos')->insert(['id'=>9,'usuario_id'=>3,'espaco_id'=>9,'perfil'=>'professor_autonomo','situacao'=>'ativo','inicio_em'=>now()->subDay()]);
        DB::table('turmas')->insert(['id'=>9,'espaco_id'=>9,'espaco_tipo'=>'pessoal','nome'=>'Pessoal','serie'=>'1','turno'=>'tarde','ano_letivo'=>2026]);
        DB::table('alunos')->insert(['id'=>9,'espaco_id'=>9,'espaco_tipo'=>'pessoal','turma_id'=>9,'nome'=>'Cadastro mínimo','matricula'=>'F2-P9']);
        DB::table('provas')->insert(['id'=>9,'espaco_id'=>9,'espaco_tipo'=>'pessoal','criado_por'=>3,'titulo'=>'Pessoal fictícia','disciplina'=>'Fictícia','serie'=>'1','num_questoes'=>1,'num_alternativas'=>4]);
        $u=Usuario::findOrFail(3)->noVinculo(9);$qs=['turmas'=>[Escopo::turmas($u),'turmas.id'],'alunos'=>[Escopo::alunos($u),'alunos.id'],'provas'=>[Escopo::provas($u),'provas.id']];
        foreach($qs as[$q])$q->toSql();$this->resultados($qs,[9]);
        DB::table('usuarios')->where('id',3)->update(['ativo'=>false]);$this->resultados($qs,[]);
        DB::table('usuarios')->where('id',3)->update(['ativo'=>true]);$this->resultados($qs,[9]);
    }

    public function test_fim_utc_exato_nao_fica_congelado_apos_tosql_join_e_clone(): void
    {
        DB::table('usuario_vinculos')->where('id',3)->update(['fim_em'=>now()->addSecond()]);
        $u=Usuario::findOrFail(2)->noVinculo(3);$qs=$this->consultas($u,true);$this->resultados($qs,[1]);
        $this->travelTo(CarbonImmutable::now('UTC')->addSecond());$this->resultados($qs,[]);
    }

    public function test_meia_noite_sp_da_atribuicao_nao_e_meia_noite_utc_do_aplicador(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-05 02:59:59','UTC'));
        DB::table('docente_atribuicoes')->where('id',1)->update(['fim_em'=>'2026-11-05']);
        DB::table('usuario_vinculos')->insert(['id'=>8,'usuario_id'=>3,'espaco_id'=>5,'perfil'=>'aplicador','situacao'=>'ativo','inicio_em'=>now()->subDay()]);
        $prof=Usuario::findOrFail(2)->noVinculo(3);$aplicador=Usuario::findOrFail(3)->noVinculo(8);
        $p=Escopo::provas($prof);$a=Escopo::provas($aplicador);$p->toSql();$a->toSql();
        $this->resultados([[$p,'provas.id'],[$a,'provas.id']],[1]);
        $this->travelTo(CarbonImmutable::parse('2026-11-05 03:00:00','UTC'));
        $this->resultados([[$p,'provas.id']],[]);$this->resultados([[$a,'provas.id']],[1]);
    }

    public function test_dia_utc_do_aplicador_e_avaliado_na_execucao_do_builder_antigo(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-04 23:59:59','UTC'));
        DB::table('usuario_vinculos')->insert(['id'=>8,'usuario_id'=>3,'espaco_id'=>5,'perfil'=>'aplicador','situacao'=>'ativo','inicio_em'=>now()->subDay()]);
        $u=Usuario::findOrFail(3)->noVinculo(8);$q=Escopo::provas($u);$q->toSql();$this->resultados([[$q,'provas.id']],[]);
        $this->travelTo(CarbonImmutable::parse('2026-11-05 00:00:00','UTC'));$this->resultados([[$q,'provas.id']],[1]);
    }
}
