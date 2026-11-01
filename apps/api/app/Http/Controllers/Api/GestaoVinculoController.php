<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Usuario;
use App\Support\Cadastro;
use App\Support\ContextoVinculo;
use App\Support\GestaoVinculos;
use App\Support\Permissao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** Adaptador das rotas existentes; seleção do vínculo autenticado continua reservada ao V2. */
class GestaoVinculoController extends Controller
{
    public function index(Request $r)
    {
        $pessoas = GestaoVinculos::pessoas($r->user(), vigentes: false)->where('usuarios.id', '!=', $r->user()->id)
            ->orderBy('contextual.perfil_vinculo')->orderBy('usuarios.nome')->get();
        $totais = $pessoas->countBy('perfil');
        if ($r->filled('perfil')) $pessoas = $pessoas->where('perfil', $r->query('perfil'));
        if ($r->filled('busca')) {
            $busca = mb_strtolower($r->query('busca'));
            $pessoas = $pessoas->filter(fn ($u) => str_contains(mb_strtolower($u->nome), $busca) || str_contains(mb_strtolower($u->email), $busca));
        }
        return ApiResponse::success(['usuarios' => $pessoas->values(), 'totais' => $totais]);
    }

    public function show(Request $r, int $id)
    {
        $v = GestaoVinculos::alvo($r, $id, false);
        $pessoa = GestaoVinculos::pessoas($r->user(), vigentes: false)->where('contextual.vinculo_id', $v->id)->firstOrFail();
        $pessoa->setAttribute('convite_primeiro_acesso', $v->situacao === 'convidado' && !$pessoa->ultimo_acesso
            && DB::table('usuario_vinculos')->where('usuario_id', $id)->count() === 1);
        return ApiResponse::success($pessoa);
    }

    public function update(Request $r, int $id)
    {
        GestaoVinculos::impedirIdentidade($r);
        $data = $r->validate(['ativo' => 'sometimes|boolean', 'perfil' => ['sometimes', Rule::in(['admin_rede', 'dir_nucleo', 'dir_escolar', 'coordenador', 'professor', 'aplicador', 'aluno'])],
            'escola_id' => 'sometimes|integer|min:1']);
        // Perfil é validado pelo catálogo completo abaixo, sem aceitar mudanças de espaço.
        return DB::transaction(function () use ($r, $id, $data) {
            $v = GestaoVinculos::alvo($r, $id, lock: true);
            $e = DB::table('espacos')->find($v->espaco_id);
            abort_if($r->has('escola_id') && (int) $data['escola_id'] !== (int) $e->escola_id, 422, 'Transferência exige outro vínculo.');
            abort_if(isset($data['perfil']) && $data['perfil'] !== $v->perfil, 422, 'Mudança de papel exige outro vínculo; preserve o histórico deste.');
            if (array_key_exists('ativo', $data)) $this->situacao($v, $r, (bool) $data['ativo']);
            return $this->show($r, $id);
        });
    }

    public function toggle(Request $r, int $id)
    {
        return DB::transaction(function () use ($r, $id) {
            $v = GestaoVinculos::alvo($r, $id, lock: true);
            $ativo = $v->situacao !== 'ativo';
            $this->situacao($v, $r, $ativo);
            return ApiResponse::success(['ativo' => $ativo, 'vinculo_id' => $v->id]);
        });
    }

    private function situacao(object $v, Request $r, bool $ativo): void
    {
        abort_unless(in_array($v->situacao, ['ativo', 'suspenso'], true), 422, 'Vínculo não pode ser reativado por esta operação.');
        abort_if($ativo && ($v->inicio_em > now() || ($v->fim_em && $v->fim_em <= now())), 422, 'Vigência do vínculo não permite ativação.');
        $situacao = $ativo ? 'ativo' : 'suspenso';
        if ($v->situacao === $situacao) return;
        DB::table('usuario_vinculos')->where('id', $v->id)->update(['situacao' => $situacao]);
        GestaoVinculos::evento($v, $r->user(), $situacao, 'gestao_vinculo');
    }

