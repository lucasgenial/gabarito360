<?php
namespace App\Http\Controllers;

use App\Services\ApiClient;
use App\Support\Acesso;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    public const TIPOS = ['professor'=>'Professor','escola'=>'Escola','nucleo'=>'Núcleo','rede'=>'Rede','secretaria'=>'Secretaria','governo'=>'Governo'];
    public const MODALIDADES = ['pagante'=>'Pagante','presenteado'=>'Presenteado','teste'=>'Teste','interno'=>'Interno'];
    private const CAMPOS = ['tipo','nome','modalidade','documento','contato_nome','contato_email','contato_telefone','contrato_referencia'];

    public function __construct(private ApiClient $api) {}
    private function autorizar(string $permissao = 'cliente.ver'): void
    {
        abort_unless(Acesso::interno() && Acesso::pode($permissao),403);
    }
    private function conferir($resp): void
    {
        abort_unless($resp->successful(),in_array($resp->status(),[403,404,422],true) ? $resp->status() : 503);
    }
    public function index(Request $r)
    {
        $this->autorizar();
        $filtros=$r->validate(['busca'=>'nullable|string|max:100','tipo'=>'nullable|in:'.implode(',',array_keys(self::TIPOS)),
            'modalidade'=>'nullable|in:'.implode(',',array_keys(self::MODALIDADES)),'page'=>'sometimes|integer|min:1']);
        $resp=$this->api->get('/v1/clientes',$filtros);
        $this->conferir($resp);
        return view('clientes.index',['lista'=>$resp->json('data'),'filtros'=>$filtros,'tipos'=>self::TIPOS,'modalidades'=>self::MODALIDADES]);
    }
    public function create()
    {
        $this->autorizar('cliente.gerir');
        return view('clientes.form',['cliente'=>null,'espacos'=>null,'tipos'=>self::TIPOS,'modalidades'=>self::MODALIDADES]);
    }
    public function show(Request $r, int $id)
    {
        $this->autorizar();
        $r->validate(['page'=>'sometimes|integer|min:1']);
        $resp=$this->api->get('/v1/clientes/'.$id); $this->conferir($resp);
        $espacos=$this->api->get('/v1/clientes/'.$id.'/espacos',['page'=>$r->query('page',1)]); $this->conferir($espacos);
        return view('clientes.form',['cliente'=>$resp->json('data'),'espacos'=>$espacos->json('data'),'tipos'=>self::TIPOS,'modalidades'=>self::MODALIDADES]);
    }
    public function store(Request $r)
    {
        $this->autorizar('cliente.gerir');
        return $this->resultado($this->api->post('/v1/clientes',$r->only(self::CAMPOS)),$r);
    }
    public function update(Request $r, int $id)
    {
        $this->autorizar('cliente.gerir');
        return $this->resultado($this->api->put('/v1/clientes/'.$id,$r->only(self::CAMPOS)),$r);
    }
    private function resultado($resp, Request $r)
    {
        if ($resp->failed()) {
            // Não copiar respostas/PII do backend para logs ou mensagens de erro.
            abort_if(in_array($resp->status(),[403,404],true),$resp->status());
            return back()->withInput($r->only(self::CAMPOS))->with('erro',$resp->status()===422
                ? 'Confira os campos obrigatórios, o e-mail e os limites indicados.'
                : 'Não foi possível confirmar o cadastro. Confira a lista antes de repetir.');
        }
        return redirect()->route('clientes.show',$resp->json('data.id'))->with('sucesso','Cadastro do cliente salvo.');
    }
}
