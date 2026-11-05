<?php
namespace App\Http\Controllers;
use App\Services\ApiClient;
use App\Support\Acesso;
use Illuminate\Http\Request;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Str;

final class CatalogoAtendimentoController extends Controller
{
    public function __construct(private ApiClient $api){}
    private function modo(Request $r): string {return $r->routeIs('catalogo-atendimento.*')?'atendimento':($r->routeIs('catalogo-gestao.*')?'gestao':'minhas-solicitacoes');}
    private function autorizar(Request $r): string
    {
        abort_unless(config('catalogo.interface_ativa')===true,404);$m=$this->modo($r);
        abort_unless(match($m){'atendimento'=>Acesso::interno() && Acesso::pode('catalogo.solicitacao.analisar'),'gestao'=>!Acesso::interno() && !Acesso::pessoal() && Acesso::pode('catalogo.manter_escopo'),default=>Acesso::pessoal() && Acesso::pode('catalogo.solicitar')},403);
        return $m;
    }
    private function get(string $path,array $q=[]): array
    {
        try{$r=$this->api->get('/v1/catalogo/'.$path,$q);}catch(ConnectionException){abort(503,'Não foi possível consultar. Tente novamente.');}
        abort_unless($r->successful(),in_array($r->status(),[403,404,409,422],true)?$r->status():503,'Não foi possível consultar. Confira o contexto e tente novamente.');return $r->json('data');
    }
    public function index(Request $r)
    {
        $modo=$this->autorizar($r);$dados=$this->get($modo,array_filter($r->only(['page','situacao']),fn($v)=>$v!==null && $v!==''));
        return view('catalogo-atendimento.index',compact('modo','dados'));
    }
    public function novo(Request $r)
    {
        $modo=$this->autorizar($r);abort_if($modo==='gestao',403);$opcoes=$this->get($modo.'/opcoes');$cadastro=[];$chave=(string)Str::uuid();
        return view('catalogo-atendimento.formulario',compact('modo','opcoes','cadastro','chave')+['e'=>null]);
    }
    public function editar(Request $r,string $unidade)
    {
        $modo=$this->autorizar($r);abort_if($modo==='minhas-solicitacoes',403);$e=$this->get($modo.'/instituicoes/'.$unidade);$opcoes=$this->get($modo.'/opcoes');$cadastro=$e;
        if($cadastro['namespace']==='plataforma'){$cadastro['namespace']=null;$cadastro['codigo']=null;}
        return view('catalogo-atendimento.formulario',compact('modo','opcoes','cadastro','e')+['chave'=>null]);
    }
    public function show(Request $r,string $id)
    {
        $modo=$this->autorizar($r);abort_if($modo==='gestao',403);$pedido=$this->get($modo.'/'.$id);$opcoes=$this->get($modo.'/opcoes');
        return view('catalogo-atendimento.show',compact('modo','pedido','opcoes'));
    }
    public function municipios(Request $r)
    {
        $modo=$this->autorizar($r);return response()->json($this->get($modo.'/municipios',array_filter($r->only(['uf','busca','page','codigo_ibge']),fn($v)=>$v!==null && $v!=='')));
    }
    public function buscar(Request $r)
    {
        $modo=$this->autorizar($r);abort_unless($modo==='atendimento',403);
        $f=array_filter($r->only(['uf','tipo','busca','namespace','codigo','page']),fn($v)=>$v!==null && $v!=='');
        $v=$r->validate(['solicitacao'=>'nullable|uuid']);$pedido=!empty($v['solicitacao'])?$this->get('atendimento/'.$v['solicitacao']):null;
        $opcoes=$this->get('atendimento/opcoes');$dados=null;$status=200;$errors=session('errors',new \Illuminate\Support\ViewErrorBag());
        if($f) {
            try{$resposta=$this->api->get('/v1/catalogo/atendimento/catalogo',$f);}catch(ConnectionException){abort(503,'Não foi possível consultar. Tente novamente.');}
            if($resposta->status()===422) {
                $status=422;$errors=new \Illuminate\Support\ViewErrorBag();
                $errors->put('default',new \Illuminate\Support\MessageBag($resposta->json('errors')?:['busca'=>['Confira os filtros e tente novamente.']]));
            }else{
                abort_unless($resposta->successful(),in_array($resposta->status(),[403,404],true)?$resposta->status():503,'Não foi possível consultar. Tente novamente.');
                $dados=$resposta->json('data');
            }
        }
        return response()->view('catalogo-atendimento.buscar',compact('modo','f','pedido','opcoes','dados','errors'),$status);
    }
    public function autoridades(Request $r,string $unidade)
    {
        $modo=$this->autorizar($r);abort_unless($modo==='atendimento',403);$e=$this->get('atendimento/instituicoes/'.$unidade);
        $opcoes=$this->get('atendimento/opcoes',['unidade_id'=>$unidade]+array_filter($r->only('escola_busca'),fn($v)=>$v!==null && $v!==''));
        return view('catalogo-atendimento.autoridades',compact('modo','e','opcoes'));
    }
    public function gravar(Request $r,string $unidade) {return $this->enviar($r,null,$unidade);}
    public function enviar(Request $r,?string $id=null,?string $unidade=null)
    {
        $modo=$this->autorizar($r);$d=$r->except(['_token','_ctx']);
        $path=$modo;
        if($r->routeIs('catalogo-atendimento.autorizar')){abort_unless($modo==='atendimento',403);$path.='/autoridades';}
        elseif($id!==null)$path.='/'.$id.'/acoes';
        elseif($modo!=='minhas-solicitacoes')$path.='/instituicoes'.($unidade?'/'.$unidade:'');
        try{$resp=$this->api->post('/v1/catalogo/'.$path,$d);}catch(ConnectionException){return back()->withInput()->with('erro','Não foi possível confirmar. Atualize o acompanhamento antes de repetir a operação.');}
        abort_if(in_array($resp->status(),[403,404],true),$resp->status());
        if(!$resp->successful()) {
            if($resp->status()===422) {
                $erros=[];$campos=['nome','sigla','tipo','dependencia','uf','municipio_ibge','namespace','codigo','site','observacao'];
                foreach((array)$resp->json('errors',[]) as $campo=>$mensagens) {
                    $destino=$r->has('cadastro') && in_array($campo,$campos,true)?'cadastro.'.$campo:$campo;
                    if(!in_array($destino,array_merge(array_map(fn($c)=>'cadastro.'.$c,$campos),['motivo','conferencia','situacao','mensagem','escola_id','site']),true))$destino='formulario';
                    $erros[$destino]=is_array($mensagens)?implode(' ',array_filter($mensagens,'is_string')):(is_string($mensagens)?$mensagens:'Confira este campo.');
                }
                if(!$erros)$erros=['formulario'=>'Não foi possível registrar. Confira os dados e a confirmação antes de tentar novamente.'];
                return back()->withInput()->withErrors($erros)->with('erro','Revise os campos destacados para continuar.');
            }
            return back()->withInput()->with('erro',$resp->status()===409?'Os dados mudaram ou a intenção já foi utilizada. Atualize e confira antes de continuar.':'Serviço indisponível. Consulte o acompanhamento antes de repetir.');
        }
        if($modo==='minhas-solicitacoes')return redirect()->route('catalogo-pedidos.show',['id'=>$resp->json('data.id')])->with('sucesso','Solicitação atualizada. Acompanhe as respostas nesta página.');
        if($id)return redirect()->route('catalogo-atendimento.show',['id'=>$id])->with('sucesso','Atendimento registrado. O solicitante recebe o aviso no acompanhamento.');
        return back()->with('sucesso','Operação registrada com histórico.');
    }
}
