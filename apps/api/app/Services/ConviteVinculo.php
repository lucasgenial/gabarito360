<?php

namespace App\Services;

use App\Models\Usuario;
use App\Support\Cadastro;
use App\Support\ContextoVinculo;
use App\Support\GestaoVinculos;
use App\Support\Permissao;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Convite de um novo papel a uma identidade existente; nunca redefine senha ou perfil global. */
class ConviteVinculo
{
    public function emitir(Usuario $gestor, string $email, int $espaco, string $perfil, array $turmas = []): ?array
    {
        return DB::transaction(function () use ($gestor, $email, $espaco, $perfil, $turmas) {
            $this->destino($gestor, $espaco, $perfil);
            if ($turmas && ($perfil !== 'professor' || !Permissao::pode($gestor, 'turma.vincular_professor'))) abort(403);
            $turmas = array_values(array_unique($turmas));
            if ($turmas && \App\Support\Escopo::turmas($gestor)->where('turmas.espaco_id', $espaco)->whereIn('turmas.id', $turmas)->count() !== count($turmas)) abort(404);
            // Não retorna dados nem distingue conta ausente, inativa ou papel já existente.
            $alvo = Usuario::porEmail($email)->where('ativo', true)->lockForUpdate()->first();
            if (!$alvo || (int) $alvo->id === (int) $gestor->id) return null;
            $v = DB::table('usuario_vinculos')->where('usuario_id', $alvo->id)->where('espaco_id', $espaco)->where('perfil', $perfil)->lockForUpdate()->first();
            if ($v && $v->situacao !== 'convidado') return null;
            // Primeiro acesso ainda usa ContaUsuario; não misturar dois tipos de aceite.
            if (DB::table('password_reset_tokens')->where('email', \App\Support\DadoProtegido::hash($email, 'email'))->where('expira_em', '>', now())->exists()) return null;
            if (!$v) {
                $id = DB::table('usuario_vinculos')->insertGetId(['usuario_id' => $alvo->id, 'espaco_id' => $espaco, 'perfil' => $perfil,
                    'situacao' => 'convidado', 'inicio_em' => now(), 'criado_por' => $gestor->id, 'created_at' => now(), 'updated_at' => now()]);
                $v = DB::table('usuario_vinculos')->find($id);
                foreach ($turmas as $t) DB::table('vinculo_turmas')->insert(['vinculo_id' => $id, 'espaco_id' => $espaco, 'turma_id' => $t]);
            } else {
                $atuais = DB::table('vinculo_turmas')->where('vinculo_id', $v->id)->pluck('turma_id')->map(fn ($id) => (int) $id)->all();
                sort($atuais); sort($turmas);
                if ($atuais !== $turmas) return null; // Não alterar alcance nem revelar vínculo existente.
                if ($v->inicio_em > now() || ($v->fim_em && $v->fim_em <= now())) return null;
            }
            DB::table('vinculo_convites')->where('vinculo_id', $v->id)->whereNull('aceito_em')->whereNull('recusado_em')->update(['expira_em' => now(), 'updated_at' => now()]);
            $token = Str::random(64);
            $id = DB::table('vinculo_convites')->insertGetId(['vinculo_id' => $v->id, 'token_hash' => hash('sha256', $token),
                'expira_em' => now()->addDays(7), 'convidado_por' => $gestor->id, 'convidado_por_vinculo_id' => $gestor->vinculoContextoId(),
                'created_at' => now(), 'updated_at' => now()]);
            GestaoVinculos::evento($v, $gestor, 'convite_emitido', 'convite:'.$id);
            // Retorno interno ao adaptador de entrega. Nunca devolver token/dados ao gestor via HTTP.
            return ['convite_id' => $id, 'vinculo_id' => (int) $v->id, 'usuario_id' => (int) $alvo->id, 'token' => $token];
        });
    }

    public function responder(Usuario $titular, int $id, string $token, bool $aceitar): void
    {
        DB::transaction(function () use ($titular, $id, $token, $aceitar) {
            abort_unless(Usuario::where('id', $titular->id)->where('ativo', true)->lockForUpdate()->exists(), 403);
            $c = DB::table('vinculo_convites')->where('id', $id)->lockForUpdate()->first();
            abort_unless($c && hash_equals($c->token_hash, hash('sha256', $token)) && !$c->aceito_em && !$c->recusado_em && $c->expira_em > now(), 422, 'Convite inválido ou expirado.');
            $v = DB::table('usuario_vinculos')->where('id', $c->vinculo_id)->where('usuario_id', $titular->id)->lockForUpdate()->first();
            abort_unless($v && $v->situacao === 'convidado' && $v->inicio_em <= now() && (!$v->fim_em || $v->fim_em > now()), 422, 'Convite inválido ou expirado.');
            if ($aceitar) {
                $emissor = Usuario::whereKey($c->convidado_por)->lockForUpdate()->first();
                DB::table('usuario_vinculos')->where('id', $c->convidado_por_vinculo_id)->lockForUpdate()->first();
                abort_unless($emissor, 422, 'Convite indisponível.');
                $emissor = $emissor->noVinculo($c->convidado_por_vinculo_id);
                $this->destino($emissor, (int) $v->espaco_id, $v->perfil);
            }
            DB::table('usuario_vinculos')->where('id', $v->id)->update(['situacao' => $aceitar ? 'ativo' : 'encerrado', 'fim_em' => $aceitar ? null : now()]);
            DB::table('vinculo_convites')->where('id', $id)->update([$aceitar ? 'aceito_em' : 'recusado_em' => now(), 'updated_at' => now()]);
            GestaoVinculos::evento($v, $titular, $aceitar ? 'convite_aceito' : 'convite_recusado', 'convite:'.$id);
        });
    }

    private function destino(Usuario $gestor, int $espaco, string $perfil): void
    {
        ContextoVinculo::resolver($gestor);
        abort_unless(Permissao::pode($gestor, 'usuario.gerir') && Cadastro::podeGerirPerfil($gestor, $perfil), 403);
        $e = DB::table('espacos')->find($espaco);
        $tipo = match ($perfil) { 'admin_rede' => 'rede', 'dir_nucleo' => 'nucleo', default => 'escola' };
        abort_unless($e && $e->tipo === $tipo && ContextoVinculo::espacoValido($e), 404);
        $ids = match ($tipo) {
            'rede' => $gestor->perfil === 'admin_rede' ? \App\Support\Instituicoes::redesIds($gestor) : [],
            'nucleo' => $gestor->perfil === 'admin_rede' ? \App\Support\Instituicoes::nucleosIds($gestor) : [],
            'escola' => \App\Support\Escopo::escolaIds($gestor),
        };
        abort_unless(in_array((int) $e->{$tipo.'_id'}, $ids ?? [], true), 404);
    }
}
