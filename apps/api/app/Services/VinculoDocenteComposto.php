<?php
namespace App\Services;

use App\Models\Usuario;
use App\Support\{GestaoVinculos,GruposFuncionais,Permissao};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Uma intenção explícita; os quatro vínculos e seu recibo confirmam ou revertem juntos. */
final class VinculoDocenteComposto
{
    use \Illuminate\Database\DetectsConcurrencyErrors;

    public function __construct(private GestaoDisciplinas $gestao) {}

    /** Depois de esperar por uma trava, os helpers DC1 precisam ver o commit concorrente. */
    private function transacionar(\Closure $acao):mixed
    {
        $c=DB::connection();
        if(!in_array($c->getDriverName(),['mysql','mariadb'],true))return $c->transaction($acao,3);
        if($c->transactionLevel()!==0)throw new \LogicException('A operação composta exige transação própria.');
        for($tentativa=1;;$tentativa++){
            // Só o próximo BEGIN; não modifica o padrão de isolamento da sessão.
            $c->statement('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
            try{return $c->transaction($acao,1);}
            catch(\Throwable $e){if($tentativa>=3 || !$this->causedByConcurrencyError($e))throw $e;}
        }
    }

    private function contexto(Usuario $u): object
    {
        $v=$this->gestao->contexto($u,true);
        abort_unless(Permissao::pode($u,'turma.vincular_professor'),403);
        return $v;
    }

    private function abertas(int $espaco,int $turma)
    {
        return DB::table('docente_atribuicoes as a')->join('turma_disciplinas as td','td.id','=','a.turma_disciplina_id')
            ->where('a.espaco_id',$espaco)->where('td.espaco_id',$espaco)->where('td.turma_id',$turma)
            ->where('a.situacao','ativa')->where(fn($q)=>$q->whereNull('a.fim_em')->orWhere('a.fim_em','>',now('America/Sao_Paulo')->toDateString()));
    }

    public function opcoes(Usuario $u,int $turma): array
    {
        $v=$this->contexto($u);$t=$this->gestao->turma($u,$turma);
        abort_unless($t->ativo,422,'A turma precisa estar ativa.');
        $disciplinas=$this->gestao->disciplinas($u)->where('d.ativo',true)
            ->whereNotIn('d.id',$this->abertas($v->espaco_id,$turma)->select('td.disciplina_id'))
            ->whereNotIn('d.id',DB::table('turma_disciplinas')->where('espaco_id',$v->espaco_id)->where('turma_id',$turma)->where('ativo',false)->select('disciplina_id'))
            ->orderBy('d.nome')->orderBy('d.id')->get(['d.id','d.nome','d.sigla']);
        $professores=$this->gestao->professores($u)->where('u.id','!=',$u->id)->addSelect('u.id as usuario_id')->get()
            ->filter(function($p)use($v){
                if($p->fim_em && \Carbon\CarbonImmutable::parse($p->fim_em,'UTC')->setTimezone('America/Sao_Paulo')->toDateString()<=now('America/Sao_Paulo')->toDateString())return false;
                try { GruposFuncionais::conferir((int)$p->usuario_id,(int)$v->espaco_id,'professor',now(),$p->fim_em,(int)$p->vinculo_id);return true; }
                catch (\Illuminate\Validation\ValidationException|\Symfony\Component\HttpKernel\Exception\HttpException $e) { return false; }
            })->map(fn($p)=>['vinculo_id'=>(int)$p->vinculo_id,'nome'=>$p->nome])->values()->all();
        return ['disciplinas'=>$disciplinas->all(),'professores'=>$professores,'completo'=>true];
    }

    public function executar(Usuario $u,int $turma,array $d): array
    {
        $d['chave_idempotencia']=strtolower($d['chave_idempotencia']);
        $this->contexto($u);
        return $this->transacionar(function()use($u,$turma,$d){
            DB::table('usuario_vinculos')->where('id',$u->vinculoContextoId())->lockForUpdate()->first();
            $v=$this->contexto($u);
            DB::table('espacos')->where('id',$v->espaco_id)->lockForUpdate()->first();
            $t=$this->gestao->turma($u,$turma);
            $hash=hash('sha256',json_encode(['v1',$turma,(int)$d['disciplina_id'],(int)$d['professor_vinculo_id']],JSON_THROW_ON_ERROR));
            $recibo=DB::table('vinculos_docentes_compostos')->where('ator_vinculo_id',$v->id)->where('chave',$d['chave_idempotencia'])->first();
            if($recibo){
                abort_unless((int)$recibo->espaco_id===(int)$v->espaco_id && hash_equals($recibo->pedido_hash,$hash),409,'Esta chave já foi utilizada para outra operação.');
                return json_decode($recibo->resultado,true,512,JSON_THROW_ON_ERROR)+['repetido'=>true];
            }
            // Disciplina antes de turma: mesma ordem dos escritores DC1 existentes.
            $disciplina=DB::table('disciplinas')->where('espaco_id',$v->espaco_id)->where('id',$d['disciplina_id'])->lockForUpdate()->first();
            abort_unless($disciplina,404);
            $t=DB::table('turmas')->where('espaco_id',$v->espaco_id)->where('id',$t->id)->lockForUpdate()->first();
            abort_unless($t->ativo && $disciplina->ativo,422,'Turma e disciplina precisam estar ativas.');
            abort_if($this->abertas($v->espaco_id,$turma)->where('td.disciplina_id',$disciplina->id)->exists(),409,'A disciplina já possui atribuição em curso ou programada.');
            $componente=DB::table('turma_disciplinas')->where('espaco_id',$v->espaco_id)->where('turma_id',$turma)->where('disciplina_id',$disciplina->id)->lockForUpdate()->first();
            abort_if($componente && !$componente->ativo,422,'Reative explicitamente a disciplina nesta turma antes de vincular.');
            $criado=!$componente;
            $componente??=$this->gestao->adicionarComponente($u,$turma,$disciplina->id);
            $alvo=DB::table('usuario_vinculos')->where('espaco_id',$v->espaco_id)->where('id',$d['professor_vinculo_id'])->where('perfil','professor')->first();
            abort_unless($alvo,404);
            $r=Request::create('/','POST',['vinculo_id'=>$alvo->id]);$r->setUserResolver(fn()=>$u);
            $professor=GestaoVinculos::alvo($r,(int)$alvo->usuario_id,lock:true);
            abort_unless($professor->perfil==='professor' && (int)$professor->espaco_id===(int)$v->espaco_id,404);
            abort_unless($professor->situacao==='ativo' && $professor->inicio_em<=now() && (!$professor->fim_em || $professor->fim_em>now())
                && DB::table('usuarios')->where('id',$professor->usuario_id)->where('ativo',true)->exists(),422,'O vínculo Professor precisa estar aceito e vigente.');
            $fim=$professor->fim_em ? \Carbon\CarbonImmutable::parse($professor->fim_em,'UTC')->setTimezone('America/Sao_Paulo')->toDateString() : null;
            GruposFuncionais::conferir((int)$professor->usuario_id,(int)$v->espaco_id,'professor',now(),$professor->fim_em,(int)$professor->id);
            $indicacao=DB::table('disciplina_professores')->where('espaco_id',$v->espaco_id)->where('disciplina_id',$disciplina->id)->where('vinculo_id',$professor->id)->lockForUpdate()->first();
            $indicada=!$indicacao || !$indicacao->ativo;
            if($indicada)$this->gestao->indicar($u,$disciplina->id,['vinculo_id'=>$professor->id,'ativo'=>true,'versao'=>$indicacao?->versao]);
            $acesso=['espaco_id'=>$v->espaco_id,'turma_id'=>$turma,'vinculo_id'=>$professor->id];
            $concedido=!DB::table('vinculo_turmas')->where($acesso)->exists();
            if($concedido){DB::table('vinculo_turmas')->insert($acesso);GestaoVinculos::evento($professor,$u,'turma_adicionada','turma:'.$turma.'; operacao_composta:'.$d['chave_idempotencia']);}
            $hoje=now('America/Sao_Paulo')->toDateString();
            $atribuicao=$this->gestao->atribuir($u,$turma,$componente->id,['vinculo_id'=>$professor->id,'inicio_em'=>$hoje,'fim_em'=>$fim,'papel'=>'titular','chave'=>$d['chave_idempotencia'],'versao'=>$componente->versao]);
            $resultado=['componente_id'=>(int)$componente->id,'atribuicao_id'=>(int)$atribuicao->id,'inicio_em'=>$hoje,'fim_em'=>$fim,
                'efeitos'=>['componente_criado'=>$criado,'indicacao_criada_ou_reativada'=>$indicada,'acesso_concedido'=>$concedido]];
            DB::table('vinculos_docentes_compostos')->insert(['espaco_id'=>$v->espaco_id,'ator_vinculo_id'=>$v->id,'chave'=>$d['chave_idempotencia'],
                'pedido_hash'=>$hash,'turma_id'=>$turma,'disciplina_id'=>$disciplina->id,'professor_vinculo_id'=>$professor->id,'atribuicao_id'=>$atribuicao->id,
                'resultado'=>json_encode($resultado,JSON_THROW_ON_ERROR),'ocorrido_em'=>now()]);
            return $resultado+['repetido'=>false];
        });
    }
}
