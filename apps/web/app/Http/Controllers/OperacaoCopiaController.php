<?php
namespace App\Http\Controllers;
use App\Services\ApiClient;
use App\Support\{Acesso,ContextoWeb};
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class OperacaoCopiaController extends Controller
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
    public function show(Request $r,string $id)
    {
        $d=$this->dados($r,['lado'=>'required|in:origem,destino']); $this->autorizar($d['lado']);
        return view('copias.operacao',['dados'=>$this->api('get','/v1/copias/operacao/'.$id,$d)]);
    }
    public function solicitar(Request $r,string $id)
    {
        $this->autorizar('destino'); $d=$this->dados($r,['confirmado'=>'required|accepted']);
        $this->api('post','/v1/copias/operacao/'.$id.'/solicitar',$d);
        return redirect()->route('copias.operacao',['id'=>$id,'lado'=>'destino']);
    }
    public function revogar(Request $r,string $id)
    {
        $d=$this->dados($r,['lado'=>'required|in:origem,destino','confirmado'=>'required|accepted']); $this->autorizar($d['lado']);
        $this->api('post','/v1/copias/operacao/'.$id.'/revogar',$d);
        return redirect()->route('copias.operacao',['id'=>$id,'lado'=>$d['lado']]);
    }
    public function acervo(Request $r,string $id)
    {
        $this->autorizar('destino');
        return view('copias.acervo',['dados'=>$this->api('get','/v1/copias/operacao/'.$id.'/acervo')]);
    }
}