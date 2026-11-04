<?php
namespace App\Http\Controllers;

use App\Services\ApiClient;
use App\Support\{Acesso, ContextoWeb};
use Illuminate\Http\Request;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\MessageBag;

/** Preparação local do formulário: só os comandos finais escrevem na API. */
class CurriculoProvaController extends Controller
{
    public function __construct(private ApiClient $api) {}

    public static function ativo(): bool
    {
        return config('disciplinas.ativo') && session('auth_vinculos')
            && (ContextoWeb::atual()['espaco']['tipo']??null)==='escola';
    }

    private function autorizar(?int $id): void
    {
        abort_unless(self::ativo(),404);
        abort_unless(Acesso::pode($id?'prova.editar':'prova.criar'),403);
    }

    private function prova(int $id): array
    {
        try { $r=$this->api->get('/v1/provas/'.$id); }
        catch (ConnectionException) { abort(503,'Não foi possível consultar a prova. Tente novamente.'); }
        abort_unless($r->successful(),in_array($r->status(),[403,404])?$r->status():503);
        $p=$r->json('data');abort_unless($p['pode_editar']??false,403);
        return $p;
    }

    public function novo(Request $r) { $this->autorizar(null);return $this->formulario($r); }
    public function editar(Request $r,int $id) { $this->autorizar($id);return $this->formulario($r,$id); }
    public function preparar(Request $r,?int $id=null)
    {
        $this->autorizar($id);
        try { return $this->formulario($r,$id); }
        catch(\Illuminate\Validation\ValidationException $e) {
            return redirect()->route($id?'provas.curriculo.edit':'provas.create',$id?['id'=>$id]:[])->withInput()->withErrors($e->errors());
        }
    }

    /** Rascunho explícito; não confiar em nomes, capacidades ou estado de prova vindos do browser. */
    private function rascunho(Request $r,?array $p): array
    {
        $base=['turma_ids'=>array_column($p['turmas']??[],'id'),'disciplina_ids'=>array_column($p['curriculo']['disciplinas']??[],'id'),
            'curriculo_versao'=>$p['curriculo']['versao']??0,'titulo'=>'','serie'=>'','num_questoes'=>20,'num_alternativas'=>5,
            'nota_maxima'=>10,'gerar_cartao_pdf'=>1,'formato_avaliacao'=>'gabarito','data_aplicacao'=>'','escola_id'=>''];
        if ($r->isMethod('post')) {
            $d=$r->validate(['turma_ids'=>'sometimes|array|max:500','turma_ids.*'=>'integer|min:1|distinct',
                'disciplina_ids'=>'sometimes|array|max:200','disciplina_ids.*'=>'integer|min:1|distinct',
                'curriculo_versao'=>'nullable|integer|min:0','acao'=>'nullable|string|max:30','nova_disciplina_id'=>'nullable|integer|min:1',
                'revisao_pendente'=>'nullable|in:1','conferido'=>'nullable|in:1']);
            $campos=['titulo'=>255,'serie'=>50,'data_aplicacao'=>10,'num_questoes'=>4,'num_alternativas'=>1,'nota_maxima'=>10,'formato_avaliacao'=>30,'escola_id'=>20];
            foreach(array_keys($campos) as $campo) if(is_scalar($r->input($campo)))$r->merge([$campo=>(string)$r->input($campo)]);
            foreach($campos as $campo=>$max) $r->validate([$campo=>'nullable|string|max:'.$max]);
            $dados=$r->only(array_keys($campos));
            foreach(['anular_se_todas','opcao_lingua','gerar_cartao_pdf'] as $campo) $dados[$campo]=$r->boolean($campo)?1:0;
            return $dados+$d+['turma_ids'=>[],'disciplina_ids'=>[]]+$base;
        }
        $anteriores=array_intersect_key((array)$r->session()->getOldInput(),$base);
        foreach($anteriores as $campo=>$valor) {
            if(in_array($campo,['turma_ids','disciplina_ids'],true)) {
                $anteriores[$campo]=array_slice(array_values(array_unique(array_filter((array)$valor,fn($i)=>is_scalar($i)&&ctype_digit((string)$i)&&(int)$i>0))),0,$campo==='turma_ids'?500:200);
            } elseif(!is_scalar($valor) && $valor!==null) unset($anteriores[$campo]);
        }
        return $anteriores+$base;
    }

    private function consultar(string $path,array $dados=[],bool $post=false): ?\Illuminate\Http\Client\Response
    {
        try { return $post?$this->api->post($path,$dados):$this->api->get($path,$dados); }
        catch (ConnectionException) { return null; }
    }

