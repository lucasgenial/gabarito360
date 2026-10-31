<?php

namespace App\Services\Omr;

use App\Jobs\ProcessarCartaoJob;
use App\Models\Cartao;
use App\Models\CartaoEnvio;
use App\Models\Usuario;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class FilaCartoes
{
    public function receber(Usuario $usuario, int $provaId, string $chave, UploadedFile $foto, ?int $alunoId): CartaoEnvio
    {
        return Cache::lock("cartao-envio:{$usuario->id}:{$chave}", 30)->block(3, function () use ($usuario, $provaId, $chave, $foto, $alunoId) {
            $existente = CartaoEnvio::where('usuario_id', $usuario->id)->where('chave', $chave)->first();
            if ($existente) {
                abort_if((int) $existente->prova_id !== $provaId, 409, 'Este identificador já foi usado em outra prova.');

                return $existente;
            }
            $caminho = $foto->store("cartoes/{$provaId}", 'public');
            if (!$caminho) {
                throw new \RuntimeException('Não foi possível salvar a foto do cartão.');
            }
            try {
                $envio = DB::transaction(function () use ($usuario, $provaId, $chave, $caminho, $alunoId) {
                    $cartao = Cartao::create(['prova_id' => $provaId, 'aluno_id' => $alunoId,
                        'imagem_url' => $caminho, 'status' => 'pendente']);

                    return CartaoEnvio::create(['usuario_id' => $usuario->id, 'prova_id' => $provaId,
                        'chave' => $chave, 'cartao_id' => $cartao->id, 'status' => 'na_fila', 'tentativa' => 1]);
                });
            } catch (Throwable $e) {
                Storage::disk('public')->delete($caminho);
                throw $e;
            }

            return $this->publicar($envio);
        });
    }

    public function repetir(CartaoEnvio $envio): CartaoEnvio
    {
        return Cache::lock("cartao-envio:{$envio->usuario_id}:{$envio->chave}", 30)->block(3, function () use ($envio) {
            $novo = DB::transaction(function () use ($envio) {
                $atual = CartaoEnvio::whereKey($envio->id)->lockForUpdate()->firstOrFail();
                if (! $atual->podeRepetir()) {
                    return null;
                }
                $atual->update(['status' => 'na_fila', 'mensagem' => null, 'resultado' => null,
                    'tentativa' => $atual->tentativa + 1]);

                return $atual;
            });

            return $novo ? $this->publicar($novo) : $envio->fresh();
        });
    }

    private function publicar(CartaoEnvio $envio): CartaoEnvio
    {
        try {
            // Mesmo em desenvolvimento com QUEUE_CONNECTION=sync, a leitura não ocupa a API.
            $conexao = config('queue.default') === 'sync' ? 'redis' : config('queue.default');
            if (! in_array(config("queue.connections.{$conexao}.driver"), ['redis', 'database', 'sqs', 'beanstalkd'], true)) {
                throw new \RuntimeException('A leitura de cartões exige uma fila persistente.');
            }
            Bus::dispatch((new ProcessarCartaoJob($envio->cartao_id, $envio->id, $envio->tentativa))
                ->onConnection($conexao)->onQueue('alta'));
        } catch (Throwable $e) {
            CartaoEnvio::whereKey($envio->id)->where('tentativa', $envio->tentativa)->where('status', 'na_fila')
                ->update(['status' => 'erro', 'mensagem' => 'Não foi possível entrar na fila. Tente novamente.']);
            report($e);
        }

        return $envio->fresh();
    }
}
