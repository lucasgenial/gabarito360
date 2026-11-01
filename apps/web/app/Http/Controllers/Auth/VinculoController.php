<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\ApiClient;
use App\Support\ContextoWeb;
use Illuminate\Http\Request;

class VinculoController extends Controller
{
    public function __construct(private ApiClient $api) {}

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
        $resp = $this->api->identidade()->post('/v1/auth/vinculos/selecionar', $data);
        if ($resp->failed()) return redirect()->route('vinculos.index', ['trocar' => 1])->with('erro', 'Este vínculo não está disponível. Escolha novamente.');
        $dados = $resp->json('data');
        abort_unless(is_array($dados) && !empty($dados['token']) && !empty($dados['perfil']) && is_array($dados['permissoes'] ?? null), 503);
        $id = ContextoWeb::criar($dados);
        return redirect()->route('painel', ['_ctx' => $id]);
    }

    public function interno(Request $request)
    {
        abort_unless(session('auth_vinculos') && session('auth_internos'), 404);
        $data = $request->validate(['acesso_interno_id' => 'required|integer|min:1']);
        $resp = $this->api->identidade()->post('/v1/auth/acessos-internos/selecionar', $data);
        if ($resp->failed()) return redirect()->route('vinculos.index', ['trocar' => 1])->with('erro', 'Este acesso interno não está disponível. Escolha novamente.');
        $dados = $resp->json('data');
        abort_unless(is_array($dados) && ($dados['tipo_contexto'] ?? null) === 'interno'
            && !empty($dados['token']) && !empty($dados['perfil']) && is_array($dados['permissoes'] ?? null), 503);
        return redirect()->route('painel', ['_ctx' => ContextoWeb::criar($dados)]);
    }
}
