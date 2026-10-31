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

    public function convidar(Usuario $usuario): void
    {
        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $usuario->email],
            ['token' => Hash::make($token), 'created_at' => now(), 'expira_em' => now()->addDays(self::VALIDADE_CONVITE_DIAS)]
        );

        $link = config('emails.url_web') . '/redefinir-senha?' . http_build_query([
            'token' => $token, 'email' => $usuario->email, 'convite' => 1,
        ]);

        Mail::to($usuario->email)->queue(new EmailModelo('convite-usuario', 'Seu acesso ao Gabarito360', [
            'nome' => $usuario->nome,
            'link' => $link,
        ]));
    }

    public function pedirTrocaDeEmail(Usuario $usuario, string $novoEmail): void
    {
        $token = Str::random(64);

        DB::table('trocas_email')->updateOrInsert(
            ['usuario_id' => $usuario->id],
            ['novo_email' => $novoEmail, 'token_hash' => hash('sha256', $token), 'expira_em' => now()->addHours(self::VALIDADE_TROCA_HORAS), 'created_at' => now()]
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

        $usuario = Usuario::find($troca->usuario_id);
        if (!$usuario || now()->greaterThan($troca->expira_em) || Usuario::where('email', $troca->novo_email)->exists()) {
            return false;
        }

        $anterior = $usuario->email;
        DB::table('password_reset_tokens')->where('email', $anterior)->delete();
        $usuario->update(['email' => $troca->novo_email]);

        Mail::to($anterior)->queue(new EmailModelo('email-alterado-aviso', 'O e-mail da sua conta foi alterado', [
            'nome'       => $usuario->nome,
            'novo_email' => $troca->novo_email,
        ]));

        return true;
    }
}
