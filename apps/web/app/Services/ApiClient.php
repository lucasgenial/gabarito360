<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class ApiClient
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.api.base_url'), '/');
    }

    private function http(): PendingRequest
    {
        $request = Http::baseUrl($this->baseUrl)
            ->acceptJson()
            ->timeout(15);

        if ($token = Session::get('auth_token')) {
            $request = $request->withToken($token);
        }

        return $request;
    }

    public function post(string $path, array $data = []): Response
    {
        return $this->http()->post($path, $data);
    }

    public function get(string $path, array $query = []): Response
    {
        return $this->http()->get($path, $query);
    }

    public function put(string $path, array $data = []): Response
    {
        return $this->http()->put($path, $data);
    }

    public function delete(string $path): Response
    {
        return $this->http()->delete($path);
    }
}
