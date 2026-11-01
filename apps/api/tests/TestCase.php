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
    }
}
