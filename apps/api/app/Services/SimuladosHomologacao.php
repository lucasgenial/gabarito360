<?php

namespace App\Services;

use App\Models\Usuario;
use App\Support\{ContextoVinculo, DadoProtegido, Permissao, ReferenciasDisciplinas};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Adaptação fechada dos dados de desenvolvimento autorizados; não é serviço de migração escolar. */
final class SimuladosHomologacao
{
    private const MARCA = 'dc1-simulados-homologacao-v1';
    private const MAPA = [
        3 => ['Linguagens', ['portugues','arte','educacao-fisica','ingles','espanhol']],
        4 => ['Ciências Humanas', ['historia','geografia','filosofia','sociologia']],
        5 => ['Ciências da Natureza', ['biologia','fisica','quimica']],
        6 => ['Matemática', ['matematica']],
    ];

    public function executar(bool $aplicar = false): array
    {
        $this->ambiente();
        $this->conferir();
        $concluida = DB::table('disciplina_eventos')->where('espaco_id',13)->where('entidade','ensaio_dc1')->where('acao',self::MARCA)->exists();
        if ($concluida) {
            $this->conferirResultado();
            return ['aplicado'=>false,'ja_preparado'=>true,'provas'=>4,'professores_teste'=>13];
        }
        if (!$aplicar) return ['simulacao'=>true,'provas'=>4,'areas'=>4,'disciplinas'=>13,'professores_teste'=>13,'gravacoes'=>false];
        $conexao=DB::connection();
        if (in_array($conexao->getDriverName(),['mysql','mariadb'],true)) {
            if ($conexao->transactionLevel()!==0) throw new \LogicException('Operação exige transação própria.');
            $conexao->statement('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
        }
        return $conexao->transaction(function () {
            DB::table('espacos')->where('id',13)->lockForUpdate()->first();
            $this->conferir();
            // A trava do espaço serializa duas execuções, incluindo a verificação de conclusão.
            if (DB::table('disciplina_eventos')->where('espaco_id',13)->where('entidade','ensaio_dc1')->where('acao',self::MARCA)->exists()) {
                $this->conferirResultado();
                return ['aplicado'=>false,'ja_preparado'=>true];
            }
            $antes=$this->preservados();
            $usuarios=DB::table('usuarios')->orderBy('id')->get()->keyBy('id');
            $ator=Usuario::findOrFail(10)->noVinculo(7); $v=ContextoVinculo::resolver($ator);
            $gestao=app(GestaoDisciplinas::class); $areas=app(GestaoAreasConhecimento::class);
            $refs=collect(ReferenciasDisciplinas::todas())->keyBy('chave');
            $refs['espanhol']=['nome'=>'Língua Espanhola','chave'=>null];
            $base=Usuario::porEmail('professor@gabarito360.dev')->lockForUpdate()->firstOrFail();
            $numero=0; $contas=[];
            foreach (self::MAPA as $prova=>[$nomeArea,$chaves]) {
                $a=$areas->salvar($ator,['nome'=>$nomeArea,'ativo'=>true]);
                $disciplinas=[];
                foreach ($chaves as $chave) {
                    $numero++; $ref=$refs[$chave];
                    $d=$gestao->salvar($ator,['nome'=>$ref['nome'],'referencia'=>$ref['chave'],'ativo'=>true,'area_ids'=>[(int)$a->id]]);
                    // Documento sem validade civil, reservado para teste; não promove identidade verificada.
                    $p=Usuario::create(['perfil'=>'professor','nome'=>'Professor de teste '.$numero.' — '.$ref['nome'],
                        'email'=>'professor'.$numero.'@gabarito360.dev','cpf'=>'0000080'.str_pad((string)$numero,4,'0',STR_PAD_LEFT),
                        'password'=>$base->getRawOriginal('password'),'ativo'=>true,'observacoes'=>self::MARCA.'; conta fictícia; aceite de ensaio autorizado; não é identidade verificada.']);
                    $contas[]=(int)$p->id;
                    $vinculo=DB::table('usuario_vinculos')->insertGetId(['usuario_id'=>$p->id,'espaco_id'=>13,'perfil'=>'professor','situacao'=>'ativo',
                        'inicio_em'=>now(),'criado_por'=>10,'created_at'=>now(),'updated_at'=>now()]);
                    DB::table('vinculo_eventos')->insert(['vinculo_id'=>$vinculo,'acao'=>'ativado','ator_id'=>10,'ocorrido_em'=>now(),'motivo_tecnico'=>self::MARCA]);
                    $gestao->indicar($ator,(int)$d->id,['vinculo_id'=>$vinculo,'ativo'=>true]);
                    foreach ([14,15] as $turma) {
                        DB::table('vinculo_turmas')->insert(['vinculo_id'=>$vinculo,'espaco_id'=>13,'turma_id'=>$turma]);
                        $c=$gestao->adicionarComponente($ator,$turma,(int)$d->id);
                        $gestao->atribuir($ator,$turma,(int)$c->id,['vinculo_id'=>$vinculo,'inicio_em'=>now('America/Sao_Paulo')->toDateString(),
                            'fim_em'=>null,'papel'=>'titular','versao'=>(int)$c->versao,'chave'=>(string)Str::uuid()]);
                    }
                    $disciplinas[]=(int)$d->id;
                }
                app(CurriculoProva::class)->validar($ator,[14,15],$disciplinas,'criar');
                $p=DB::table('provas')->where('id',$prova)->lockForUpdate()->first();
                // A primeira atribuição de procedência é permitida pelo gatilho; nunca derrubar a imutabilidade.
                DB::table('provas')->where('id',$prova)->update(['vinculo_criacao_id'=>7,'autoria_origem'=>'adaptacao_desenvolvimento']);
                DB::table('prova_curriculos')->insert(['espaco_id'=>13,'prova_id'=>$prova,'versao'=>1,'ator_vinculo_id'=>7,
                    'publicado_em'=>now(),'created_at'=>now(),'updated_at'=>now()]);
                foreach ($disciplinas as $id) {
                    $d=DB::table('disciplinas')->find($id);
                    DB::table('prova_disciplinas')->insert(['espaco_id'=>13,'prova_id'=>$prova,'disciplina_id'=>$id,'nome'=>$d->nome,'sigla'=>$d->sigla]);
                    foreach (DB::table('disciplina_areas')->where('espaco_id',13)->where('disciplina_id',$id)->pluck('vinculo_id') as $periodo)
                        \App\Services\PeriodosAreaDisciplina::registrarUso(13,$prova,(int)$periodo);
                }
                $gestao->evento($v,'prova_curriculo',$prova,'adaptacao_desenvolvimento',
                    (object)['perfil'=>$p->perfil_criacao,'vinculo'=>$p->vinculo_criacao_id,'origem'=>$p->autoria_origem],
                    (object)['perfil'=>'coordenador','vinculo'=>7,'origem'=>'adaptacao_desenvolvimento','disciplina_ids'=>$disciplinas,'data_preparacao'=>now()->toIso8601String()]);
            }
            if ($antes!==$this->preservados()) throw new \RuntimeException('Dados acadêmicos divergiram; reverter a transação.');
            foreach ($usuarios as $id=>$registro) if ((array)$registro!==(array)DB::table('usuarios')->find($id)) throw new \RuntimeException('Conta preexistente alterada.');
            $gestao->evento($v,'ensaio_dc1',13,self::MARCA,null,(object)['provas'=>array_keys(self::MAPA),'usuarios_teste'=>$contas,'aceite'=>'ensaio_autorizado','identidade_verificada'=>false,'resultado_hash'=>$this->resultadoHash()]);
            $this->conferirResultado();
            return ['aplicado'=>true,'provas'=>4,'professores_teste'=>13,'academico_preservado'=>true,'usuarios_anteriores_preservados'=>true];
        });
    }

    private function ambiente(): void
    {
        $teste=app()->environment('testing') && DB::getDriverName()==='sqlite';
        if (!app()->runningInConsole() || (!$teste && !(app()->environment('staging') && DB::connection()->getDatabaseName()==='gabarito360_homolog_snapshot_20261103')))
            throw new \LogicException('Operação exclusiva da homologação.');
        if (!config('disciplinas.ativo') || !config('espacos.ativo')) throw new \LogicException('Módulos indisponíveis.');
    }

    private function conferir(): void
    {
        $escola=DB::table('escolas')->find(8); $espaco=DB::table('espacos')->find(13);
        if (!$escola || $escola->inep!=='29428254' || !$espaco || (int)$espaco->escola_id!==8 || $espaco->tipo!=='escola') throw new \LogicException('Escola divergente.');
        $ator=Usuario::findOrFail(10)->noVinculo(7);
        if ($ator->perfil!=='coordenador' || (int)ContextoVinculo::resolver($ator)->espaco_id!==13 || !Permissao::pode($ator,'disciplina.gerir')) throw new \LogicException('Ator divergente.');
        $concluida=DB::table('disciplina_eventos')->where('espaco_id',13)->where('entidade','ensaio_dc1')->where('acao',self::MARCA)->exists();
        foreach (self::MAPA as $id=>[$nome]) {
            $p=DB::table('provas')->find($id);
            if (!$p || $p->titulo!=='Simulado ENEM 2026' || $p->disciplina!==$nome || (int)$p->criado_por!==10 || $p->perfil_criacao!=='coordenador'
                || (int)$p->espaco_id!==13 || (int)$p->escola_id!==8 || $p->status==='rascunho') throw new \LogicException('Prova divergente.');
            if (!$concluida && ($p->autoria_origem!==null || $p->vinculo_criacao_id!==null || DB::table('prova_curriculos')->where('prova_id',$id)->exists())) throw new \LogicException('Adaptação anterior não reconhecida.');
            $turmas=DB::table('prova_turmas')->where('espaco_id',13)->where('prova_id',$id)->orderBy('turma_id')->pluck('turma_id')->map(fn($x)=>(int)$x)->all();
            if ($turmas!==[14,15]) throw new \LogicException('Turmas divergentes.');
        }
        if (!$concluida) {
            if (DB::table('disciplinas')->where('espaco_id',13)->exists() || DB::table('areas_conhecimento')->where('espaco_id',13)->exists()) throw new \LogicException('Catálogo já preenchido; conferir manualmente.');
            $base=Usuario::porEmail('professor@gabarito360.dev')->firstOrFail();
            if (!$base->ativo || !$base->getRawOriginal('password')) throw new \LogicException('Conta-base indisponível.');
            for ($i=1;$i<=13;$i++) if (Usuario::porEmail('professor'.$i.'@gabarito360.dev')->exists() || Usuario::where('cpf_hash',DadoProtegido::hash('0000080'.str_pad((string)$i,4,'0',STR_PAD_LEFT),'cpf'))->exists()) throw new \LogicException('Identificador de teste em uso.');
        }
    }

    /** Compara todas as colunas originais, exceto os dois campos de autoria explicitamente adaptados. */
    private function preservados(): array
    {
        $r=[];
        foreach (['provas','prova_turmas','gabaritos','gabarito_questoes','cartoes','cartao_respostas','notas','alunos','turmas'] as $t) {
            $linhas=DB::table($t)->get()->map(function($v) use($t) { $a=(array)$v; if ($t==='provas' && in_array((int)$a['id'],array_keys(self::MAPA),true)) unset($a['vinculo_criacao_id'],$a['autoria_origem']); ksort($a); return json_encode($a,JSON_THROW_ON_ERROR); })->all();
            sort($linhas,SORT_STRING); $r[$t]=hash('sha256',implode("\n",$linhas));
        }
        return $r;
    }

    private function conferirResultado(): void
    {
        $evento=DB::table('disciplina_eventos')->where('espaco_id',13)->where('entidade','ensaio_dc1')->where('acao',self::MARCA)->first();
        $dados=$evento ? json_decode($evento->depois,true,512,JSON_THROW_ON_ERROR) : [];
        if (!isset($dados['resultado_hash']) || !hash_equals($dados['resultado_hash'],$this->resultadoHash())) throw new \LogicException('O estado preparado mudou; não reaplicar a operação pontual.');
        foreach (self::MAPA as $id=>[$area,$chaves]) {
            $p=DB::table('provas')->find($id);
            if ($p->autoria_origem!=='adaptacao_desenvolvimento' || (int)$p->vinculo_criacao_id!==7 || DB::table('prova_disciplinas')->where('espaco_id',13)->where('prova_id',$id)->count()!==count($chaves)) throw new \LogicException('Adaptação incompleta.');
        }
        for ($i=1;$i<=13;$i++) {
            $p=Usuario::porEmail('professor'.$i.'@gabarito360.dev')->firstOrFail();
            if (!$p->ativo || !str_contains($p->observacoes??'',self::MARCA)) throw new \LogicException('Conta de teste divergente.');
        }
    }

    /** Impressão digital, sem registrar documentos, e-mails ou hashes de senha na auditoria. */
    private function resultadoHash(): string
    {
        $estado=[];
        foreach (['areas_conhecimento','disciplinas','disciplina_areas','disciplina_area_vinculos','disciplina_professores','turma_disciplinas','docente_atribuicoes','prova_curriculos','prova_disciplinas','prova_area_vinculos'] as $t) {
            $linhas=DB::table($t)->where('espaco_id',13)->get()->map(fn($r)=>json_encode($r,JSON_THROW_ON_ERROR))->all(); sort($linhas,SORT_STRING); $estado[$t]=$linhas;
        }
        for ($i=1;$i<=13;$i++) {
            $p=Usuario::porEmail('professor'.$i.'@gabarito360.dev')->firstOrFail();
            $estado['conta'.$i]=$p->getAttributes();
            $estado['vinculos'.$i]=DB::table('usuario_vinculos')->where('usuario_id',$p->id)->orderBy('id')->get()->all();
            $estado['turmas'.$i]=DB::table('vinculo_turmas')->whereIn('vinculo_id',DB::table('usuario_vinculos')->where('usuario_id',$p->id)->select('id'))->orderBy('turma_id')->get()->all();
        }
        return hash('sha256',json_encode($estado,JSON_THROW_ON_ERROR));
    }
}
