<?php
namespace App\Http\Controllers;

use App\Services\ApiClient;
use App\Support\Acesso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class ConviteVinculoController extends Controller
{
    public function __construct(private ApiClient $api) {}

    public function create()
    {
        abort_unless(session('auth_vinculos') && Acesso::pode('usuario.gerir'), 403);
        $r = $this->api->get('/v1/usuarios/convites-vinculo/opcoes');
        abort_unless($r->successful(), $r->status() === 403 ? 403 : 503);
        return view('membros.convidar-vinculo', ['opcoes' => $r->json('data') ?? []]);
    }

    public function store(Request $r)
    {
        abort_unless(session('auth_vinculos') && Acesso::pode('usuario.gerir'), 403);
        $d = $r->validate(['email' => 'required|email|max:255', 'espaco_id' => 'required|integer|min:1',
            'perfil' => 'required|string', 'turma_ids' => 'sometimes|array', 'turma_ids.*' => 'integer|min:1|distinct']);
        $resp = $this->api->post('/v1/usuarios/convites-vinculo', $d);
        if ($resp->failed()) return back()->withInput($r->only('email', 'espaco_id', 'perfil', 'turma_ids'))->with('erro', $resp->json('message') ?? 'Não foi possível enviar o convite.');
        return redirect()->route('membros.index')->with('sucesso', 'Se a conta puder receber este vínculo, o convite será enviado.');
    }

    public function show(Request $r)
    {
        if ($r->hasAny(['token', 'codigo'])) return redirect()->route('vinculos.convite')
            ->with('erro', 'Cole o código recebido por e-mail no formulário.');
        return view('auth.convite-vinculo', ['convite' => null, 'referencia' => null]);
    }

    private function chave(string $id): string
    {
        return 'v2-convite:'.hash('sha256', session()->getId().'|'.session('auth_token')).':'.$id;
    }

    public function consultar(Request $r)
    {
        abort_if($r->query->has('token') || $r->query->has('codigo'), 422, 'Cole o código no formulário.');
        if ($r->has('codigo')) {
            $entrada = $r->validate(['codigo' => ['required', 'string', 'max:84', 'regex:/^[1-9][0-9]{0,18}\.[A-Za-z0-9]{64}$/D']]);
            [$numero, $token] = explode('.', $entrada['codigo'], 2);
            $d = \Illuminate\Support\Facades\Validator::make(['convite' => $numero, 'token' => $token], ['convite' => 'required|integer|min:1|max:'.PHP_INT_MAX, 'token' => 'required|alpha_num:ascii|size:64'])->validate();
        } else $d = $r->validate(['convite' => 'required|integer|min:1', 'token' => 'required|alpha_num:ascii|size:64']);
        $resp = $this->api->identidade()->post('/v1/auth/convites-vinculo/'.$d['convite'].'/consultar', ['token' => $d['token']]);
        if ($resp->failed()) {
            $mensagem = 'Convite indisponível. Confira a conta e o código ou peça um novo convite.';
            if ($r->expectsJson()) return response()->json(['message' => $mensagem], $resp->status() >= 500 ? 503 : 422);
            return redirect()->route('vinculos.convite')->with('erro', $mensagem);
        }
        $ref = Str::random(48);
        Cache::put($this->chave($ref), Crypt::encryptString(json_encode($d, JSON_THROW_ON_ERROR)), now()->addMinutes(20));
        if ($r->expectsJson()) return response()->json(['data' => $resp->json('data'), 'referencia' => $ref]);
        return view('auth.convite-vinculo', ['convite' => $resp->json('data'), 'referencia' => $ref]);
    }

    public function responder(Request $r)
    {
        abort_if($r->query->has('referencia') || $r->query->has('token') || $r->query->has('codigo'), 422, 'Use o formulário para confirmar o convite.');
        $d = $r->validate(['referencia' => 'required|alpha_num:ascii|size:48', 'aceitar' => 'required|boolean']);
        $cifra = Cache::get($this->chave($d['referencia']));
        if (!$cifra) return redirect()->route('vinculos.convite')->with('erro', 'A conferência expirou. Cole novamente o código do convite.');
        $c = json_decode(Crypt::decryptString($cifra), true, 512, JSON_THROW_ON_ERROR);
        $resp = $this->api->identidade()->post('/v1/auth/convites-vinculo/'.$c['convite'].'/responder', ['token' => $c['token'], 'aceitar' => (bool) $d['aceitar']]);
        if ($resp->failed()) return redirect()->route('vinculos.convite')->with('erro', 'Não foi possível confirmar. Confira novamente o código e a conta usada.');
        Cache::forget($this->chave($d['referencia']));
        return redirect()->route('vinculos.index', ['trocar' => 1])->with('sucesso', $d['aceitar'] ? 'Vínculo aceito. Escolha onde deseja entrar.' : 'Convite recusado. Seus outros vínculos foram preservados.');
    }
}