    private function formulario(Request $r,?int $id=null,?array $erros=null,int $status=200,bool $forcarConflito=false)
    {
        $p=$id?$this->prova($id):null;
        if ($p && !($p['organizacao_curricular']['editavel']??false)) {
            return redirect()->route('provas.show',$id)->with('erro','Classificação preservada após a publicação ou aplicação.');
        }
        $f=$this->rascunho($r,$p);$acao=$r->input('acao','');$mensagens=new MessageBag($erros??[]);
        $f['turma_ids']=array_map('intval',(array)$f['turma_ids']);$f['disciplina_ids']=array_map('intval',(array)$f['disciplina_ids']);
        if (preg_match('/^remover:(\d+)$/D',$acao,$m)) $f['disciplina_ids']=array_values(array_diff($f['disciplina_ids'],[(int)$m[1]]));
        $turmasResp=$this->consultar('/v1/turmas',['ativo'=>1]);
        $escolasResp=$id?null:$this->consultar('/v1/escolas');
        $consulta=['turma_ids'=>$f['turma_ids'],'rascunho_ids'=>$f['disciplina_ids']];if($id)$consulta['prova_id']=$id;
        $op=$this->consultar('/v1/provas/opcoes-disciplinas',$consulta,true);
        $falha=!$op?->successful() || !$turmasResp?->successful() || (!$id && !$escolasResp?->successful());
        $o=$op?->successful()?$op->json('data'):['opcoes'=>[],'selecionadas'=>[],'curriculo_versao'=>$f['curriculo_versao']];
        if ($acao==='adicionar' && !$falha) {
            $nova=(int)$r->input('nova_disciplina_id');$d=collect($o['opcoes'])->firstWhere('id',$nova);
            if (!$d || in_array($nova,$f['disciplina_ids'],true)) $mensagens->add('nova_disciplina_id','Selecione uma disciplina disponível que ainda não foi adicionada.');
            elseif(count($f['disciplina_ids'])>=200) $mensagens->add('nova_disciplina_id','O limite é de 200 disciplinas por prova.');
            else { $f['disciplina_ids'][]=$nova;$o['selecionadas'][]=['id'=>$nova,'nome'=>$d['nome'],'elegivel'=>true,'motivo'=>null]; }
        }
        // Em falha, os IDs continuam visíveis e podem ser removidos; nunca são descartados.
        if ($falha) $o['selecionadas']=array_map(fn($i)=>['id'=>$i,'nome'=>collect($p['curriculo']['disciplinas']??[])->firstWhere('id',$i)['nome']??'Disciplina selecionada','elegivel'=>false,'motivo'=>'Não foi possível conferir a disponibilidade. Atualize as opções.'],$f['disciplina_ids']);
        $coerente=!$id || (int)($p['curriculo']['versao']??0)===(int)$o['curriculo_versao'];
        $conflito=$id && ($forcarConflito || !$coerente || (int)$f['curriculo_versao']!==(int)$o['curriculo_versao']);
        if ($acao==='rever' && !$falha && !$forcarConflito && $coerente) {
            $f['curriculo_versao']=$o['curriculo_versao'];$f['revisao_pendente']=1;$f['conferido']=null;$conflito=false;
        }
        $turmas=$turmasResp?->successful()?($turmasResp->json('data.turmas')??[]):[];
        $desconhecidas=array_diff($f['turma_ids'],array_column($turmas,'id'));
        $invalidas=collect($o['selecionadas'])->contains(fn($d)=>!$d['elegivel']) || (bool)$desconhecidas;
        $pronto=!$falha && !$conflito && !$invalidas && $f['turma_ids'] && $f['disciplina_ids'];
        $dados=compact('p','f','o','falha','conflito','turmas','desconhecidas','pronto','mensagens')+['escolas'=>$escolasResp?->json('data.escolas')??[]];
        if ($r->header('X-Curriculo-Fragmento')==='1') return response()->view('provas.curriculo._editor',$dados,$falha?503:$status)->header('Cache-Control','no-store');
        return response()->view('provas.curriculo.form',$dados,$status)->header('Cache-Control','no-store');
    }

