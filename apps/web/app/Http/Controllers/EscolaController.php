<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EscolaController extends Controller
{
    public function __construct(private ApiClient $api) {}

    public function index(Request $request): View
    {
        $resp = $this->api->get('/v1/escolas', $request->only('busca', 'ativo'));

        $escolas = $resp->successful() ? $resp->json('data.escolas') : [];
        $kpis    = $resp->successful() ? $resp->json('data.kpis')    : [];

        $nucleos = $this->api->get('/v1/nucleos')->json('data.nucleos') ?? [];

        return view('escolas.index', [
            'escolas' => $escolas,
            'kpis'    => $kpis,
            'nucleos' => $nucleos,
            'busca'   => $request->query('busca', ''),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nucleo_id' => 'required|integer',
            'nome'      => 'required|string|max:200',
            'inep'      => 'required|string|size:8',
            'tipo_rede' => 'required|string',
        ]);

        $resp = $this->api->post('/v1/escolas', $request->only(
            'nucleo_id','nome','inep','tipo_rede','logradouro','cidade','uf','telefone','email'
        ));

        if ($resp->failed()) {
            return back()->withInput()
                ->with('erro', $resp->json('message') ?? 'Erro ao criar escola.');
        }

        return redirect()->route('escolas.index')->with('sucesso', 'Escola criada com sucesso.');
    }

    public function show(int $id): View
    {
        $resp = $this->api->get("/v1/escolas/{$id}");

        if ($resp->failed()) {
            abort(404, 'Escola não encontrada.');
        }

        $nucleos = $this->api->get('/v1/nucleos')->json('data.nucleos') ?? [];

        return view('escolas.show', [
            'escola'  => $resp->json('data'),
            'nucleos' => $nucleos,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $request->validate([
            'nome'      => 'required|string|max:200',
            'tipo_rede' => 'required|string',
        ]);

        $resp = $this->api->put("/v1/escolas/{$id}", $request->only(
            'nucleo_id','nome','inep','tipo_rede','logradouro','cidade','uf','telefone','email'
        ));

        if ($resp->failed()) {
            return back()->withInput()
                ->with('erro', $resp->json('message') ?? 'Erro ao atualizar escola.');
        }

        return redirect()->route('escolas.show', $id)->with('sucesso', 'Escola atualizada.');
    }

    public function toggle(int $id)
    {
        $resp = $this->api->post("/v1/escolas/{$id}/toggle");

        if ($resp->failed()) {
            return back()->with('erro', 'Não foi possível alterar o status da escola.');
        }

        $status = $resp->json('data.ativo') ? 'ativada' : 'desativada';
        return back()->with('sucesso', "Escola {$status} com sucesso.");
    }
}
