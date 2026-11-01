<?php

namespace App\Services\Omr;

use App\Models\Cartao;
use App\Models\CartaoEnvio;
use App\Models\Usuario;
use App\Support\Escopo;
use App\Support\Espacos;
use App\Support\ContextoCorrecao;
use Illuminate\Support\Facades\DB;

class ResultadoEnvio
{
    public static function concluir(CartaoEnvio $envio, Cartao $cartao): void
    {
        $cartao->refresh();
        if (Espacos::ativo()) abort_unless(ContextoCorrecao::cartaoValido($cartao) && (int) $cartao->espaco_id === (int) $envio->espaco_id && (int) $envio->cartao_id === (int) $cartao->id, 409);
        $cartao->load('aluno:id,nome');
        $nota = ContextoCorrecao::filtrar(DB::table('notas'), $cartao->espaco_id)->where('cartao_id', $cartao->id)->first();
        $envio->update(['status' => 'concluido', 'mensagem' => null, 'resultado' => [
            'id' => $cartao->id, 'prova_id' => $cartao->prova_id, 'status' => $cartao->status,
            'aluno_id' => $cartao->aluno_id, 'aluno_nome' => $cartao->aluno?->nome,
            'lingua' => $cartao->lingua, 'acertos' => $nota->acertos ?? null,
            'total' => $nota->total_questoes ?? null, 'nota_final' => $nota->nota_final ?? null,
            'ambiguas' => ContextoCorrecao::filtrar(DB::table('cartao_respostas'), $cartao->espaco_id)->where('cartao_id', $cartao->id)->where('ambigua', true)->count(),
            'prova_corrigida' => (int) $cartao->prova_id !== (int) $envio->prova_id ? $cartao->prova->disciplina : null,
        ]]);
    }

    public static function resposta(CartaoEnvio $envio, Usuario $usuario): array
    {
        $envio->exigirContexto($usuario);
        if (Espacos::ativo()) {
            $prova = Escopo::prova($usuario, $envio->prova_id);
            abort_unless((int) $envio->usuario_id === (int) $usuario->id && $prova && (int) $prova->espaco_id === (int) $envio->espaco_id, 404);
            if ($envio->cartao_id) {
                $cartao = ContextoCorrecao::cartoes($usuario)->find($envio->cartao_id);
                abort_unless($cartao && ContextoCorrecao::cartaoValido($cartao) && (int) $cartao->espaco_id === (int) $envio->espaco_id, 404);
                $r = $envio->resultado;
                if ($r) abort_unless((int) ($r['id'] ?? 0) === (int) $cartao->id && (int) ($r['prova_id'] ?? 0) === (int) $cartao->prova_id && (int) ($r['aluno_id'] ?? 0) === (int) $cartao->aluno_id, 404);
            }
        }
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

        return ['envio_id' => $envio->chave_cliente ?? $envio->chave, 'processamento' => $envio->status, 'pode_repetir' => $envio->podeRepetir(),
            'mensagem' => $envio->mensagem, 'resultado' => $resultado];
    }
}
