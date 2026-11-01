<?php
namespace App\Console\Commands;

use App\Services\GestaoAcessosInternos;
use Illuminate\Console\Command;

class IniciarDesenvolvedor extends Command
{
    protected $signature = 'g360:iniciar-desenvolvedor {usuario} {--aplicar} {--confirmar-usuario=} {--motivo=}';
    protected $description = 'Simula o primeiro Desenvolvedor; aplicação exige aprovação do ID, manutenção e área interna desligada.';
    public function handle(GestaoAcessosInternos $gestao): int
    {
        try {
            $id = (string)$this->argument('usuario');
            if (!ctype_digit($id) || (int)$id < 1 || ($this->option('aplicar') && $this->option('confirmar-usuario') !== $id)) {
                $this->error('Informe um ID válido e, ao aplicar, repita-o em --confirmar-usuario.'); return self::FAILURE;
            }
            $r = $gestao->iniciarDesenvolvedor((int)$id,(bool)$this->option('aplicar'),(string)$this->option('motivo'));
            $this->line(json_encode($r,JSON_THROW_ON_ERROR));
            return self::SUCCESS;
        } catch (\Throwable) {
            $this->error('Operação recusada. Confira elegibilidade, bootstrap anterior, manutenção, habilitação e justificativa. Nenhuma alteração desta transação foi confirmada.');
            return self::FAILURE;
        }
    }
}
