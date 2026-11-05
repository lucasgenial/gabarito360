<?php
namespace App\Http\Controllers;
use App\Services\ApiClient;
use App\Support\{Acesso,ContextoWeb,EnvioPedidoTitular};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;

final class PedidoTitularController extends Controller
{
    public const ESTADOS=['recebida'=>'Recebido','em_verificacao'=>'Em verificação','em_analise'=>'Em análise','aguardando_informacao'=>'Aguardando informação','concluida'=>'Concluído','cancelada'=>'Cancelado'];
    public const TIPOS=['acesso'=>'Acesso aos dados','correcao'=>'Correção','anonimizacao'=>'Anonimização','eliminacao'=>'Eliminação','informacao'=>'Informação'];
    public function __construct(private ApiClient $api,private EnvioPedidoTitular $envios) {}
    private function autorizar(bool $abrir=false): void
    {
        abort_unless(config('services.pedidos.interface_ativa')===true,404);
        abort_unless(Acesso::interno() && in_array(Acesso::perfil(),['desenvolvedor','suporte'],true)
            && Acesso::pode($abrir?'pedido.abrir':'pedido.atender'),403);
        if ($abrir) abort_unless(Acesso::perfil()==='desenvolvedor',403);
    }
    private function validar(array $dados,array $regras): array
    {
        $v=Validator::make($dados,$regras);
        abort_if($v->fails(),422,'Confira os campos obrigatórios e os limites do formulário.');
        return $v->validated(); // Sem flash de dados pessoais.
    }
    private function consultar(string $path,array $dados=[]): array
    {
        try { $r=$this->api->get('/v1/pedidos-titulares'.$path,$dados); }
        catch (\Illuminate\Http\Client\ConnectionException) { abort(503,'Não foi possível consultar os pedidos.'); }
        abort_unless($r->successful(),in_array($r->status(),[403,404,409,422,429],true)?$r->status():503,'Consulta indisponível.');
        abort_unless(is_array($r->json('data')),503);
        return $r->json('data');
    }
    public function index(Request $r)
    {
        $this->autorizar();$f=$this->validar($r->only('page'),['page'=>'sometimes|integer|min:1']);
        return view('pedidos.index',['lista'=>$this->consultar('',$f),'estados'=>self::ESTADOS,'tipos'=>self::TIPOS]);
    }
    public function create(Request $r)
    {
        $this->autorizar(true);
        $f=$this->validar($r->only('cliente_id','clientes_page','espacos_page','responsaveis_page'),[
            'cliente_id'=>'nullable|integer|min:1','clientes_page'=>'sometimes|integer|min:1','espacos_page'=>'sometimes|integer|min:1','responsaveis_page'=>'sometimes|integer|min:1']);
        return view('pedidos.create',['opcoes'=>$this->consultar('/opcoes',$f),'filtros'=>$f,'tipos'=>self::TIPOS,'envio'=>$this->envios->criar('abrir')]);
    }
    public function show(string $id)
    {
        $this->autorizar();$p=$this->consultar('/'.$id);
        $envios=[];foreach (['triagem','verificacoes','decisao','sujeito'] as $acao) $envios[$acao]=$this->envios->criar($acao,$id);
        return view('pedidos.show',['pedido'=>$p,'envios'=>$envios,'estados'=>self::ESTADOS,'tipos'=>self::TIPOS]);
    }
    public function store(Request $r) { return $this->enviar($r,'abrir',null); }
    public function alterar(Request $r,string $id,string $acao) { return $this->enviar($r,$acao,$id); }
    private function enviar(Request $r,string $acao,?string $id)
    {
        $this->autorizar($acao==='abrir');
        abort_unless(in_array($acao,['abrir','triagem','verificacoes','decisao','sujeito'],true),404);
        if ($acao==='sujeito') abort_unless(config('services.pedidos.exportacoes'),404);
        $ticket=$this->validar($r->only('envio'),['envio'=>'required|string|size:48'])['envio'];
        $this->validar($r->only('confirmado'),['confirmado'=>'required|accepted']);
        $d=null;
        if ($r->boolean('repetir')) abort_if(array_diff(array_keys($r->all()),['_ctx','_token','envio','confirmado','repetir']),422);
        if (!$r->boolean('repetir')) {
            $regras=match($acao) {
                'sujeito'=>['versao'=>'required|integer|min:1','aluno_id'=>'required|integer|min:1','evidencia_referencia'=>'required|string|min:5|max:500','motivo'=>'required|string|min:5|max:500'],
                'abrir'=>['cliente_id'=>'required|integer|min:1','espaco_id'=>'required|integer|min:1','responsavel_acesso_id'=>'required|integer|min:1',
                    'tipo'=>'required|in:'.implode(',',array_keys(self::TIPOS)),'identificacao_declarada'=>'required|string|max:200','relato'=>'required|string|max:2000','motivo'=>'required|string|min:5|max:500'],
                'triagem'=>['versao'=>'required|integer|min:1','situacao'=>'required|in:em_verificacao,aguardando_informacao,cancelada','motivo'=>'required|string|min:5|max:500','resultado'=>'nullable|string|max:40'],
                'verificacoes'=>['versao'=>'required|integer|min:1','tipo'=>'required|in:identidade,autoridade','metodo'=>'required|string|max:40',
                    'evidencia_referencia'=>'required|string|min:5|max:500','valida_ate'=>'required|date_format:Y-m-d\TH:i','motivo'=>'required|string|min:5|max:500'],
                'decisao'=>['versao'=>'required|integer|min:1','situacao'=>'required|in:em_analise,concluida','motivo'=>'required|string|min:5|max:500',
                    'resultado'=>'nullable|string|max:40','resposta_referencia'=>'nullable|string|min:5|max:500'],
            };
            abort_if(array_diff(array_keys($r->all()),array_merge(array_keys($regras),['_ctx','_token','envio','confirmado','repetir'])),422);
            $d=$this->validar($r->only(array_keys($regras)),$regras);
            foreach (['resultado','resposta_referencia'] as $campo) if (array_key_exists($campo,$d) && ($d[$campo]===null || $d[$campo]==='')) unset($d[$campo]);
            if ($acao==='verificacoes') $d['valida_ate']=Carbon::createFromFormat('Y-m-d\TH:i',$d['valida_ate'],'America/Sao_Paulo')->seconds(0)->utc()->toIso8601String();
        }
        $res=$this->envios->enviar($ticket,$acao,$id,$d);
        abort_if(in_array($res['status'],[401,403,404],true),$res['status']);
        if ($res['status']!==200) return response()->view('pedidos.envio',['status'=>$res['status'],'envio'=>$ticket,'acao'=>$acao,'id'=>$id],$res['status']);
        if ($acao!=='abrir') return redirect()->route('pedidos.show',['id'=>$id]);
        return view('pedidos.recibo',['recibo'=>$res['recibo']]);
    }
}
