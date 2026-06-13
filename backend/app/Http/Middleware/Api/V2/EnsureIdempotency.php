<?php

namespace App\Http\Middleware\Api\V2;

use App\Models\IdempotencyKey;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIdempotency
{
    private const MUTATING_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    /**
     * Garante idempotencia para requisicoes mutadoras que enviam o header
     * `Idempotency-Key`. Em B0 nenhum recurso mutador existe ainda em
     * /api/v2; a obrigatoriedade por endpoint (captura, confirmacao,
     * importacao, exportacao) e aplicada a partir de B1.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($request->method(), self::MUTATING_METHODS, true)) {
            return $next($request);
        }

        $chave = $request->header('Idempotency-Key');

        if (! is_string($chave) || trim($chave) === '') {
            return $next($request);
        }

        $chave = trim($chave);
        $usuarioId = $request->user()?->getKey();
        $metodo = $request->method();
        $rota = $request->route()?->uri() ?? $request->path();

        $existente = IdempotencyKey::query()
            ->where('chave', $chave)
            ->where('metodo', $metodo)
            ->where('rota', $rota)
            ->when(
                $usuarioId !== null,
                fn ($query) => $query->where('usuario_id', $usuarioId),
                fn ($query) => $query->whereNull('usuario_id'),
            )
            ->first();

        if ($existente !== null) {
            if ($existente->expira_em->isPast()) {
                $existente->delete();
            } elseif ($existente->status === 'concluido') {
                return $this->reproduzir($existente);
            } else {
                return ApiResponse::error(
                    code: 'CONFLICT',
                    message: 'Requisicao com a mesma chave de idempotencia ja esta em processamento.',
                    status: 409,
                );
            }
        }

        $registro = IdempotencyKey::create([
            'usuario_id' => $usuarioId,
            'chave' => $chave,
            'metodo' => $metodo,
            'rota' => $rota,
            'request_hash' => hash('sha256', $request->getContent() ?: ''),
            'status' => 'processando',
            'expira_em' => now()->addHours((int) config('gabarito360.idempotency.ttl_hours', 24)),
        ]);

        $response = $next($request);

        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            $registro->update([
                'status' => 'concluido',
                'resposta_status' => $response->getStatusCode(),
                'resposta_corpo' => $this->decodificarCorpo($response),
                'resposta_headers' => $this->headersRelevantes($response),
            ]);
        } else {
            $registro->delete();
        }

        return $response;
    }

    private function reproduzir(IdempotencyKey $registro): Response
    {
        return response()->json(
            $registro->resposta_corpo,
            $registro->resposta_status ?? 200,
            $registro->resposta_headers ?? [],
        );
    }

    private function decodificarCorpo(Response $response): mixed
    {
        $conteudo = $response->getContent();

        if ($conteudo === false || $conteudo === '') {
            return null;
        }

        $decodificado = json_decode($conteudo, true);

        return json_last_error() === JSON_ERROR_NONE ? $decodificado : null;
    }

    /**
     * @return array<string, string>
     */
    private function headersRelevantes(Response $response): array
    {
        $headers = [];

        if ($response->headers->has('Location')) {
            $headers['Location'] = (string) $response->headers->get('Location');
        }

        return $headers;
    }
}
