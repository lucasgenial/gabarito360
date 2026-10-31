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

    public function convidar(Usuario $usuario, Usuario $convidadoPor): void
    {
        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => \App\Support\DadoProtegido::hash($usuario->email, 'email')],
            ['token' => Hash::make($token), 'created_at' => now(), 'expira_em' => now()->addDays(self::VALIDADE_CONVITE_DIAS), 'convidado_por' => $convidadoPor->id]
        );

        $link = config('emails.url_web') . '/convite?' . http_build_query(['token' => $token, 'conta' => \App\Support\DadoProtegido::hash($usuario->email, 'email')]);

        Mail::to($usuario->email)->queue(new EmailModelo('convite-usuario', 'Convite para o Gabarito360', $this->dadosDoConvite($usuario, $convidadoPor) + ['link' => $link]));
    }

    /** O que o convidado precisa saber para decidir. @return array<string, string> */
    public function dadosDoConvite(Usuario $usuario, ?Usuario $convidadoPor): array
    {
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

        return $registro
            && $registro->expira_em
            && now()->lessThan($registro->expira_em)
            && Hash::check($token, $registro->token) ? $registro : null;
    }

    public function recusarConvite(Usuario $usuario, object $convite): void
    {
        DB::table('password_reset_tokens')->where('email', \App\Support\DadoProtegido::hash($usuario->email, 'email'))->delete();

        $gestor = Usuario::find($convite->convidado_por);
        $dados = $this->dadosDoConvite($usuario, $gestor);
        $usuario->update(['ativo' => false]);

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
            ['email' => \App\Support\DadoProtegido::hash($usuario->email, 'email'), 'token' => Hash::make($token), 'created_at' => now(), 'expira_em' => null],
            ['email'],
            ['token', 'created_at', 'expira_em']
        );

        $link = config('emails.url_web') . '/redefinir-senha?' . http_build_query(['token' => $token, 'conta' => \App\Support\DadoProtegido::hash($usuario->email, 'email')]);

        Mail::to($usuario->email)->queue(new EmailModelo('recuperar-senha', 'Redefina sua senha do Gabarito360', [
            'nome' => $usuario->nome,
            'link' => $link,
        ]));
    }
}
