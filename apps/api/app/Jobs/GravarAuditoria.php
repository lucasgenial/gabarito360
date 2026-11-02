<?php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\UniqueConstraintViolationException;

class GravarAuditoria implements ShouldQueue, ShouldBeEncrypted
{
    use Queueable, InteractsWithQueue;
    public int $tries = 3;
    public int $timeout = 20;
    public function __construct(public readonly array $evento) { $this->onQueue('baixa'); }
    public function backoff(): array { return [10, 60]; }
    public function handle(): void
    {
        $nome = config('auditoria.conexao_gravacao');
        if (!is_string($nome) || $nome === '' || !config("database.connections.$nome")
            || (!app()->environment('testing') && $nome === config('database.default'))) {
            throw new \LogicException('Conexão dedicada de auditoria obrigatória.');
        }
        if (in_array(config("database.connections.$nome.driver"), ['mysql','mariadb'], true)
            && !config("database.connections.$nome.username")) {
            throw new \LogicException('Credencial de auditoria obrigatória.');
        }
        $banco = DB::connection($nome);
        // Reentrega não altera o evento existente nem duplica o histórico.
        try {
            $banco->table('auditoria_eventos')->insert($this->evento);
        } catch (UniqueConstraintViolationException $e) {
            if (!$banco->table('auditoria_eventos')->where('id', $this->evento['id'])->exists()) throw $e;
        }
    }
}
