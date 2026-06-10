<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class RequestId
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $this->resolveRequestId($request);

        $request->attributes->set('request_id', $requestId);
        Log::shareContext(['request_id' => $requestId]);

        app()->terminating(static fn () => Log::flushSharedContext());

        $response = $next($request);
        $response->headers->set('X-Request-ID', $requestId);

        return $response;
    }

    private function resolveRequestId(Request $request): string
    {
        $requestId = $request->header('X-Request-ID');

        if (is_string($requestId) && Str::isUuid($requestId)) {
            return $requestId;
        }

        return (string) Str::uuid();
    }
}
