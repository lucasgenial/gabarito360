<?php

namespace App\Services;

use App\Models\{Prova, Usuario};
use App\Support\{ContextoVinculo, Escopo, Espacos};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Relação curricular externa: não altera autoria, gabarito ou dados da aplicação. */
final class CurriculoProva
{
    use \Illuminate\Database\DetectsConcurrencyErrors;

    /** A revalidação após esperar por travas precisa enxergar as revogações confirmadas. */
    public function transacionar(Usuario $u, \Closure $operacao): mixed
    {
        $conexao=DB::connection();
        if (!self::aplicavel($u) || !in_array($conexao->getDriverName(),['mysql','mariadb'],true)) {
            return $conexao->transaction($operacao,3);
        }
        if ($conexao->transactionLevel()!==0) {
            throw new \LogicException('A gravação curricular exige uma transação própria para revalidar o acesso atual.');
        }
        for ($tentativa=1; ; $tentativa++) {
            // SET TRANSACTION vale só para o próximo BEGIN, inclusive em cada repetição.
            $conexao->statement('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
            try {
                return $conexao->transaction($operacao,1);
            } catch (\Throwable $e) {
                if ($tentativa>=3 || !$this->causedByConcurrencyError($e)) throw $e;
            }
        }
    }

    public static function aplicavel(Usuario $u): bool
    {
        return config('disciplinas.ativo') && Espacos::ativo() && ContextoVinculo::presente($u)
            && ContextoVinculo::resolver($u)->espaco->tipo === 'escola';
    }

    /** Antes da trava da prova; revalida o contexto na mesma transação da gravação. */
    public function travar(Usuario $u): void
    {
        if (!self::aplicavel($u)) return;
        DB::table('usuario_vinculos')->where('id',$u->vinculoContextoId())->lockForUpdate()->first();
        $v=ContextoVinculo::resolver($u);
        DB::table('espacos')->where('id',$v->espaco_id)->lockForUpdate()->first();
    }

    public function validar(Usuario $u, array $turmas, array $ids, string $operacao): array
    {
        $v=app(GestaoDisciplinas::class)->contexto($u);
        if (!$turmas) throw ValidationException::withMessages(['turma_ids'=>'Selecione ao menos uma turma para configurar a prova.']);
        if (!$ids || count($ids)>200 || count(array_unique($ids))!==count($ids)
            || collect($ids)->contains(fn($id)=>!is_int($id)||$id<1)) {
            throw ValidationException::withMessages(['disciplina_ids'=>'Selecione de uma a 200 disciplinas diferentes para a prova.']);
        }
        // Serializa com inativação, indicação e encerramento de atribuições das disciplinas escolhidas.
        DB::table('disciplinas')->where('espaco_id',$v->espaco_id)->whereIn('id',$ids)->orderBy('id')->lockForUpdate()->get();
        $componentes=DB::table('turma_disciplinas')->where('espaco_id',$v->espaco_id)->whereIn('disciplina_id',$ids)->whereIn('turma_id',$turmas)->orderBy('id')->lockForUpdate()->pluck('id');
        DB::table('turmas')->where('espaco_id',$v->espaco_id)->whereIn('id',$turmas)->orderBy('id')->lockForUpdate()->get();
        if ($v->perfil==='professor') {
            DB::table('vinculo_turmas')->where('vinculo_id',$v->id)->where('espaco_id',$v->espaco_id)->whereIn('turma_id',$turmas)->orderBy('turma_id')->lockForUpdate()->get();
            DB::table('disciplina_professores')->where('vinculo_id',$v->id)->where('espaco_id',$v->espaco_id)->whereIn('disciplina_id',$ids)->orderBy('id')->lockForUpdate()->get();
            DB::table('docente_atribuicoes')->where('vinculo_id',$v->id)->where('espaco_id',$v->espaco_id)->whereIn('turma_disciplina_id',$componentes)->orderBy('id')->lockForUpdate()->get();
        }
        $opcoes=app(OpcoesDisciplinaProva::class)->listar($u,$turmas,$operacao);
        $escolhidas=array_values(array_filter($opcoes,fn($d)=>in_array((int)$d->id,$ids,true)));
        if (count($escolhidas)!==count($ids)) throw ValidationException::withMessages([
            'disciplina_ids'=>'Uma disciplina não está disponível em todas as turmas para o vínculo atual. Confira a seleção e as atribuições antes de continuar.',
        ]);
        return $escolhidas;
    }

    /** Chamado dentro da transação da prova, após travar ator/espaço/prova. */
    public function configurar(Usuario $u, Prova $p, array $turmas, array $ids, ?int $versao, string $operacao): string
    {
        if (DB::transactionLevel()<1) throw new \LogicException('Configuração curricular exige transação.');
        $v=ContextoVinculo::resolver($u);
        abort_unless((int)$p->espaco_id===(int)$v->espaco_id && (int)$p->escola_id===(int)$v->espaco->escola_id,404);
        abort_unless($p->status==='rascunho',422,'A organização curricular só pode ser alterada enquanto a prova está em rascunho.');
        $antes=$this->resumoInterno((int)$p->espaco_id,(int)$p->id);
        abort_if($antes?->publicado_em || DB::table('gabaritos')->where('prova_id',$p->id)->whereNotNull('publicado_em')->exists()
            || DB::table('cartoes')->where('prova_id',$p->id)->exists() || DB::table('notas')->where('prova_id',$p->id)->exists(),422,'Esta prova já possui publicação ou dados de aplicação. Sua organização curricular deve ser preservada.');
        abort_unless(($antes && $versao===(int)$antes->versao) || (!$antes && ($versao===null||$versao===0)),409,'A configuração da prova mudou. Atualize o estado e confira seu preenchimento.');
        $escolhidas=$this->validar($u,$turmas,$ids,$operacao);
        $valores=['versao'=>($antes?->versao??0)+1,'ator_vinculo_id'=>$v->id,'updated_at'=>now()];
        if ($antes) DB::table('prova_curriculos')->where('prova_id',$p->id)->update($valores);
        else DB::table('prova_curriculos')->insert($valores+['espaco_id'=>$v->espaco_id,'prova_id'=>$p->id,'created_at'=>now()]);
        DB::table('prova_disciplinas')->where('espaco_id',$v->espaco_id)->where('prova_id',$p->id)->delete();
        DB::table('prova_disciplinas')->insert(array_map(fn($d)=>['espaco_id'=>$v->espaco_id,'prova_id'=>$p->id,'disciplina_id'=>$d->id,'nome'=>$d->nome,'sigla'=>$d->sigla],$escolhidas));
        // Fotografia dos períodos usados nesta configuração, nunca reconstruída na publicação.
        $periodos=DB::table('disciplina_areas as da')->join('areas_conhecimento as a',function($j){$j->on('a.id','=','da.area_id')->on('a.espaco_id','=','da.espaco_id');})
            ->where('da.espaco_id',$v->espaco_id)->whereIn('da.disciplina_id',$ids)->where('a.ativo',true)->whereNotNull('da.vinculo_id')->orderBy('da.vinculo_id')->pluck('da.vinculo_id');
        foreach ($periodos as $periodo) PeriodosAreaDisciplina::registrarUso((int)$v->espaco_id,(int)$p->id,(int)$periodo);
        $depois=$this->resumoInterno((int)$p->espaco_id,(int)$p->id); $depois->turma_ids=$turmas;
        if ($antes) $antes->turma_ids=DB::table('prova_turmas')->where('espaco_id',$v->espaco_id)->where('prova_id',$p->id)->orderBy('turma_id')->pluck('turma_id')->all();
        app(GestaoDisciplinas::class)->evento($v,'prova_curriculo',(int)$p->id,$antes?'atualizado':'configurado',$antes,$depois);
        return count($escolhidas)===1 ? $escolhidas[0]->nome : 'Multidisciplinar';
    }

    public function conferirPublicacao(Usuario $u, Prova $p, string $operacao): void
    {
        if (!self::aplicavel($u)) return;
        $c=$this->resumoInterno((int)$p->espaco_id,(int)$p->id);
        if (!$c) throw ValidationException::withMessages(['disciplina_ids'=>'Configure as disciplinas e as turmas da prova antes de publicar.']);
        $turmas=DB::table('prova_turmas')->where('espaco_id',$p->espaco_id)->where('prova_id',$p->id)->orderBy('turma_id')->pluck('turma_id')->map(fn($id)=>(int)$id)->all();
        $this->validar($u,$turmas,array_map(fn($d)=>(int)$d->id,$c->disciplinas),$operacao);
    }

    private function resumoInterno(int $espaco, int $prova): ?object
    {
        $c=DB::table('prova_curriculos')->where('espaco_id',$espaco)->where('prova_id',$prova)->first();
        if ($c) $c->disciplinas=DB::table('prova_disciplinas')->where('espaco_id',$espaco)->where('prova_id',$prova)->orderBy('nome')->orderBy('disciplina_id')->get(['disciplina_id as id','nome','sigla'])->all();
        return $c;
    }

    public function registrarPublicacao(Usuario $u, Prova $p): void
    {
        if (self::aplicavel($u)) DB::table('prova_curriculos')->where('espaco_id',$p->espaco_id)->where('prova_id',$p->id)->whereNull('publicado_em')->update(['publicado_em'=>now()]);
    }

    /** A interface não deve oferecer reclassificação ao reabrir uma prova já aplicada. */
    public function estado(Prova $p): array
    {
        $c=DB::table('prova_curriculos')->where('espaco_id',$p->espaco_id)->where('prova_id',$p->id)->first();
        $preservado=$p->status!=='rascunho' || $c?->publicado_em
            || DB::table('gabaritos')->where('prova_id',$p->id)->whereNotNull('publicado_em')->exists()
            || DB::table('cartoes')->where('prova_id',$p->id)->exists() || DB::table('notas')->where('prova_id',$p->id)->exists();
        return ['configurado'=>(bool)$c,'editavel'=>!$preservado,'bloqueio'=>$preservado?'Classificação preservada após a publicação ou aplicação.':null];
    }

    /** Consultas por lote, sem acrescentar uma consulta por prova à listagem. */
    public function resumos(Usuario $u, array $ids): array
    {
        $resultado=[];
        foreach (array_chunk($ids,500) as $lote) {
            $autorizadas=Escopo::provas($u)->whereIn('provas.id',$lote)->pluck('provas.id');
            $curriculos=DB::table('prova_curriculos')->whereIn('prova_id',$autorizadas)->get()->keyBy('prova_id');
            $disciplinas=DB::table('prova_disciplinas')->whereIn('prova_id',$curriculos->keys())->orderBy('nome')->orderBy('disciplina_id')->get(['prova_id','disciplina_id as id','nome','sigla'])->groupBy('prova_id');
            foreach ($curriculos as $id=>$c) $resultado[$id]=['versao'=>(int)$c->versao,'configurado_em'=>$c->created_at,'atualizado_em'=>$c->updated_at,'publicado_em'=>$c->publicado_em,'disciplinas'=>($disciplinas[$id]??collect())->map(fn($d)=>['id'=>(int)$d->id,'nome'=>$d->nome,'sigla'=>$d->sigla])->all()];
        }
        return $resultado;
    }
}
