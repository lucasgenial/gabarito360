<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\{PoliticaCatalogo,SolicitacoesCatalogo,ManutencaoCatalogo,CatalogoSelecao};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class SolicitacaoCatalogoController extends Controller
{
    private function interno(Request $r): bool { return str_starts_with($r->path(),'api/v1/catalogo/atendimento'); }
    public function index(Request $r,SolicitacoesCatalogo $s) {return ApiResponse::success($s->listar($r->user(),$r->query(),$this->interno($r)));}
    public function show(Request $r,string $id,SolicitacoesCatalogo $s) {return ApiResponse::success($s->detalhe($r->user(),$id,$this->interno($r)));}
    public function store(Request $r,SolicitacoesCatalogo $s) {return ApiResponse::success($s->criar($r->user(),$r->all()));}
    public function alterar(Request $r,string $id,SolicitacoesCatalogo $s) {return ApiResponse::success($s->alterar($r->user(),$id,$r->all(),$this->interno($r)));}
    private function autorizar(Request $r): void
    {
        $p=app(PoliticaCatalogo::class);
        if($this->interno($r))$p->interno($r->user());
        elseif(str_starts_with($r->path(),'api/v1/catalogo/gestao'))$p->gestor($r->user());else $p->pessoal($r->user());
    }
    public function opcoes(Request $r,CatalogoSelecao $s)
    {
        $this->autorizar($r);$d=['ufs'=>$s->ufs()['itens']];
        if($this->interno($r)) {
            $f=$r->validate(['escola_busca'=>'sometimes|string|min:3|max:100','unidade_id'=>'sometimes|uuid']);
            $q=DB::table('escolas')->select('id','nome','inep')->orderBy('nome');
            if(isset($f['escola_busca']))$q->where('nome','like','%'.str_replace(['%','_'],['\%','\_'],$f['escola_busca']).'%');
            $d['escolas']=$q->limit(50)->get()->all();
            $d['autoridades']=isset($f['unidade_id'])?DB::table('catalogo_gestao_autoridades as a')->join('escolas as e','e.id','=','a.escola_id')->where('a.unidade_id',strtolower($f['unidade_id']))->whereIn('a.escola_id',array_column($d['escolas'],'id'))->get(['a.escola_id','e.nome as escola_nome','a.unidade_id','a.situacao','a.versao'])->all():[];
        }
        return ApiResponse::success($d);
    }
    public function municipios(Request $r,CatalogoSelecao $s) {$this->autorizar($r);return ApiResponse::success($s->municipios($r->query()));}
    public function buscar(Request $r,CatalogoSelecao $s) {$this->autorizar($r);return ApiResponse::success($s->buscar($r->query()));}
    public function unidade(Request $r,string $unidade,CatalogoSelecao $s)
    {
        $this->autorizar($r);
        if(!$this->interno($r))app(PoliticaCatalogo::class)->gestor($r->user(),strtolower($unidade));
        return ApiResponse::success($s->publica($s->entrada($unidade)));
    }
    public function gravar(Request $r,ManutencaoCatalogo $s,?string $unidade=null)
    {
        $this->autorizar($r);
        $d=$r->validate(['cadastro'=>'required|array','motivo'=>'required|string|min:5|max:1000','conferencia'=>'required|accepted','situacao'=>'required|in:1,2,3','edicao_id'=>'nullable|uuid']);
        abort_unless(!array_diff(array_keys($r->all()),array_keys($d)),422,'Campos não permitidos.');
        return ApiResponse::success($s->gravar($r->user(),$d,$unidade,$d['edicao_id']??null,$this->interno($r)));
    }
    public function gestao(Request $r,ManutencaoCatalogo $s) {return ApiResponse::success(['itens'=>$s->unidadesGestao($r->user())]);}
    public function autoridade(Request $r,ManutencaoCatalogo $s) {return ApiResponse::success($s->autoridade($r->user(),$r->all()));}
}
