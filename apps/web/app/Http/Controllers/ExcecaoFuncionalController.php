<?php
namespace App\Http\Controllers;
use App\Services\ApiClient;
use Illuminate\Http\Request;
class ExcecaoFuncionalController extends Controller {
 public function __construct(private ApiClient $api){}
 private function autorizar():void {abort_unless(\App\Support\Acesso::interno() && \App\Support\Acesso::pode('usuario.excecao_funcional.gerir'),403);}
 private function pagina(array $dados=[],?string $mensagem=null,?string $erro=null){$r=$this->api->get('/v1/configuracoes/funcoes/opcoes');abort_unless($r->successful(),$r->status()===403?403:503);return view('funcoes.index',['escolas'=>$r->json('data')??[],'dados'=>$dados,'mensagem'=>$mensagem,'erro'=>$erro]);}
 public function index(){ $this->autorizar();return $this->pagina();}
 public function consultar(Request $r){$this->autorizar();$d=$r->validate(['email'=>'required|email','espaco_id'=>'required|integer']);$resp=$this->api->post('/v1/configuracoes/funcoes/consultar',$d);return $resp->successful()?$this->pagina($resp->json('data')):$this->pagina(erro:$resp->status()===404?'Não foi encontrado um usuário com vínculo nesta instituição. Confira os dados.':($resp->json('message')??'Não foi possível consultar.'));}
 public function salvar(Request $r){$this->autorizar();$d=$r->validate(['usuario_id'=>'required|integer','espaco_id'=>'required|integer','permitir'=>'required|boolean','versao'=>'required|integer|min:0','chamado'=>'required|string|min:3|max:80','motivo'=>'required|string|min:10|max:500']);$resp=$this->api->post('/v1/configuracoes/funcoes',$d);return $resp->successful()?$this->pagina($resp->json('data'),'Configuração registrada. Os vínculos e suas permissões permanecem separados.'):$this->pagina(erro:$resp->json('message')??'Não foi possível confirmar a configuração. Consulte novamente.');}
}
