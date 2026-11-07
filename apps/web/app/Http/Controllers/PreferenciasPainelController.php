<?php
namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\Request;
use Illuminate\Http\Client\ConnectionException;

class PreferenciasPainelController extends Controller
{
    public function __construct(private ApiClient $api) {}

    public function consultar(string $painel, ?string $turmaId=null)
    {
        return $this->encaminhar($painel,null,$turmaId);
    }

    public function salvar(Request $request, string $painel, ?string $turmaId=null)
    {
        return $this->encaminhar($painel,$request->only('versao','chave','acao','apresentacao','layouts','alvos'),$turmaId);
    }

    private function encaminhar(string $painel, ?array $dados=null, ?string $turmaId=null)
    {
        abort_unless(in_array($painel,['turmas','turma'],true) && \App\Support\Acesso::pode('turma.ver'),403);
        // O alvo vem dos parâmetros autenticados da referência UR4, nunca do payload livre.
        abort_unless($painel==='turma' ? is_string($turmaId)&&preg_match('/^[1-9][0-9]{0,17}$/D',$turmaId) : $turmaId===null,422);
        try {
            $cliente=$this->api->semRedirecionamentos()->noPainelDaTurma($turmaId===null?null:(int)$turmaId);
            $r=$dados===null?$cliente->get('/v1/paineis/preferencias/'.$painel):$cliente->put('/v1/paineis/preferencias/'.$painel,$dados);
            if($r->status()===401)return \App\Support\SessaoWeb::encerrar(request());
            if($r->status()===429)return response()->json(['message'=>'Aguarde antes de tentar novamente.'],429)->header('Retry-After',(string)max(1,min(120,(int)$r->header('Retry-After')?:60)))->header('Cache-Control','private, no-store');
            if($r->successful()) {
                $d=$r->json('data');
                if(!\App\Support\ContratoPreferenciasPainel::valido($d))return response()->json(['message'=>'Não foi possível confirmar a resposta de preferências.'],502)->header('Cache-Control','private, no-store');
                return response()->json(['data'=>\App\Support\ContratoPreferenciasPainel::projetar($d)])->header('Cache-Control','private, no-store');
            }
            $status=$r->status()>=400 && $r->status()<=599?$r->status():502;
            return response()->json(['message'=>$status===409?'A organização mudou. Recarregue antes de salvar novamente.':'Não foi possível confirmar suas preferências.'],$status)->header('Cache-Control','private, no-store');
        } catch(ConnectionException) {
            return response()->json(['message'=>'Não foi possível consultar ou confirmar suas preferências.'],503)->header('Cache-Control','private, no-store');
        }
    }
}
