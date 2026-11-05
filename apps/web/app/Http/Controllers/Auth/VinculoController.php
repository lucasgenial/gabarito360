<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\ApiClient;
use App\Support\ContextoWeb;
use Illuminate\Http\Request;
use Illuminate\Http\Client\ConnectionException;

class VinculoController extends Controller
{
    public function __construct(private ApiClient $api) {}

    private function falhaTroca(Request $request,bool $interno,int $status=503)
    {
        $incerto=$status>=500;
        $mensagem=$incerto ? 'Não foi possível confirmar a troca. Atualize a página antes de tentar novamente.'
            : ($interno ? 'Este acesso não está disponível. Atualize a página para conferir seus acessos.' : 'Este vínculo não está disponível. Atualize a página para conferir seus acessos.');
        if($request->expectsJson())return response()->json(['message'=>$mensagem],$incerto?503:409);
        return redirect()->route('vinculos.index',['trocar'=>1])->with('erro',$mensagem);
    }

    public function index(Request $request)
    {
        if (!session('auth_vinculos')) return redirect()->route('painel');
        $resp = $this->api->identidade()->get('/v1/auth/vinculos');
        if ($resp->status() === 401) {
            $request->session()->invalidate(); $request->session()->regenerateToken();
            return redirect()->route('login')->with('erro', 'Entre novamente para continuar.');
        }
        abort_unless($resp->successful(), 503, 'Não foi possível carregar seus vínculos. Tente novamente.');
        $internos = [];
        if (session('auth_internos')) {
            $resposta = $this->api->identidade()->get('/v1/auth/acessos-internos');
            // Desabilitar a área interna não impede escolher um vínculo escolar.
            abort_unless($resposta->successful() || $resposta->status() === 404, 503, 'Não foi possível carregar seus acessos. Tente novamente.');
            $internos = $resposta->successful() ? ($resposta->json('data') ?? []) : [];
        }
        return view('auth.vinculo', ['vinculos' => $resp->json('data') ?? [], 'internos' => $internos,
            'automatico' => !$internos && !$request->boolean('trocar') && !session('erro')]);
    }

    public function store(Request $request)
    {
        abort_unless(session('auth_vinculos'), 404);
        $data = $request->validate(['vinculo_id' => 'required|integer|min:1']);
        try { $resp = $this->api->identidade()->post('/v1/auth/vinculos/selecionar', $data); }
        catch(ConnectionException $e){return $this->falhaTroca($request,false);}
        if ($resp->failed()) return $this->falhaTroca($request,false,$resp->status());
        $dados = $resp->json('data');
        abort_unless(is_array($dados) && !empty($dados['token']) && !empty($dados['perfil']) && is_array($dados['permissoes'] ?? null), 503,'Não foi possível confirmar a troca. Atualize a página antes de tentar novamente.');
        abort_unless((int)($dados['vinculo_id']??0)===(int)$data['vinculo_id'] && ($acesso=ContextoWeb::referencia($dados)),503,'Não foi possível confirmar seu vínculo.');
        ContextoWeb::criar($dados);
        if ($request->expectsJson()) return response()->json(['url'=>route('painel',['acesso'=>$acesso])]);
        return redirect()->route('painel', ['acesso' => $acesso]);
    }

    public function interno(Request $request)
    {
        abort_unless(session('auth_vinculos') && session('auth_internos'), 404);
        $data = $request->validate(['acesso_interno_id' => 'required|integer|min:1']);
        try { $resp = $this->api->identidade()->post('/v1/auth/acessos-internos/selecionar', $data); }
        catch(ConnectionException $e){return $this->falhaTroca($request,true);}
        if ($resp->failed()) return $this->falhaTroca($request,true,$resp->status());
        $dados = $resp->json('data');
        abort_unless(is_array($dados) && ($dados['tipo_contexto'] ?? null) === 'interno'
            && !empty($dados['token']) && !empty($dados['perfil']) && is_array($dados['permissoes'] ?? null), 503,'Não foi possível confirmar a troca. Atualize a página antes de tentar novamente.');
        abort_unless((int)($dados['acesso_interno_id']??0)===(int)$data['acesso_interno_id'] && ($acesso=ContextoWeb::referencia($dados)),503,'Não foi possível confirmar seu acesso.');
        ContextoWeb::criar($dados);
        if ($request->expectsJson()) return response()->json(['url'=>route('painel',['acesso'=>$acesso])]);
        return redirect()->route('painel', ['acesso' => $acesso]);
    }
}
