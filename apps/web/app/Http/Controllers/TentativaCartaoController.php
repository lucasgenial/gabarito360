<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\Request;

class TentativaCartaoController extends Controller
{
    public function __construct(private ApiClient $api) {}

    public function index(Request $r, int $id)
    {
        $resp = $this->api->get('/v1/provas/'.$id.'/tentativas', $r->only('aluno_id', 'page'));
        abort_if($resp->failed(), $resp->status(), $resp->json('message') ?? 'Histórico indisponível.');

        return view('correcao.tentativas', ['dados' => $resp->json('data')]);
    }

    public function show(string $id)
    {
        $resp = $this->api->get('/v1/tentativas/'.$id);
        abort_if($resp->failed(), $resp->status(), $resp->json('message') ?? 'Tentativa indisponível.');

        return view('correcao.tentativa', ['dados' => $resp->json('data')]);
    }

    public function imagem(string $id)
    {
        $resp = $this->api->getArquivo('/v1/tentativas/'.$id.'/imagem');
        abort_if($resp->failed(), $resp->status(), 'Foto indisponível.');
        abort_unless(str_starts_with((string) $resp->header('Content-Type'), 'image/'), 502);

        return response($resp->body(), 200, ['Content-Type' => $resp->header('Content-Type'), 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function aplicar(Request $r, string $id)
    {
        $d = $r->validate(['motivo' => 'required|string|min:10|max:500', 'confirmar' => 'required|accepted']);
        $resp = $this->api->post('/v1/tentativas/'.$id.'/aplicar', $d);
        if ($resp->failed()) {
            return back()->withInput()->withErrors(['tentativa' => $resp->json('message') ?? 'Não foi possível aplicar. A correção atual foi mantida.']);
        }

        return redirect()->route('correcao.tentativa', $id)->with('sucesso', 'Nova tentativa aplicada. A correção anterior permanece no histórico.');
    }

    public function descartar(string $id)
    {
        $resp = $this->api->post('/v1/tentativas/'.$id.'/descartar');
        if ($resp->failed()) {
            return back()->withErrors(['tentativa' => $resp->json('message') ?? 'Não foi possível descartar.']);
        }

        return redirect()->route('correcao.tentativa',$id)->with('sucesso','Tentativa descartada. A correção vigente foi mantida.');
    }
}
