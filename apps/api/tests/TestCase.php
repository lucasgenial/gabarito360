<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Regressões do regime legado. Suítes de espaços ativam explicitamente após este setup.
        config(['espacos.ativo' => false]);
        // Fixture compartilhada da suíte; isolamento real de credenciais é ensaiado no CI.
        config(['auditoria.conexao_gravacao'=>config('database.default')]);
        // Suítes de autorização/fila usam imagens vazias deliberadamente. Visão real tem suíte própria;
        // ela remove este double e ensaia o validador/Python. Nenhum bypass existe no código da aplicação.
        $this->app->instance(\App\Services\Omr\QualidadeCartao::class, new class extends \App\Services\Omr\QualidadeCartao {
            public function avaliar(string $caminho): array { return ['aprovada'=>true,'versao'=>'fixture','codigo'=>'adequada','mensagem'=>'Foto fictícia']; }
        });
    }
}
