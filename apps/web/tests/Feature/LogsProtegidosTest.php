<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class LogsProtegidosTest extends TestCase
{
    public function test_stack_preserva_diagnostico_sem_processar_o_handler_duas_vezes(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'g360-stack-');
        try {
            config(['logging.channels.l3_arquivo' => ['driver' => 'single', 'path' => $path],
                'logging.channels.l3_stack' => ['driver' => 'stack', 'channels' => ['l3_arquivo']]]);
            Log::channel('l3_stack')->error('Falha privada', ['exception' => new \RuntimeException('dado-secreto')]);
            $conteudo = file_get_contents($path);
            $this->assertStringContainsString('RuntimeException', $conteudo);
            $this->assertStringNotContainsString('dado-secreto', $conteudo);
        } finally {
            @unlink($path);
        }
    }

    public function test_arquivo_final_nao_recebe_dados_do_contexto_objetos_sql_ou_smtp(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'g360-log-');
        try {
            $log = Log::build(['driver' => 'single', 'path' => $path, 'replace_placeholders' => true]);
            $this->assertInstanceOf(\App\Logging\GerenciadorProtegido::class, app('log'));
            $log->error('Falha {email}', ['email' => 'ficticio@example.test', 'nome' => 'Nome Fictício',
                'cpf' => '12345678901', 'extra' => ['password' => 'segredo'],
                'exception' => new \RuntimeException('SQL nome=Nome Fictício')]);
            $log->debug("From: ficticio@example.test\nMensagem Nome Fictício");
            $log->warning('OMR nativo: leitura falhou', ['exit_code' => 1, 'erro' => 'Nome Fictício']);
            $conteudo = file_get_contents($path);
            foreach (['ficticio@example.test', 'Nome Fictício', '12345678901', 'segredo'] as $dado) {
                $this->assertStringNotContainsString($dado, $conteudo);
            }
            $this->assertStringContainsString('RuntimeException', $conteudo);
            $this->assertStringContainsString('OMR nativo: leitura falhou', $conteudo);
            $this->assertStringContainsString('exit_code', $conteudo);
        } finally {
            @unlink($path);
        }
    }
}