    public function permissoes(Request $r, int $id)
    {
        if ($id === (int) $r->user()->id) {
            $v = ContextoVinculo::resolver($r->user());
            abort_if($r->has('vinculo_id') && (int) $r->input('vinculo_id') !== (int) $v->id, 404);
        } else $v = GestaoVinculos::alvo($r, $id, false);
        $alvo = Usuario::findOrFail($id);
        $alvo->perfil = $v->perfil; // Só descritor para a política de gestão; nunca persistido.
        $ajustes = DB::table('vinculo_permissoes as vp')->leftJoin('usuarios as u', 'u.id', '=', 'vp.definida_por')
            ->where('vp.vinculo_id', $v->id)->get(['vp.*', 'u.nome as autor'])->keyBy('permissao');
        $tipos = DB::table('perfil_permissoes')->where('perfil', $v->perfil)->pluck('tipo', 'permissao');
        $itens = DB::table('permissoes')->orderBy('modulo')->orderBy('chave')->get()->filter(fn ($p) => isset($tipos[$p->chave]))
            ->map(function ($p) use ($ajustes, $tipos, $r, $alvo) {
                $a = $ajustes->get($p->chave);
                return ['chave' => $p->chave, 'modulo' => $p->modulo, 'descricao' => $p->descricao, 'tipo' => $tipos[$p->chave],
                    'estado' => $a->efeito ?? 'padrao', 'valida_ate' => $a->valida_ate ?? null, 'definida_por' => $a->autor ?? null,
                    'definida_em' => $a->updated_at ?? null, 'pode_alterar' => $alvo->id !== $r->user()->id
                        && Permissao::podeDefinir($r->user(), $alvo, $p->chave, $tipos[$p->chave] === 'padrao' ? 'retirar' : 'conceder') === null];
            })->values();
        $historico = DB::table('vinculo_eventos as ve')->leftJoin('usuarios as u', 'u.id', '=', 've.ator_id')
            ->where('ve.vinculo_id', $v->id)->orderByDesc('ve.id')->limit(30)
            ->get(['ve.acao', 've.motivo_tecnico', 've.valida_ate', 've.ocorrido_em as created_at', 'u.nome as feita_por']);
        $efetivas = [];
        if ($v->situacao === 'ativo' && $alvo->ativo && $v->inicio_em <= now() && (!$v->fim_em || $v->fim_em > now())) {
            $efetivas = Permissao::efetivas(Usuario::findOrFail($id)->noVinculo($v->id));
        }
        return ApiResponse::success(['usuario' => ['id' => $id, 'nome' => $alvo->nome, 'perfil' => $v->perfil, 'vinculo_id' => $v->id],
            'efetivas' => $efetivas, 'itens' => $itens, 'historico' => $historico]);
    }

    public function definirPermissoes(Request $r, int $id)
    {
        $data = $r->validate(['itens' => 'required|array|min:1', 'itens.*.permissao' => 'required|string|distinct|exists:permissoes,chave',
            'itens.*.estado' => ['required', Rule::in(['padrao', 'conceder', 'retirar'])], 'itens.*.valida_ate' => 'sometimes|nullable|date|after_or_equal:today']);
        DB::transaction(function () use ($r, $id, $data) {
            $v = GestaoVinculos::alvo($r, $id, lock: true);
            $alvo = Usuario::findOrFail($id);
            $alvo->perfil = $v->perfil;
            foreach ($data['itens'] as $item) {
                $q = DB::table('vinculo_permissoes')->where('vinculo_id', $v->id)->where('permissao', $item['permissao']);
                $atual = $q->first();
                $efeito = $item['estado'] === 'padrao' ? ($atual->efeito ?? (Permissao::tipoNoPerfil($v->perfil, $item['permissao']) === 'padrao' ? 'retirar' : 'conceder')) : $item['estado'];
                $motivo = Permissao::podeDefinir($r->user(), $alvo, $item['permissao'], $efeito);
                abort_if($motivo !== null, 403, $motivo ?? 'Permissão indisponível.');
                if ($item['estado'] === 'padrao') $q->delete();
                else DB::table('vinculo_permissoes')->updateOrInsert(['vinculo_id' => $v->id, 'permissao' => $item['permissao']],
                    ['efeito' => $item['estado'], 'valida_ate' => $item['valida_ate'] ?? null, 'definida_por' => $r->user()->id,
                        'created_at' => $atual->created_at ?? now(), 'updated_at' => now()]);
                GestaoVinculos::evento($v, $r->user(), $item['estado'] === 'padrao' ? 'remover' : $item['estado'], $item['permissao'], $item['valida_ate'] ?? null);
            }
        });
        return $this->permissoes($r, $id);
    }
}
