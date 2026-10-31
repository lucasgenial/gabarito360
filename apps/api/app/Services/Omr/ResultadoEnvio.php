<?php

namespace App\Services\Omr;

use App\Models\Cartao;
use App\Models\CartaoEnvio;
use App\Models\Usuario;
use App\Support\Escopo;
use Illuminate\Support\Facades\DB;

class ResultadoEnvio
{
    public static function concluir(CartaoEnvio $envio, Cartao $cartao): void
    {
        $cartao->refresh()->load('aluno:id,nome');
        $nota = DB::table('notas')->where('cartao_id', $cartao->id)->first();
        $envio->update(['status' => 'concluido', 'mensagem' => null, 'resultado' => [
            'id' => $cartao->id, 'prova_id' => $cartao->prova_id, 'status' => $cartao->status,
            'aluno_id' => $cartao->aluno_id, 'aluno_nome' => $cartao->aluno?->nome,
            'lingua' => $cartao->lingua, 'acertos' => $nota->acertos ?? null,
            'total' => $nota->total_questoes ?? null, 'nota_final' => $nota->nota_final ?? null,
            'ambiguas' => DB::table('cartao_respostas')->where('cartao_id', $cartao->id)->where('ambigua', true)->count(),
            'prova_corrigida' => (int) $cartao->prova_id !== (int) $envio->prova_id ? $cartao->prova->disciplina : null,
        ]]);
    }

    public static function resposta(CartaoEnvio $envio, Usuario $usuario): array
    {
        // Inclui recibos antigos cuja FK foi anulada por uma exclusão em cascata.
        if ($envio->status !== 'substituido' &&
            (! $envio->cartao_id || ! Cartao::whereKey($envio->cartao_id)->exists())) {
            $envio->update(['status' => 'recusado', 'resultado' => null,
                'mensagem' => 'O cartão foi excluído. Fotografe novamente.']);
        }
        $resultado = $envio->resultado ?? [];
        // Vale também para a resposta de um upload repetido e de uma retentativa.
        if (isset($resultado['prova_id']) && !Escopo::prova($usuario, $resultado['prova_id'])) {
            abort(404, 'Envio não encontrado.');
        }
        if ($usuario->perfil === 'aplicador') {
            $resultado = array_intersect_key($resultado, array_flip(['id', 'status', 'aluno_id', 'aluno_nome']));
        }

        return ['envio_id' => $envio->chave, 'processamento' => $envio->status, 'pode_repetir' => $envio->podeRepetir(),
            'mensagem' => $envio->mensagem, 'resultado' => $resultado];
    }
}
