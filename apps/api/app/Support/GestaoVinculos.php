<?php

namespace App\Support;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Consultas de terceiros e gestão de um vínculo, sem poderes sobre a identidade global. */
class GestaoVinculos
{
    public static function consulta(Usuario $ator, bool $vigentes = false)
    {
        ContextoVinculo::resolver($ator);
        $escolas = Escopo::escolaIds($ator) ?? [];
        $nucleos = Instituicoes::nucleosIds($ator) ?? [];
        $redes = Instituicoes::redesIds($ator) ?? [];
        $q = DB::table('usuario_vinculos as v')->join('espacos as ev', 'ev.id', '=', 'v.espaco_id')
            ->join('usuarios as uv', 'uv.id', '=', 'v.usuario_id')
            ->where('ev.situacao', 'ativo')->where(function ($q) use ($ator, $escolas, $nucleos, $redes) {
                $q->where(fn ($q) => $q->where('ev.tipo', 'escola')->whereIn('ev.escola_id', $escolas)
                    ->whereIn('v.perfil', Cadastro::DE_ESCOLA));
                if (in_array($ator->perfil, ['admin_rede', 'dir_nucleo'], true)) {
                    $q->orWhere(fn ($q) => $q->where('ev.tipo', 'nucleo')->whereIn('ev.nucleo_id', $nucleos)->where('v.perfil', 'dir_nucleo'));
                }
                if ($ator->perfil === 'admin_rede') {
                    $q->orWhere(fn ($q) => $q->where('ev.tipo', 'rede')->whereIn('ev.rede_id', $redes)->where('v.perfil', 'admin_rede'));
                }
            });
        // Valida também espaços ancestrais/clientes dos alvos; não basta o alcance do ator.
        $ids = (clone $q)->distinct()->pluck('v.espaco_id');
        $validos = DB::table('espacos')->whereIn('id', $ids)->get()
            ->filter(fn ($e) => ContextoVinculo::espacoValido($e))->pluck('id');
        $q->whereIn('v.espaco_id', $validos);
        if ($vigentes) $q->where('uv.ativo', true)->where('v.situacao', 'ativo')->where('v.inicio_em', '<=', now())
            ->where(fn ($q) => $q->whereNull('v.fim_em')->orWhere('v.fim_em', '>', now()));
        return $q;
    }

    /** Projeção de leitura: cada linha é um vínculo, mesmo quando a pessoa aparece duas vezes. */
    public static function pessoas(Usuario $ator, ?int $escola = null, ?int $turma = null, bool $vigentes = true)
    {
        $q = self::consulta($ator, $vigentes);
        if ($escola !== null) $q->where('ev.tipo', 'escola')->where('ev.escola_id', $escola);
        if ($turma !== null) $q->where('v.perfil', 'professor')->whereExists(fn ($s) => $s->selectRaw('1')
            ->from('vinculo_turmas as vt')->join('turmas as t', 't.id', '=', 'vt.turma_id')
            ->whereColumn('vt.vinculo_id', 'v.id')->whereColumn('vt.espaco_id', 'v.espaco_id')
            ->whereColumn('t.espaco_id', 'v.espaco_id')->whereColumn('t.escola_id', 'ev.escola_id')->where('t.id', $turma));
        return Usuario::query()->joinSub($q->select('v.id as vinculo_id', 'v.usuario_id', 'v.perfil as perfil_vinculo', 'v.situacao', 'v.inicio_em', 'v.fim_em', 'v.espaco_id', 'ev.escola_id', 'ev.nome as espaco_nome'), 'contextual', 'contextual.usuario_id', '=', 'usuarios.id')
            ->select('usuarios.id', 'usuarios.nome', 'usuarios.email', 'usuarios.ultimo_acesso', 'contextual.vinculo_id',
                'contextual.perfil_vinculo as perfil', 'contextual.situacao', 'contextual.espaco_id', 'contextual.escola_id', 'contextual.espaco_nome', 'contextual.inicio_em as created_at')
            ->selectRaw("CASE WHEN usuarios.ativo = 1 AND contextual.situacao = 'ativo' AND contextual.inicio_em <= ? AND (contextual.fim_em IS NULL OR contextual.fim_em > ?) THEN 1 ELSE 0 END AS ativo", [now(), now()]);
    }

    public static function alvo(Request $request, int $pessoa, bool $gerir = true, bool $lock = false): object
    {
        $request->validate(['vinculo_id' => 'sometimes|required|integer|min:1']);
        $ator = $request->user();
        $q = self::consulta($ator)->where('v.usuario_id', $pessoa);
        if ($request->has('vinculo_id')) $q->where('v.id', $request->input('vinculo_id'));
        if ($lock) $q->lockForUpdate();
        $vinculos = $q->select('v.*')->get();
        abort_if($vinculos->isEmpty(), 404, 'Vínculo não encontrado.');
        abort_if($vinculos->count() !== 1, 422, 'Informe o vínculo que deseja gerir.');
        $v = $vinculos->first();
        if ($gerir) abort_unless((int) $ator->id !== $pessoa && Cadastro::podeGerirPerfil($ator, $v->perfil), 403, 'Perfil fora da sua gestão.');
        return $v;
    }

    public static function evento(object $v, Usuario $ator, string $acao, string $motivo, ?string $validaAte = null): void
    {
        DB::table('usuario_vinculos')->where('id', $v->id)->increment('versao_autorizacao', 1, ['updated_at' => now()]);
        DB::table('vinculo_eventos')->insert(['vinculo_id' => $v->id, 'ator_id' => $ator->id,
            'acao' => $acao, 'motivo_tecnico' => $motivo, 'valida_ate' => $validaAte, 'ocorrido_em' => now()]);
    }

    public static function impedirIdentidade(Request $request): void
    {
        $campos = ['nome', 'email', 'cpf', 'password', 'forcar_troca_senha', 'data_nascimento', 'telefone',
            'data_ingresso', 'formacao_academica', 'especializacao', 'registro_profissional', 'observacoes'];
        abort_if(collect($campos)->contains(fn ($campo) => $request->exists($campo)), 422,
            'A gestão do vínculo não altera os dados pessoais da conta.');
    }
}
