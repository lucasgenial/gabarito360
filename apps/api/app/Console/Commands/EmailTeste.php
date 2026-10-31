<?php

namespace App\Console\Commands;

use App\Mail\EmailModelo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Arquivo: apps/api/app/Console/Commands/EmailTeste.php
 * Função: Coloca um e-mail de teste na fila, para conferir a configuração de envio.
 */
class EmailTeste extends Command
{
    protected $signature = 'g360:email-teste {destino : Endereço que recebe o teste}';

    protected $description = 'Envia um e-mail de teste pela fila';

    public function handle(): int
    {
        Mail::to($this->argument('destino'))->queue(new EmailModelo('senha-alterada', 'Teste de e-mail do Gabarito360', [
            'nome'   => 'equipe',
            'quando' => now()->timezone('America/Bahia')->format('d/m/Y H:i'),
        ]));

        $this->info('E-mail colocado na fila.');

        return self::SUCCESS;
    }
}
