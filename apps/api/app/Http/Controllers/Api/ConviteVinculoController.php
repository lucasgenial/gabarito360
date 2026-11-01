<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Mail\EmailModelo;
use App\Models\Usuario;
use App\Services\ConviteVinculo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ConviteVinculoController extends Controller
{
    public function opcoes(Request $r, ConviteVinculo $servico)
    {
        return ApiResponse::success($servico->opcoes($r->user()));
    }

    public function store(Request $request, ConviteVinculo $servico)
    {
        $d = $request->validate(['email' => 'required|email|max:255', 'espaco_id' => 'required|integer|min:1',
            'perfil' => 'required|in:admin_rede,dir_nucleo,dir_escolar,coordenador,professor,aplicador,aluno',
            'turma_ids' => 'sometimes|array', 'turma_ids.*' => 'integer|min:1|distinct']);
        $convite = $servico->emitir($request->user(), $d['email'], (int) $d['espaco_id'], $d['perfil'], $d['turma_ids'] ?? []);
        if ($convite) {
            $titular = Usuario::findOrFail($convite['usuario_id']);
            $dados = app(\App\Services\ContaUsuario::class)->dadosDoConvite($titular, $request->user(), $convite['vinculo_id']);
            // Fragmento não é enviado ao servidor web em GET nem deve ir para logs de URL.
            $link = rtrim(config('emails.url_web'), '/').'/vinculos/convite#'.http_build_query(['convite' => $convite['convite_id'], 'token' => $convite['token']]);
            Mail::to($titular->email)->queue(new EmailModelo('convite-vinculo', 'Convite para um novo vínculo', $dados + ['link' => $link]));
        }
        return ApiResponse::success(['mensagem' => 'Se a conta puder receber este vínculo, o convite será enviado.'], null, 202);
    }

    public function consultar(Request $request, int $id)
    {
        abort_unless($request->user()->ativo, 403);
        $d = $request->validate(['token' => 'required|string|size:64']);
        $c = \Illuminate\Support\Facades\DB::table('vinculo_convites as c')->join('usuario_vinculos as v', 'v.id', '=', 'c.vinculo_id')
            ->where('c.id', $id)->where('v.usuario_id', $request->user()->id)->where('v.situacao', 'convidado')
            ->whereNull('c.aceito_em')->whereNull('c.recusado_em')->where('c.expira_em', '>', now())->select('c.*')->first();
        abort_unless($c && hash_equals($c->token_hash, hash('sha256', $d['token'])), 422, 'Convite inválido ou expirado.');
        $dados = app(\App\Services\ContaUsuario::class)->dadosDoConvite($request->user(), Usuario::find($c->convidado_por), $c->vinculo_id);
        return ApiResponse::success($dados + ['expira_em' => $c->expira_em]);
    }

    public function responder(Request $request, int $id, ConviteVinculo $servico)
    {
        $d = $request->validate(['token' => 'required|string|size:64', 'aceitar' => 'required|boolean']);
        $servico->responder($request->user(), $id, $d['token'], (bool) $d['aceitar']);
        return ApiResponse::success(['mensagem' => $d['aceitar'] ? 'Vínculo aceito.' : 'Convite recusado.']);
    }
}
