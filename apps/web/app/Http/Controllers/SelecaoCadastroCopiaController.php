<?php
namespace App\Http\Controllers;
use App\Services\ApiClient;
use App\Support\Acesso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SelecaoCadastroCopiaController extends Controller
{
    public function __construct(private ApiClient $api) {}
    private function autorizar(): void
    {
        abort_unless(config('services.copias.interface_ativa')===true,404);
        abort_unless(session('auth_vinculos') && !Acesso::interno() && in_array(Acesso::perfil(),['admin_rede','dir_escolar','professor_autonomo'],true)
            && Acesso::pode('copia.autorizar_origem'),403);
    }
    private function dados(Request $r,array $regras): array
    {
        $campos=array_unique(array_map(fn($k)=>explode('.',$k)[0],array_keys($regras)));
        $v=Validator::make($r->only($campos),$regras);
        abort_if($v->fails(),422,'Confira a turma, os alunos e o tipo de destino da simulação.'); return $v->validated();
    }
    private function consultar(string $metodo,string $rota,array $d): array
    {
        try { $r=$this->api->$metodo('/v1/copias/cadastros'.$rota,$d); }
        catch (\Illuminate\Http\Client\ConnectionException) { abort(503,'Não foi possível consultar o cadastro.'); }
        abort_unless($r->successful(),in_array($r->status(),[403,404,409,422,429],true)?$r->status():503,'Confira a seleção e as dependências no seu espaço.');
        return $r->json('data');
    }
    public function index(Request $r)
    {
        $this->autorizar(); $d=$this->dados($r,['espaco_id'=>'nullable|integer|min:1','turma_id'=>'nullable|integer|min:1',
            'page'=>'sometimes|integer|min:1|max:100000','alunos_page'=>'sometimes|integer|min:1|max:100000']);
        return view('copias.cadastros',['dados'=>$this->consultar('get','',$d)]);
    }
    public function previa(Request $r)
    {
        $this->autorizar(); $d=$this->dados($r,['espaco_id'=>'required|integer|min:1','tipo_destino'=>'required|in:pessoal,escola',
            'turmas'=>'required|array|list|size:1','turmas.*'=>'required|integer|min:1|distinct',
            'alunos'=>'sometimes|array|list|max:20','alunos.*'=>'required|integer|min:1|distinct']);
        return view('copias.previa-cadastro',['dados'=>$this->consultar('post','/previa',$d)]);
    }
}
