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
    }
}
