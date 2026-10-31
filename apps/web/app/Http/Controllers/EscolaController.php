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

        return view('escolas.index', [
            'escolas' => $escolas,
            'kpis'    => $kpis,
            'busca'   => $request->query('busca', ''),
        ]);
    }

    /** Oferta da escola (mesmas chaves de App\Models\Escola na API). */
    private const OFERTA = [
        'turnos'      => ['manha' => 'Manhã', 'tarde' => 'Tarde', 'noite' => 'Noite', 'integral' => 'Integral'],
        'etapas'      => ['infantil' => 'Educação Infantil', 'fundamental_iniciais' => 'Fundamental — anos iniciais',
                          'fundamental_finais' => 'Fundamental — anos finais', 'medio' => 'Ensino Médio',
                          'eja' => 'EJA', 'profissional' => 'Educação Profissional'],
        'modalidades' => ['regular' => 'Ensino regular', 'especial' => 'Educação especial',
                          'eja' => 'Educação de Jovens e Adultos', 'profissional' => 'Educação profissional',
                          'campo' => 'Educação do campo', 'indigena' => 'Educação indígena', 'quilombola' => 'Educação quilombola'],
    ];

    private const CAMPOS = ['nucleo_id', 'nome', 'inep', 'tipo_rede', 'tipo', 'escola_sede_id', 'situacao_funcionamento',
        'localizacao', 'orgao_regional', 'logradouro', 'numero', 'complemento', 'bairro', 'cep', 'cidade', 'co_municipio',
        'uf', 'latitude', 'longitude', 'telefone', 'email'];

    /** Núcleos e escolas sede que o usuário pode usar no cadastro (só para quem cadastra escolas). */
    private function opcoesCadastro(): array
    {
        if (!\App\Support\Acesso::pode('escola.criar')) {
            return ['nucleos' => [], 'sedes' => []];
        }
        $sedes = collect($this->api->get('/v1/escolas')->json('data.escolas') ?? [])
            ->filter(fn ($e) => ($e['tipo'] ?? 'sede') === 'sede')
            ->map(fn ($e) => ['id' => $e['id'], 'nome' => $e['nome'], 'inep' => $e['inep'], 'nucleo_id' => $e['nucleo_id']])
            ->values()->all();

        return ['nucleos' => $this->api->get('/v1/nucleos')->json('data.nucleos') ?? [], 'sedes' => $sedes];
    }

    public function create(): View
    {
        abort_unless(\App\Support\Acesso::pode('escola.criar'), 403, 'Só o diretor de núcleo e o administrador cadastram escolas.');

        return view('escolas.form', ['escola' => null, 'opcoes' => self::OFERTA] + $this->opcoesCadastro());
    }

    public function edit(int $id): View
    {
        $resp = $this->api->get("/v1/escolas/{$id}");
        if ($resp->failed()) {
            abort(404, 'Escola não encontrada.');
        }

        return view('escolas.form', ['escola' => $resp->json('data'), 'opcoes' => self::OFERTA] + $this->opcoesCadastro());
    }

    /** Campos do formulário para a API: vazios viram null, CEP só com dígitos, listas da oferta. */
    private function dados(Request $request): array
    {
        $d = collect($request->only(self::CAMPOS))->map(fn ($v) => is_string($v) && trim($v) === '' ? null : (is_string($v) ? trim($v) : $v))->all();
        if (!empty($d['cep'])) {
            $d['cep'] = preg_replace('/\D/', '', $d['cep']);
        }
        if (!empty($d['inep'])) {
            $d['inep'] = preg_replace('/\D/', '', $d['inep']);
        }
        if (!empty($d['uf'])) {
            $d['uf'] = strtoupper($d['uf']);
        }
        foreach (array_keys(self::OFERTA) as $lista) {
            $d[$lista] = array_values((array) $request->input($lista, []));
        }
        if (!\App\Support\Acesso::pode('escola.criar')) {
            unset($d['nucleo_id'], $d['inep'], $d['tipo'], $d['escola_sede_id']);
        }

        return $d;
    }

    /** Erro da API de volta ao formulário (mensagem geral + erros por campo). */
    private function voltarComErro($resp, string $padrao)
    {
        return back()->withInput()->withErrors($resp->json('errors') ?? [])
            ->with('erro', $resp->json('message') ?? $padrao);
    }

    public function store(Request $request)
    {
        $request->validate(['nome' => 'required|string|max:200', 'inep' => 'required|string', 'nucleo_id' => 'required|integer']);

        $resp = $this->api->post('/v1/escolas', $this->dados($request));
        if ($resp->failed()) {
            return $this->voltarComErro($resp, 'Não foi possível cadastrar a escola.');
        }

        return redirect()->route('escolas.show', $resp->json('data.id'))->with('sucesso', 'Escola cadastrada.');
    }

    public function show(int $id): View
    {
        $resp = $this->api->get("/v1/escolas/{$id}");

        if ($resp->failed()) {
            abort(404, 'Escola não encontrada.');
        }

        return view('escolas.show', ['escola' => $resp->json('data'), 'opcoes' => self::OFERTA]);
    }

    public function update(Request $request, int $id)
    {
        $request->validate(['nome' => 'required|string|max:200']);

        $resp = $this->api->put("/v1/escolas/{$id}", $this->dados($request));
        if ($resp->failed()) {
            return $this->voltarComErro($resp, 'Não foi possível atualizar a escola.');
        }

        return redirect()->route('escolas.show', $id)->with('sucesso', 'Dados da escola atualizados.');
    }

    /** Consulta ao Censo Escolar pelo INEP (preenche o formulário). */
    public function censoBuscar(Request $request)
    {
        $inep = preg_replace('/\D/', '', (string) $request->query('inep'));
        $resp = $this->api->get("/v1/censo/escolas/{$inep}");

        return response()->json($resp->json(), $resp->status());
    }

    /** Escolas do Censo pelo nome (para quem não sabe o INEP). */
    public function censoPesquisar(Request $request)
    {
        $resp = $this->api->get('/v1/censo/escolas', $request->only('busca', 'municipio'));

        return response()->json($resp->json(), $resp->status());
    }

    public function toggle(int $id)
    {
        $resp = $this->api->post("/v1/escolas/{$id}/toggle");

        if ($resp->failed()) {
            return back()->with('erro', 'Não foi possível alterar o status da escola.');
        }

        $status = $resp->json('data.ativo') ? 'ativada' : 'desativada';
        return back()->with('sucesso', "Escola {$status}.");
    }

    /** Logo da escola (para o cabeçalho dos relatórios impressos). */
    public function logo(int $id)
    {
        $resp = $this->api->getArquivo("/v1/escolas/{$id}/logo");
        if ($resp->failed() || !str_starts_with((string) $resp->header('Content-Type'), 'image/')) {
            abort(404);
        }

        return response($resp->body(), 200, ['Content-Type' => $resp->header('Content-Type'), 'Cache-Control' => 'private, max-age=3600']);
    }

    public function enviarLogo(Request $request, int $id)
    {
        $request->validate(['logo' => 'required|image|mimes:png,jpg,jpeg,webp|max:2048']);
        $resp = $this->api->postFile("/v1/escolas/{$id}/logo", 'logo', $request->file('logo'));

        return back()->with($resp->failed() ? 'erro' : 'sucesso', $resp->failed()
            ? ($resp->json('message') ?? 'Não foi possível enviar o logo.')
            : 'Logo atualizado. Ele aparece no cabeçalho dos relatórios impressos.');
    }

    public function logoRede(int $id)
    {
        $resp = $this->api->getArquivo("/v1/redes/{$id}/logo");
        if ($resp->failed() || !str_starts_with((string) $resp->header('Content-Type'), 'image/')) {
            abort(404);
        }

        return response($resp->body(), 200, ['Content-Type' => $resp->header('Content-Type'), 'Cache-Control' => 'private, max-age=3600']);
    }

    public function enviarLogoRede(Request $request, int $id)
    {
        $request->validate(['logo' => 'required|image|mimes:png,jpg,jpeg,webp|max:2048']);
        $resp = $this->api->postFile("/v1/redes/{$id}/logo", 'logo', $request->file('logo'));

        return back()->with($resp->failed() ? 'erro' : 'sucesso', $resp->failed()
            ? ($resp->json('message') ?? 'Não foi possível enviar o logo do ente.')
            : 'Logo do ente atualizado. Ele aparece à esquerda no cabeçalho dos relatórios.');
    }
}
