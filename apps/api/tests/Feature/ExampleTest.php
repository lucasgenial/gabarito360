<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /** A API responde no endpoint de saúde (a raiz "/" não existe: o site é o apps/web). */
    public function test_a_api_responde_no_health(): void
    {
        $this->getJson('/api/v1/health')->assertOk()->assertJson(['status' => 'ok']);
    }
}
