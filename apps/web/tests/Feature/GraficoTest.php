<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * Arquivo: apps/web/tests/Feature/GraficoTest.php
 * Função: O componente de gráfico entrega os dados às telas, inclusive coleções e valores vazios.
 */
class GraficoTest extends TestCase
{
    public function test_entrega_colecoes_e_valores_vazios_em_json(): void
    {
        $html = Blade::render('<x-grafico descricao="Média" :dados="$dados" />', ['dados' => [
            'categorias' => collect(['1º bimestre', '2º bimestre']),
            'series'     => [['nome' => 'Média', 'valores' => collect([7.5, null])]],
        ]]);

        $this->assertMatchesRegularExpression('/data-grafico="([^"]+)"/', $html);
        preg_match('/data-grafico="([^"]+)"/', $html, $m);
        $dados = json_decode(html_entity_decode($m[1]), true);

        $this->assertSame(['1º bimestre', '2º bimestre'], $dados['categorias']);
        $this->assertSame([7.5, null], $dados['series'][0]['valores']);
        $this->assertStringContainsString('aria-label="Média"', $html);
    }
}
