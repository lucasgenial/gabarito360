<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Turnstile
{
    public function validar(string $token): void
    {
        $secret = config('services.turnstile.secret_key');
        $hostnames = config('services.turnstile.hostnames', []);
        if (!$secret || !$hostnames) {
            throw new HttpException(503, 'Verificação de segurança indisponível. Tente novamente mais tarde.');
        }

        try {
            $resposta = Http::asForm()->connectTimeout(3)->timeout(6)->post(
                'https://challenges.cloudflare.com/turnstile/v0/siteverify',
                ['secret' => $secret, 'response' => $token]
            );
        } catch (ConnectionException) {
            throw new HttpException(503, 'Verificação de segurança indisponível. Tente novamente mais tarde.');
        }

        if (!$resposta->successful()) {
            throw new HttpException(503, 'Verificação de segurança indisponível. Tente novamente mais tarde.');
        }

        if ($resposta->json('success') !== true
            || !in_array($resposta->json('hostname'), $hostnames, true)
            || $resposta->json('action') !== 'fale-conosco') {
            throw ValidationException::withMessages([
                'cf-turnstile-response' => 'Confirme a verificação de segurança e tente novamente.',
            ]);
        }
    }
}
