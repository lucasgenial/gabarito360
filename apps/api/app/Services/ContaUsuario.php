<?php

namespace App\Services;

use App\Mail\EmailModelo;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Arquivo: apps/api/app/Services/ContaUsuario.php
 * Função: Convite de novos usuários e troca de e-mail com confirmação.
 */
class ContaUsuario
{
    private const VALIDADE_CONVITE_DIAS = 7;

    private const VALIDADE_TROCA_HORAS = 24;

    private const PERFIS = [
        'secretario_educacao' => 'Secretário de Educação',
        'admin_rede'          => 'Administrador da Rede',
        'dir_nucleo'          => 'Diretor de Núcleo',
        'dir_escolar'         => 'Diretor Escolar',
        'coordenador'         => 'Coordenador Pedagógico',
        'professor'           => 'Professor',
        'aplicador'           => 'Aplicador de Prova',
        'aluno'               => 'Aluno',
    ];

    public function convidar(Usuario $usuario, Usuario $convidadoPor, ?int $vinculoId = null): void
    {
        if ($vinculoId !== null) {
            $v = DB::table('usuario_vinculos')->where('id', $vinculoId)->where('usuario_id', $usuario->id)->first();
            abort_unless($v && $v->situacao === 'convidado' && $usuario->ativo
                && \App\Support\ContextoVinculo::presente($convidadoPor)
                && \App\Support\GestaoVinculos::consulta($convidadoPor)->where('v.id', $vinculoId)->exists()
                && \App\Support\Cadastro::podeGerirPerfil($convidadoPor, $v->perfil), 403, 'Convite indisponível.');
            // Este fluxo define a primeira senha; uma conta estabelecida usa recuperação pelo titular.
            abort_unless(DB::table('usuario_vinculos')->where('usuario_id', $usuario->id)->count() === 1
                && !$usuario->ultimo_acesso, 422, 'Conta existente exige convite de vínculo sem redefinir a identidade.');
        } else {
            abort_if(\App\Support\ContextoVinculo::presente($convidadoPor)
                || DB::table('usuario_vinculos')->where('usuario_id', $usuario->id)->exists(), 422, 'Informe o vínculo do convite.');
        }
        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => \App\Support\DadoProtegido::hash($usuario->email, 'email')],
            ['token' => Hash::make($token), 'created_at' => now(), 'expira_em' => now()->addDays(self::VALIDADE_CONVITE_DIAS), 'convidado_por' => $convidadoPor->id, 'vinculo_id' => $vinculoId]
        );

        $link = config('emails.url_web') . '/convite?' . http_build_query(['token' => $token, 'conta' => \App\Support\DadoProtegido::hash($usuario->email, 'email')]);

        Mail::to($usuario->email)->queue(new EmailModelo('convite-usuario', 'Convite para o Gabarito360', $this->dadosDoConvite($usuario, $convidadoPor, $vinculoId) + ['link' => $link]));
    }

    /** O que o convidado precisa saber para decidir. @return array<string, string> */
    public function dadosDoConvite(Usuario $usuario, ?Usuario $convidadoPor, ?int $vinculoId = null): array
    {
        if ($vinculoId !== null) {
            $v = DB::table('usuario_vinculos')->where('id', $vinculoId)->where('usuario_id', $usuario->id)->first();
            abort_unless($v, 422, 'Convite indisponível.');
            $e = DB::table('espacos')->find($v->espaco_id);
            abort_unless($e && \App\Support\ContextoVinculo::espacoValido($e), 422, 'Convite indisponível.');
            $tabela = match ($e->tipo) { 'rede' => 'redes', 'nucleo' => 'nucleos', 'escola' => 'escolas' };
            return ['nome' => $usuario->nome, 'funcao' => self::PERFIS[$v->perfil] ?? $v->perfil,
                'tipo_vinculo' => match ($e->tipo) { 'rede' => 'Rede', 'nucleo' => 'Núcleo', 'escola' => 'Escola' }, 'vinculo' => DB::table($tabela)->where('id', $e->{$e->tipo.'_id'})->value('nome'),
                'turmas' => DB::table('vinculo_turmas as vt')->join('turmas as t', 't.id', '=', 'vt.turma_id')
                    ->where('vt.vinculo_id', $v->id)->where('vt.espaco_id', $e->id)->whereColumn('t.espaco_id', 'vt.espaco_id')
                    ->orderBy('t.nome')->pluck('t.nome')->implode(', ') ?: 'Não se aplica',
                'convidado_por' => $convidadoPor?->nome ?? 'Equipe Gabarito360'];
        }
        abort_if(DB::table('usuario_vinculos')->where('usuario_id', $usuario->id)->exists(), 422, 'Convite legado exige conciliação.');
        $escopos = DB::table('usuario_escopos')->where('usuario_id', $usuario->id)->get()->groupBy('escopo_tipo');
        $ids = fn (string $tipo) => collect($escopos->get($tipo, []))->pluck('escopo_id');

        [$tipo, $vinculo] = match (true) {
            $ids('escola')->isNotEmpty() => ['Escola', DB::table('escolas')->whereIn('id', $ids('escola'))->pluck('nome')->implode(', ')],
            $ids('nucleo')->isNotEmpty() => ['Núcleo', DB::table('nucleos')->whereIn('id', $ids('nucleo'))->pluck('nome')->implode(', ')],
            $ids('rede')->isNotEmpty()   => ['Rede', DB::table('redes')->whereIn('id', $ids('rede'))->pluck('nome')->implode(', ')],
            default                      => ['Vínculo', 'Gabarito360'],
        };

        return [
            'nome'          => $usuario->nome,
            'funcao'        => self::PERFIS[$usuario->perfil] ?? $usuario->perfil,
            'tipo_vinculo'  => $tipo,
            'vinculo'       => $vinculo,
            'turmas'        => DB::table('turmas')->whereIn('id', $ids('turma'))->orderBy('nome')->pluck('nome')->implode(', ') ?: 'Não se aplica',
            'convidado_por' => $convidadoPor?->nome ?? 'Equipe Gabarito360',
        ];
    }

    /** Convite válido e ainda não usado, ou null. */
    public function convitePendente(string $email, string $token): ?object
    {
        $registro = DB::table('password_reset_tokens')->where('email', \App\Support\DadoProtegido::conta($email))->first();

        if ($registro && !$this->contextoConviteValido($registro)) return null;
        return $registro
            && $registro->expira_em
            && now()->lessThan($registro->expira_em)
            && Hash::check($token, $registro->token) ? $registro : null;
    }

    public function recusarConvite(Usuario $usuario, object $convite): void
    {
        abort_unless(\App\Support\DadoProtegido::hash($usuario->email, 'email') === $convite->email, 422, 'Convite indisponível.');
        $gestor = Usuario::find($convite->convidado_por);
        $dados = $this->dadosDoConvite($usuario, $gestor, $convite->vinculo_id ?? null);
        DB::transaction(function () use ($usuario, $convite) {
            $atual = DB::table('password_reset_tokens')->where('email', $convite->email)->lockForUpdate()->first();
            abort_unless($atual && $atual->expira_em && now()->lessThan($atual->expira_em) && hash_equals($atual->token, $convite->token) && $this->contextoConviteValido($atual), 422, 'Convite indisponível.');
            if ($atual->vinculo_id) {
                DB::table('usuario_vinculos')->where('id', $atual->vinculo_id)->update(['situacao' => 'encerrado', 'fim_em' => now()]);
                \App\Support\GestaoVinculos::evento((object) ['id' => $atual->vinculo_id], $usuario, 'recusado', 'convite');
            } else $usuario->update(['ativo' => false]);
            DB::table('password_reset_tokens')->where('email', $convite->email)->delete();
        });

        if ($gestor) {
            Mail::to($gestor->email)->queue(new EmailModelo('convite-recusado', 'Convite recusado', [
                'nome'      => $gestor->nome,
                'convidado' => $usuario->nome,
                'funcao'    => $dados['funcao'],
                'vinculo'   => $dados['vinculo'],
            ]));
        }
    }

    public function pedirTrocaDeEmail(Usuario $usuario, string $novoEmail): void
    {
        $token = Str::random(64);

        DB::table('trocas_email')->updateOrInsert(
            ['usuario_id' => $usuario->id],
            ['novo_email' => 'g360:v1:'.\Illuminate\Support\Facades\Crypt::encryptString($novoEmail), 'token_hash' => hash('sha256', $token), 'expira_em' => now()->addHours(self::VALIDADE_TROCA_HORAS), 'created_at' => now()]
        );

        Mail::to($novoEmail)->queue(new EmailModelo('confirmar-email', 'Confirme seu novo e-mail', [
            'nome' => $usuario->nome,
            'link' => config('emails.url_web') . '/confirmar-email?' . http_build_query(['token' => $token]),
        ]));
    }

    /** @return bool false quando o link é inválido, venceu ou o endereço foi ocupado */
    public function confirmarTrocaDeEmail(string $token): bool
    {
        $troca = DB::table('trocas_email')->where('token_hash', hash('sha256', $token))->first();
        if (!$troca) {
            return false;
        }

        DB::table('trocas_email')->where('id', $troca->id)->delete();

        $troca->novo_email = \Illuminate\Support\Facades\Crypt::decryptString(substr($troca->novo_email, 8));
        $usuario = Usuario::find($troca->usuario_id);
        if (!$usuario || now()->greaterThan($troca->expira_em) || Usuario::porEmail($troca->novo_email)->exists()) {
            return false;
        }

        $anterior = $usuario->email;
        DB::table('password_reset_tokens')->where('email', \App\Support\DadoProtegido::hash($anterior, 'email'))->delete();
        $usuario->update(['email' => $troca->novo_email]);

        Mail::to($anterior)->queue(new EmailModelo('email-alterado-aviso', 'O e-mail da sua conta foi alterado', [
            'nome'       => $usuario->nome,
            'novo_email' => $troca->novo_email,
        ]));

        return true;
    }

    /** Link de nova senha, vale 60 minutos. Usado por "esqueci a senha" e pelo gestor. */
    public function enviarLinkDeSenha(Usuario $usuario): void
    {
        $token = Str::random(64);

        DB::table('password_reset_tokens')->upsert(
            ['email' => \App\Support\DadoProtegido::hash($usuario->email, 'email'), 'token' => Hash::make($token), 'created_at' => now(), 'expira_em' => null, 'vinculo_id' => null],
            ['email'],
            ['token', 'created_at', 'expira_em', 'vinculo_id']
        );

        $link = config('emails.url_web') . '/redefinir-senha?' . http_build_query(['token' => $token, 'conta' => \App\Support\DadoProtegido::hash($usuario->email, 'email')]);

        Mail::to($usuario->email)->queue(new EmailModelo('recuperar-senha', 'Redefina sua senha do Gabarito360', [
            'nome' => $usuario->nome,
            'link' => $link,
        ]));
    }
    /** Um link antigo nunca passa a decidir sobre todos os vínculos após a conversão. */
    public function contextoConviteValido(object $registro): bool
    {
        if (!$registro->expira_em) return true;
        $u = Usuario::where('email_hash', $registro->email)->lockForUpdate()->first();
        if (!$u || !$u->ativo) return false;
        if (!($registro->vinculo_id ?? null)) return !DB::table('usuario_vinculos')->where('usuario_id', $u->id)->exists();
        $v = DB::table('usuario_vinculos')->where('id', ($registro->vinculo_id ?? null))->where('usuario_id', $u->id)
            ->where('situacao', 'convidado')->where('inicio_em', '<=', now())
            ->where(fn ($q) => $q->whereNull('fim_em')->orWhere('fim_em', '>', now()))->lockForUpdate()->first();
        if (!$v) return false;
        $e = DB::table('espacos')->find($v->espaco_id);
        return $e && \App\Support\ContextoVinculo::espacoValido($e)
            && DB::table('usuario_vinculos')->where('usuario_id', $u->id)->count() === 1 && !$u->ultimo_acesso;
    }

    public function aceitarContextoConvite(object $registro, Usuario $usuario): void
    {
        if (!$registro->expira_em || !($registro->vinculo_id ?? null)) return;
        DB::table('usuario_vinculos')->where('id', ($registro->vinculo_id ?? null))->where('usuario_id', $usuario->id)
            ->update(['situacao' => 'ativo']);
        \App\Support\GestaoVinculos::evento((object) ['id' => ($registro->vinculo_id ?? null)], $usuario, 'aceito', 'convite');
    }

}
