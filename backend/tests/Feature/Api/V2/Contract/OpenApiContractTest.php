<?php

namespace Tests\Feature\Api\V2\Contract;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Symfony\Component\Yaml\Yaml;
use Tests\TestCase;

/**
 * Contrato de paridade: toda rota /api/v2 precisa estar documentada em
 * docs/openapi-v2.yaml e todo path documentado precisa existir como rota real.
 * A comparação normaliza nomes de parâmetros ({escola} == {id} == {}).
 */
class OpenApiContractTest extends TestCase
{
    private const METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

    public function test_rotas_e_openapi_estao_em_paridade(): void
    {
        $rotas = $this->routeOperations();
        $documentadas = $this->documentedOperations();

        $naoDocumentadas = array_values(array_diff($rotas, $documentadas));
        $orfas = array_values(array_diff($documentadas, $rotas));

        sort($naoDocumentadas);
        sort($orfas);

        $mensagem = '';
        if ($naoDocumentadas !== []) {
            $mensagem .= "Rotas /api/v2 sem documentação:\n  ".implode("\n  ", $naoDocumentadas)."\n";
        }
        if ($orfas !== []) {
            $mensagem .= "Paths no openapi-v2.yaml sem rota correspondente:\n  ".implode("\n  ", $orfas)."\n";
        }

        $this->assertTrue($naoDocumentadas === [] && $orfas === [], $mensagem);
    }

    /** @return list<string> operações "METHOD /path-normalizado" das rotas reais */
    private function routeOperations(): array
    {
        $operations = [];

        foreach (RouteFacade::getRoutes() as $route) {
            /** @var Route $route */
            $uri = $route->uri();
            if ($uri !== 'api/v2' && ! str_starts_with($uri, 'api/v2/')) {
                continue;
            }

            $path = $this->normalize(substr($uri, strlen('api/v2')));

            foreach ($route->methods() as $method) {
                if (in_array($method, self::METHODS, true)) {
                    $operations[$method.' '.$path] = true;
                }
            }
        }

        return array_keys($operations);
    }

    /** @return list<string> operações "METHOD /path-normalizado" do openapi */
    private function documentedOperations(): array
    {
        /** @var array{paths?: array<string, array<string, mixed>>} $spec */
        $spec = Yaml::parseFile(base_path('../docs/openapi-v2.yaml'));
        $operations = [];

        foreach ($spec['paths'] ?? [] as $path => $methods) {
            $normalized = $this->normalize($path);
            foreach (array_keys($methods) as $method) {
                $method = strtoupper((string) $method);
                if (in_array($method, self::METHODS, true)) {
                    $operations[$method.' '.$normalized] = true;
                }
            }
        }

        return array_keys($operations);
    }

    private function normalize(string $path): string
    {
        $path = '/'.ltrim($path, '/');
        $path = preg_replace('#\{[^}]+\}#', '{}', $path);

        return rtrim($path, '/') ?: '/';
    }
}
