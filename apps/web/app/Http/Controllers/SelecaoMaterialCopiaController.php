<?php
namespace App\Http\Controllers;
use App\Services\ApiClient;
use App\Support\Acesso;
use Illuminate\Http\Request;

class SelecaoMaterialCopiaController extends Controller
{
    public function __construct(private ApiClient $api) {}
    private function autorizar(): void
    {
        abort_unless(config('services.copias.interface_ativa')===true,404);
        abort_unless(session('auth_vinculos') && !Acesso::interno() && in_array(Acesso::perfil(),['admin_rede','dir_escolar','professor_autonomo'],true)
            && Acesso::pode('copia.autorizar_origem'),403);
    }
    private function consultar(array $filtros): array
    {
        try { $r=$this->api->get('/v1/copias/materiais',$filtros); }
        catch (\Illuminate\Http\Client\ConnectionException) { abort(503,'Não foi possível carregar os materiais.'); }
        abort_unless($r->successful(),in_array($r->status(),[403,404,422],true)?$r->status():503);
        return $r->json('data');
    }
    public function index(Request $r)
    {
        $this->autorizar(); $f=$r->validate(['espaco_id'=>'nullable|integer|min:1','page'=>'sometimes|integer|min:1|max:100000']);
        return view('copias.materiais',['dados'=>$this->consultar($f),'erro'=>null]);
    }
    public function previa(Request $r)
    {
        $this->autorizar(); $d=$r->validate(['espaco_id'=>'required|integer|min:1','provas'=>'required|array|list|min:1|max:20','provas.*'=>'required|integer|min:1|distinct']);
        try { $resp=$this->api->post('/v1/copias/materiais/previa',$d); }
        catch (\Illuminate\Http\Client\ConnectionException) { abort(503,'Não foi possível preparar a prévia.'); }
        if ($resp->status()===422) return response()->view('copias.materiais',['dados'=>$this->consultar(['espaco_id'=>$d['espaco_id']]),
            'erro'=>'Não foi possível preparar a prévia. Confira a seleção e se os gabaritos estão completos.'],422);
        abort_unless($resp->successful(),in_array($resp->status(),[403,404],true)?$resp->status():503);
        return view('copias.previa-material',['dados'=>$resp->json('data')]);
    }
}