    public function criar(Request $r)
    {
        $this->autorizar(null);
        $validacao=\Illuminate\Support\Facades\Validator::make($r->all(),['escola_id'=>'required|integer|min:1','titulo'=>'required|string|max:255','serie'=>'required|string|max:50',
            'formato_avaliacao'=>'required|in:gabarito','num_questoes'=>'required|integer|min:1|max:100','num_alternativas'=>'required|integer|in:3,4,5',
            'nota_maxima'=>'nullable|numeric|min:0.5|max:100','data_aplicacao'=>'nullable|date','turma_ids'=>'required|array|min:1|max:500','turma_ids.*'=>'integer|min:1|distinct',
            'disciplina_ids'=>'required|array|min:1|max:200','disciplina_ids.*'=>'integer|min:1|distinct'],$this->mensagensValidacao(),$this->atributosFormulario());
        if($validacao->fails())return $this->formulario($r,null,$validacao->errors()->toArray(),422);
        $d=$validacao->validated();
        foreach(['anular_se_todas','opcao_lingua','gerar_cartao_pdf'] as $c)$d[$c]=$r->boolean($c);
        $resp=$this->consultar('/v1/provas',$d,true);
        if (!$resp?->successful()) return $this->formulario($r,null,$resp?->json('errors')??['formulario'=>[$resp?->json('message')??'Não foi possível confirmar a criação. Confira a lista de provas antes de tentar novamente.']],$resp?->status()??503);
        return redirect()->route('provas.gabarito.edit',$resp->json('data.id'))->with('sucesso','Prova criada como rascunho. Preencha o gabarito oficial.');
    }

    public function salvar(Request $r,int $id)
    {
        $this->autorizar($id);$p=$this->prova($id);
        abort_unless($p['organizacao_curricular']['editavel']??false,422,'Classificação preservada após a publicação ou aplicação.');
        $validacao=\Illuminate\Support\Facades\Validator::make($r->all(),['turma_ids'=>'required|array|min:1|max:500','turma_ids.*'=>'integer|min:1|distinct',
            'disciplina_ids'=>'required|array|min:1|max:200','disciplina_ids.*'=>'integer|min:1|distinct','curriculo_versao'=>'required|integer|min:0',
            'revisao_pendente'=>'nullable|in:1','conferido'=>'required_if:revisao_pendente,1|in:1'],$this->mensagensValidacao(),$this->atributosFormulario());
        if($validacao->fails())return $this->formulario($r,$id,$validacao->errors()->toArray(),422);
        $d=$validacao->validated();
        unset($d['revisao_pendente'],$d['conferido']);
        if ((int)$d['curriculo_versao']!==(int)($p['curriculo']['versao']??0)) return $this->formulario($r,$id,null,409,true);
        try { $resp=$this->api->put('/v1/provas/'.$id,$d); } catch(ConnectionException){$resp=null;}
        if (!$resp?->successful()) return $this->formulario($r,$id,$resp?->json('errors')??['formulario'=>[$resp?->json('message')??'Não foi possível confirmar a alteração. Confira o estado atual antes de tentar novamente.']],$resp?->status()??503,$resp?->status()===409);
        return redirect()->route('provas.show',$id)->with('sucesso','Turmas e disciplinas da prova atualizadas.');
    }

    private function mensagensValidacao(): array
    {
        return ['required'=>'Informe :attribute.','required_if'=>'Confirme a revisão do estado atual antes de salvar.',
            'integer'=>'Informe um número inteiro em :attribute.','numeric'=>'Informe um número válido em :attribute.',
            'array'=>'Confira a seleção de :attribute.','string'=>'Confira o texto de :attribute.','date'=>'Informe uma data válida.',
            'min'=>['array'=>'Selecione ao menos um item em :attribute.','numeric'=>'O campo :attribute deve ser no mínimo :min.'],
            'max'=>['array'=>'Selecione até :max itens em :attribute.','string'=>'Use até :max caracteres em :attribute.','numeric'=>'O campo :attribute deve ser no máximo :max.'],
            'in'=>'Escolha uma opção válida em :attribute.','distinct'=>'Não repita itens em :attribute.'];
    }

    private function atributosFormulario(): array
    {
        return ['escola_id'=>'escola','titulo'=>'título','serie'=>'série / ano','num_questoes'=>'número de questões',
            'num_alternativas'=>'alternativas por questão','nota_maxima'=>'nota máxima','data_aplicacao'=>'data de aplicação',
            'turma_ids'=>'turmas','turma_ids.*'=>'turmas','disciplina_ids'=>'disciplinas','disciplina_ids.*'=>'disciplinas',
            'formato_avaliacao'=>'formato da avaliação','conferido'=>'conferência do estado atual','curriculo_versao'=>'estado da prova'];
    }
}
