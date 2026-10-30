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

    public function postFile(string $path, string $fileField, \Illuminate\Http\UploadedFile $file, array $data = []): Response
    {
        // Upload de cartão roda o OMR de forma síncrona na API: dá mais tempo.
        return $this->http()->timeout(90)
            ->attach($fileField, $file->get(), $file->getClientOriginalName())
            ->post($path, $data);
    }

    /** GET que retorna arquivo (PDF etc.) — timeout maior para geração. */
    public function getArquivo(string $path, array $query = []): Response
    {
        return $this->http()->timeout(180)->get($path, $query);
    }
}
