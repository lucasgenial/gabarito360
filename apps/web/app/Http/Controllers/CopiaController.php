<?php
namespace App\Http\Controllers;
use App\Services\ApiClient;
use App\Support\Acesso;
use Illuminate\Http\Request;

class CopiaController extends Controller
{
    public function __construct(private ApiClient $api) {}
    public function index(Request $r)
    {
        abort_unless(config('services.copias.interface_ativa')===true,404);
        abort_unless(session('auth_vinculos') && !Acesso::interno() && in_array(Acesso::perfil(),['admin_rede','dir_escolar','professor_autonomo'],true)
            && (Acesso::pode('copia.autorizar_origem') || Acesso::pode('copia.autorizar_destino')),403);
        $filtros=$r->validate(['lado'=>'sometimes|in:origem,destino','espaco_id'=>'nullable|integer|min:1','page'=>'sometimes|integer|min:1|max:100000']);
        $filtros['lado']??=Acesso::pode('copia.autorizar_origem')?'origem':'destino';
        try { $resp=$this->api->get('/v1/copias',$filtros); }
        catch (\Illuminate\Http\Client\ConnectionException) { abort(503,'Não foi possível atualizar o acompanhamento.'); }
        abort_unless($resp->successful(),in_array($resp->status(),[403,404,422],true)?$resp->status():503);
        return view('copias.index',['dados'=>$resp->json('data')]);
    }
}
