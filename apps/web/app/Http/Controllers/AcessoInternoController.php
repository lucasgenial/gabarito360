<?php
namespace App\Http\Controllers;

use App\Services\ApiClient;
use App\Support\Acesso;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AcessoInternoController extends Controller
{
    public function __construct(private ApiClient $api) {}
    private function autorizar(): void { abort_unless(Acesso::interno() && Acesso::pode('acesso_interno.gerir'),403); }
    public function index(Request $r)
    {
        $this->autorizar();
        $resp = $this->api->get('/v1/acessos-internos',['page'=>max(1,(int)$r->query('page',1))]);
        abort_unless($resp->successful(),$resp->status()===403 ? 403 : 503);
        return view('internos.index',['lista'=>$resp->json('data')]);
    }
    private function dados(Request $r, bool $novo): array
    {
        $dados = $r->validate(['email'=>($novo ? 'required' : 'exclude').'|email|max:254',
            'perfil'=>$novo ? 'required|in:suporte,financeiro' : 'exclude',
            'situacao'=>$novo ? 'exclude' : 'required|in:ativo,suspenso',
            'versao_autorizacao'=>$novo ? 'exclude' : 'required|integer|min:1',
            'fim_em'=>'nullable|date_format:Y-m-d','motivo'=>'required|string|min:5|max:200']);
        $dados['fim_em'] = empty($dados['fim_em']) ? null : Carbon::createFromFormat('Y-m-d',$dados['fim_em'],'America/Sao_Paulo')->endOfDay()->utc()->toIso8601String();
        return $dados;
    }
    public function store(Request $r)
    {
        $this->autorizar();
        return $this->resultado($this->api->post('/v1/acessos-internos',$this->dados($r,true)));
    }
    public function update(Request $r, int $id)
    {
        $this->autorizar();
        return $this->resultado($this->api->put('/v1/acessos-internos/'.$id,$this->dados($r,false)));
    }
    private function resultado($resp)
    {
        if ($resp->failed()) return redirect()->route('internos.index')->with('erro',match($resp->status()) {
            409=>'O acesso mudou. Confira a versão atual e tente novamente.',
            403=>'Operação não permitida para este acesso ou esta conta.',
            422=>'Confira a conta, perfil, vigência e justificativa. A atribuição pode já existir.',
            default=>'Não foi possível concluir. Confira a lista antes de repetir.',
        });
        return redirect()->route('internos.index')->with('sucesso','Acesso salvo. A pessoa deve selecionar novamente seu acesso.');
    }
}
