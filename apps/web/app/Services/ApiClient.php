<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Psr7\Utils;

class ApiClient
{
    private string $baseUrl;
    private bool $usarIdentidade = false;

    public function identidade(): self
    {
        $copia = clone $this;
        $copia->usarIdentidade = true;
        return $copia;
    }

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.api.base_url'), '/');
    }

    private function http(): PendingRequest
    {
        $request = Http::baseUrl($this->baseUrl)
            ->acceptJson()
            ->timeout(15);

        if ($token = (!$this->usarIdentidade ? (\App\Support\ContextoWeb::atual()['token'] ?? null) : null) ?? \App\Support\ContextoWeb::identidade()) {
            $request = $request->withToken($token);
        }

        return $request;
    }

    public function post(string $path, array $data = []): Response
    {
        return $this->paraNavegador($this->http()->post($path, $data));
    }

    public function get(string $path, array $query = []): Response
    {
        return $this->paraNavegador($this->http()->get($path, $query));
    }

    public function put(string $path, array $data = []): Response
    {
        return $this->paraNavegador($this->http()->put($path, $data));
    }

    public function delete(string $path, array $data = []): Response
    {
        return $this->paraNavegador($this->http()->delete($path, $data));
    }

    public function postFile(string $path, string $fileField, \Illuminate\Http\UploadedFile $file, array $data = [], int $timeout = 90): Response
    {
        return $this->paraNavegador($this->http()->timeout($timeout)
            ->attach($fileField, $file->get(), $file->getClientOriginalName())
            ->post($path, $data));
    }

    /** GET que retorna arquivo (PDF etc.) — timeout maior para geração. */
    public function getArquivo(string $path, array $query = []): Response
    {
        return $this->http()->timeout(180)->get($path, $query);
    }

    /** Traduz somente referências conhecidas, sem aceitar um URL de destino do cliente. */
    private function paraNavegador(Response $resposta): Response
    {
        if (!$resposta->successful() || !str_contains(strtolower((string)$resposta->header('Content-Type')), 'application/json')) return $resposta;
        $dados = $resposta->json();
        if (!is_array($dados)) return $resposta;
        $alterado = false;
        $traduzir = function (array $valores) use (&$traduzir, &$alterado): array {
            foreach ($valores as $campo => $valor) {
                if (is_array($valor)) { $valores[$campo] = $traduzir($valor); continue; }
                if (!in_array($campo, ['foto_url','miniatura_url','endireitada_url','cartao_foto_url'], true) || !is_string($valor)) continue;
                if (preg_match('~^/api/v1/cartoes/([1-9][0-9]*)/imagens/(original|miniatura|endireitada)$~D', $valor, $m)) {
                    $valores[$campo] = route('cartoes.imagem-segura', ['id'=>$m[1], 'tipo'=>$m[2]]);
                    $alterado = true;
                } elseif (str_starts_with((string)parse_url($valor, PHP_URL_PATH), '/api/v1/cartoes/')) {
                    // Resposta incompatível/legada não pode voltar a publicar capacidades na URL.
                    $valores[$campo] = null;
                    $alterado = true;
                }
            }
            return $valores;
        };
        $dados = $traduzir($dados);
        if (!$alterado) return $resposta;
        $corpo = json_encode($dados, JSON_THROW_ON_ERROR);
        return new Response($resposta->toPsrResponse()->withBody(Utils::streamFor($corpo))->withoutHeader('Content-Length'));
    }
}
