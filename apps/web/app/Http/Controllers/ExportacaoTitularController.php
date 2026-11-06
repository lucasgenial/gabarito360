<?php
namespace App\Http\Controllers;
use App\Services\ApiClient;
use App\Support\{Acesso,ContextoWeb,EnvioPedidoTitular};
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

final class ExportacaoTitularController extends Controller
{
    public function __construct(private ApiClient $api,private EnvioPedidoTitular $envios) {}
    private function autorizar(?bool $gestor=null): bool
    {
        abort_unless(config('services.pedidos.exportacoes') && config('services.pedidos.interface_ativa'),404);
        $aprova=!Acesso::interno() && ContextoWeb::id() && in_array(Acesso::perfil(),['admin_rede','professor_autonomo'],true) && Acesso::pode('pedido.exportacao.autorizar');
        $atende=Acesso::interno() && in_array(Acesso::perfil(),['desenvolvedor','suporte'],true) && Acesso::pode('pedido.atender');
        abort_unless($gestor===null?($aprova||$atende):($gestor?$aprova:$atende),403);
        return (bool)$aprova;
    }
    private function validar(array $d,array $regras): array
    {
        $v=Validator::make($d,$regras);abort_if($v->fails(),422,'Confira as referências e os campos do formulário.');return $v->validated();
    }
    private function consultar(string $path): array
    {
        try {$r=$this->api->get($path);}catch(\Illuminate\Http\Client\ConnectionException){abort(503,'Consulta indisponível.');}
        abort_unless($r->successful(),in_array($r->status(),[401,403,404,409,422,429],true)?$r->status():503,'Consulta indisponível.');
        abort_unless(is_array($r->json('data')),503);return $r->json('data');
    }
    public function index(Request $r)
    {
        $gestor=$this->autorizar();$d=$this->validar($r->only('referencia'),['referencia'=>'nullable|uuid']);$ref=$d['referencia']??null;
        $previa=$ref?$this->consultar($gestor?'/v1/autorizacoes-exportacao/pedidos/'.$ref:'/v1/pedidos-titulares/exportacoes/'.$ref):null;
        $envios=[];foreach ($gestor?['conceder','revogar']:['gerar'] as $acao) $envios[$acao]=$this->envios->criar($acao,$acao==='conceder'?$ref:null);
        return view('pedidos.exportacoes',compact('gestor','ref','previa','envios'));
    }
    public function enviar(Request $r,string $acao)
    {
        abort_unless(in_array($acao,['conceder','revogar','gerar'],true),404);$this->autorizar($acao!=='gerar');
        $base=$this->validar($r->only('envio','referencia','confirmado'),['envio'=>'required|string|size:48','referencia'=>'required|uuid','confirmado'=>'required|accepted']);
        $d=null;
        if ($r->boolean('repetir')) abort_if(array_diff(array_keys($r->all()),['_token','_revisao','envio','referencia','confirmado','repetir']),422);
        else {
            $regras=['versao'=>'required|integer|min:1'];
            if ($acao!=='gerar') $regras+=['motivo'=>'required|string|min:5|max:500'];
            if ($acao==='conceder') $regras+=['aluno_id'=>'required|integer|min:1','sujeito_versao'=>'required|integer|min:1',
                'destinatario_acesso_id'=>'required|integer|min:1','campos'=>'required|array|min:1|max:2','campos.*'=>'required|distinct|in:nome,matricula',
                'valida_ate'=>'required|date_format:Y-m-d\TH:i','evidencia_referencia'=>'required|string|min:5|max:500'];
            abort_if(array_diff(array_keys($r->all()),array_merge(array_keys($regras),['_token','_revisao','envio','referencia','confirmado','repetir'])),422);
            $d=$this->validar($r->only(array_values(array_filter(array_keys($regras),fn($k)=>!str_contains($k,'.')))),$regras);
            if ($acao==='conceder') {$d['valida_ate']=Carbon::createFromFormat('Y-m-d\TH:i',$d['valida_ate'],'America/Sao_Paulo')->seconds(0)->utc()->toIso8601String();sort($d['campos']);}
            // Referência é parte do payload idempotente para tickets de alvo informado pelo usuário.
            if ($acao!=='conceder') $d['referencia_alvo']=$base['referencia'];
        }
        $res=$this->envios->enviar($base['envio'],$acao,$acao==='conceder'?$base['referencia']:null,$d);
        abort_if(in_array($res['status'],[401,403,404],true),$res['status']);
        return response()->view('pedidos.exportacao-recibo',['resultado'=>$res,'acao'=>$acao,'referencia'=>$base['referencia'],'envio'=>$base['envio']],$res['status']);
    }
    public function arquivo(Request $r,string $id)
    {
        $this->autorizar(false);abort_unless($r->isMethod('GET') || $r->isMethod('POST'),405);
        try {$res=$this->api->get('/v1/pedidos-titulares/exportacoes/'.$id.'/arquivo');}catch(\Illuminate\Http\Client\ConnectionException){abort(503,'Arquivo indisponível.');}
        abort_unless($res->successful(),in_array($res->status(),[401,403,404,409,429],true)?$res->status():503,'Arquivo indisponível.');
        abort_unless(strlen($res->body())<=16384 && str_contains($res->header('Content-Type'),'application/json'),503);
        return response($res->body(),200,['Content-Type'=>'application/json; charset=UTF-8','Content-Disposition'=>'attachment; filename="exportacao-'.$id.'.json"',
            'Cache-Control'=>'private, no-store','Pragma'=>'no-cache','X-Content-Type-Options'=>'nosniff']);
    }
}
