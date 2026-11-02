<?php
namespace App\Http\Controllers;
use App\Services\ApiClient;
use App\Support\{Acesso,ContextoWeb};
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class DecisaoCadastroCopiaController extends Controller
{
    public function __construct(private ApiClient $api) {}
    private function autorizar(string $lado): void
    {
        abort_unless(config('services.copias.interface_ativa')===true,404);
        abort_unless(in_array($lado,['origem','destino'],true) && session('auth_vinculos') && !Acesso::interno()
            && in_array(Acesso::perfil(),['admin_rede','dir_escolar','professor_autonomo'],true) && Acesso::pode('copia.autorizar_'.$lado),403);
    }
    private function dados(Request $r,array $regras): array
    {
        // Não usar validate()/withInput(): indicação nunca deve ir ao flash/old input.
        $campos=array_unique(array_map(fn($campo)=>explode('.',$campo)[0],array_keys($regras)));
        $v=Validator::make($r->only($campos),$regras);
        abort_if($v->fails(),422,'Confira os campos e as confirmações da solicitação.'); return $v->validated();
    }
    private function api(string $metodo,string $url,array $d=[]): array
    {
        try { $r=$this->api->$metodo($url,$d); }
        catch (\Illuminate\Http\Client\ConnectionException) { abort(503,'Não foi possível conferir a solicitação.'); }
        abort_unless($r->successful(),in_array($r->status(),[403,404,409,422,429],true)?$r->status():503,'Solicitação indisponível. Confira seu contexto e tente novamente.');
        return $r->json('data');
    }
    public function receber(Request $r)
    {
        $this->autorizar('destino'); $d=$this->dados($r,['espaco_id'=>'nullable|integer|min:1']);
        $dados=$this->api('get','/v1/copias',$d+['lado'=>'destino']);
        return view('copias.receber-cadastro',['dados'=>$dados,'recebimento'=>null]);
    }
    public function emitir(Request $r)
    {
        $this->autorizar('destino'); $d=$this->dados($r,['espaco_id'=>'required|integer|min:1']);
        $dados=$this->api('get','/v1/copias',$d+['lado'=>'destino']);
        $recebimento=$this->api('post','/v1/copias/cadastros/recebimentos',$d);
        return view('copias.receber-cadastro',compact('dados','recebimento'));
    }
    public function preparar(Request $r)
    {
        $this->autorizar('origem'); $d=$this->dados($r,['espaco_id'=>'required|integer|min:1','codigo'=>'required|string|size:64',
            'turmas'=>'required|array|list|size:1','turmas.*'=>'integer|min:1|distinct','alunos'=>'sometimes|array|list|max:20','alunos.*'=>'integer|min:1|distinct']);
        $dados=$this->api('post','/v1/copias/cadastros/preparar',$d);
        return view('copias.confirmar-cadastro',['dados'=>$dados,'codigo'=>$d['codigo'],'chave'=>(string)Str::uuid()]);
    }
    public function criar(Request $r)
    {
        $this->autorizar('origem'); $d=$this->dados($r,['espaco_id'=>'required|integer|min:1','codigo'=>'required|string|size:64',
            'turmas'=>'required|array|list|size:1','turmas.*'=>'integer|min:1|distinct','alunos'=>'sometimes|array|list|max:20','alunos.*'=>'integer|min:1|distinct','chave'=>'required|uuid',
            'destino_digest'=>'required|string|size:64','conteudo_digest'=>'required|string|size:64','confirmado'=>'required|accepted']);
        $dados=$this->api('post','/v1/copias/cadastros/lotes',$d);
        return redirect()->route('copias.cadastro.decisao',['id'=>$dados['lote_id'],'lado'=>'origem','_ctx'=>ContextoWeb::id()]);
    }
    public function abrir(Request $r)
    {
        $this->autorizar('destino'); $d=$this->dados($r,['lote_id'=>'required|uuid']);
        return redirect()->route('copias.cadastro.decisao',['id'=>$d['lote_id'],'lado'=>'destino','_ctx'=>ContextoWeb::id()]);
    }
    public function show(Request $r,string $id)
    {
        $d=$this->dados($r,['lado'=>'required|in:origem,destino']); $this->autorizar($d['lado']);
        $dados=$this->api('get','/v1/copias/cadastros/lotes/'.$id,$d);
        $ate=CarbonImmutable::now('UTC')->addHour();
        foreach (['saida_valida_ate','evidencia_valida_ate'] as $campo) if ($dados[$campo]??null) $ate=$ate->min(CarbonImmutable::parse($dados[$campo],'UTC'));
        return view('copias.decisao-cadastro',['dados'=>$dados,'validade'=>$ate->format('Y-m-d\TH:i:s\Z')]);
    }
    public function evidencia(Request $r,string $id)
    {
        $this->autorizar('origem'); $d=$this->dados($r,['conteudo_digest'=>'required|string|size:64','documento'=>'required|string|max:65536','confirmado'=>'required|accepted','valida_ate'=>'required|date_format:Y-m-d\TH:i:s\Z']);
        $this->api('post','/v1/copias/cadastros/lotes/'.$id.'/evidencia',$d);
        return redirect()->route('copias.cadastro.decisao',['id'=>$id,'lado'=>'origem','_ctx'=>ContextoWeb::id()]);
    }
    public function aprovar(Request $r,string $id)
    {
        $d=$this->dados($r,['lado'=>'required|in:origem,destino','conteudo_digest'=>'required|string|size:64',
            'valida_ate'=>'required|date_format:Y-m-d\TH:i:s\Z','confirmado'=>'required|accepted','conciliacao_digest'=>'nullable|string|size:64']); $this->autorizar($d['lado']);
        $this->api('post','/v1/copias/cadastros/lotes/'.$id.'/aprovar',$d);
        return redirect()->route('copias.cadastro.decisao',['id'=>$id,'lado'=>$d['lado'],'_ctx'=>ContextoWeb::id()]);
    }
}
