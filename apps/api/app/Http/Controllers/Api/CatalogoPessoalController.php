<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\{CatalogoSelecao,ReferenciasInstituicoesPessoais};
use Illuminate\Http\Request;

class CatalogoPessoalController extends Controller
{
    private function autorizar(Request $r): void
    {
        $r->validate(['espaco_id'=>'prohibited','usuario_id'=>'prohibited','escola_id'=>'prohibited','cliente_id'=>'prohibited']);
        app(ReferenciasInstituicoesPessoais::class)->contexto($r->user());
    }
    public function ufs(Request $r,CatalogoSelecao $s) { $this->autorizar($r);return ApiResponse::success($s->ufs()); }
    public function municipios(Request $r,CatalogoSelecao $s) { $this->autorizar($r);return ApiResponse::success($s->municipios($r->query())); }
    public function buscar(Request $r,CatalogoSelecao $s) { $this->autorizar($r);return ApiResponse::success($s->buscar($r->query())); }
    public function show(Request $r,string $unidade,CatalogoSelecao $s) { $this->autorizar($r);return ApiResponse::success($s->publica($s->entrada($unidade))); }
    public function referencias(Request $r,ReferenciasInstituicoesPessoais $s) {
        $this->autorizar($r);$v=$r->validate(['page'=>'sometimes|integer|min:1|max:100000']);
        return ApiResponse::success($s->listar($r->user(),(int)($v['page']??1)));
    }
    public function selecionar(Request $r,ReferenciasInstituicoesPessoais $s) {
        $this->autorizar($r);$v=$r->validate(['unidade_id'=>'required|uuid','edicao_id'=>'required|uuid','confirmacao'=>'required|accepted']);
        return ApiResponse::success($s->selecionar($r->user(),$v['unidade_id'],$v['edicao_id']),'Instituição adicionada às suas referências pessoais.');
    }
    public function arquivar(Request $r,string $id,ReferenciasInstituicoesPessoais $s) {
        $this->autorizar($r);$v=$r->validate(['versao'=>'required|integer|min:1']);
        return ApiResponse::success($s->arquivar($r->user(),$id,(int)$v['versao']),'Referência arquivada; histórico preservado.');
    }
}
