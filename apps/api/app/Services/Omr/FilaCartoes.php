<?php

namespace App\Services\Omr;

use App\Jobs\ProcessarCartaoJob;
use App\Models\Cartao;
use App\Models\CartaoEnvio;
use App\Models\Usuario;
use App\Support\ContextoCorrecao;
use App\Support\Escopo;
use App\Support\Espacos;
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
        $prova = Escopo::prova($usuario, $provaId);
        abort_unless($prova, 404);
        $espaco = $prova->espaco_id;
        if (Espacos::ativo() && $alunoId) abort_unless(ContextoCorrecao::alunos($prova, $usuario)->where('alunos.id', $alunoId)->exists(), 404);
        return Cache::lock("cartao-envio:{$espaco}:{$usuario->id}:{$chave}", 30)->block(3, function () use ($usuario, $provaId, $chave, $foto, $alunoId, $espaco) {
            $existente = CartaoEnvio::where('usuario_id', $usuario->id)->where('chave', $chave)->first();
            if ($existente) {
                if (Espacos::ativo()) abort_unless((int) $existente->espaco_id === (int) $espaco, 409, 'Identificador indisponível. Gere outro envio.');
                abort_if((int) $existente->prova_id !== $provaId, 409, 'Este identificador já foi usado em outra prova.');

                return $existente;
            }
            $caminho = $foto->store(Espacos::ativo() ? "cartoes/espacos/{$espaco}/{$provaId}" : "cartoes/{$provaId}", 'public');
            if (!$caminho) {
                throw new \RuntimeException('Não foi possível salvar a foto do cartão.');
            }
            try {
                $envio = DB::transaction(function () use ($usuario, $provaId, $chave, $caminho, $alunoId, $espaco) {
                    $cartao = new Cartao(['prova_id' => $provaId, 'aluno_id' => $alunoId,
                        'imagem_url' => $caminho, 'status' => 'pendente']);
                    $cartao->forceFill(ContextoCorrecao::autoria($espaco, $usuario->id))->save();

                    return CartaoEnvio::create(['usuario_id' => $usuario->id, 'prova_id' => $provaId,
                        'chave' => $chave, 'cartao_id' => $cartao->id, 'status' => 'na_fila', 'tentativa' => 1] + ContextoCorrecao::autoria($espaco, $usuario->id));
                });
            } catch (Throwable $e) {
                Storage::disk('public')->delete($caminho);
                throw $e;
            }

            return $this->publicar($envio);
        });
    }

    public function repetir(CartaoEnvio $envio, ?Usuario $usuario = null): CartaoEnvio
    {
        if (Espacos::ativo()) {
            abort_unless($usuario && (int) $envio->usuario_id === (int) $usuario->id, 404);
            $prova = Escopo::prova($usuario, $envio->prova_id);
            abort_unless($prova && (int) $prova->espaco_id === (int) $envio->espaco_id, 404);
            $cartao = ContextoCorrecao::cartoes($usuario)->find($envio->cartao_id);
            abort_unless(ContextoCorrecao::cartaoValido($cartao) && (int) $cartao->espaco_id === (int) $envio->espaco_id, 404);
        }
        return Cache::lock("cartao-envio:{$envio->espaco_id}:{$envio->usuario_id}:{$envio->chave}", 30)->block(3, function () use ($envio) {
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
            Bus::dispatch((new ProcessarCartaoJob($envio->cartao_id, $envio->id, $envio->tentativa, $envio->espaco_id, $envio->usuario_id))
                ->onConnection($conexao)->onQueue('alta'));
        } catch (Throwable $e) {
            CartaoEnvio::whereKey($envio->id)->where('tentativa', $envio->tentativa)->where('status', 'na_fila')
                ->update(['status' => 'erro', 'mensagem' => 'Não foi possível entrar na fila. Tente novamente.']);
            report($e);
        }

        return $envio->fresh();
    }
}
