<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Assinatura;
use App\Models\Plano;
use App\Services\MercadoPagoService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AssinaturaController extends Controller
{
    public function __construct(private MercadoPagoService $mp) {}

    /**
     * Inicia o checkout de uma assinatura (apenas planos autoatendimento).
     * Retorna a URL de pagamento do Mercado Pago.
     */
    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'plano_id'  => 'required|exists:planos,id',
            'licencas'  => 'integer|min:1|max:10',
        ]);

        $plano   = Plano::findOrFail($validated['plano_id']);
        $usuario = $request->user();
        $licencas = $validated['licencas'] ?? 1;

        if (!$plano->isAutoatendimento()) {
            return ApiResponse::error('Este plano requer contato comercial. Use o formulário Fale Conosco.', [], 422);
        }

        if ($plano->publico === 'individual' && $licencas > 1) {
            return ApiResponse::error('Planos individuais suportam apenas 1 licença.', [], 422);
        }

        if ($plano->licencas_max && $licencas > $plano->licencas_max) {
            return ApiResponse::error(
                "Este plano suporta no máximo {$plano->licencas_max} licenças. Para mais licenças, use o formulário Fale Conosco.",
                [],
                422
            );
        }

        $assinaturaExistente = Assinatura::where('titular_id', $usuario->id)
            ->whereIn('status', ['trial', 'ativa'])
            ->first();

        if ($assinaturaExistente) {
            return ApiResponse::error('Você já possui uma assinatura ativa.', [], 422);
        }

        $trialDays = config('mercadopago.trial_days', 14);
        $trialTerminaEm = Carbon::now()->addDays($trialDays);

        $assinatura = Assinatura::create([
            'plano_id'            => $plano->id,
            'titular_id'          => $usuario->id,
            'licencas_contratadas' => $licencas,
            'status'              => 'trial',
            'trial_termina_em'    => $trialTerminaEm,
        ]);

        try {
            if ($plano->periodicidade === 'mensal') {
                $dados = $this->mp->criarAssinaturaRecorrente($plano, $usuario, $licencas);
                $assinatura->update(['gateway_subscription_id' => $dados['subscription_id']]);
            } else {
                $dados = $this->mp->criarPreferenciaCheckout($plano, $usuario, $licencas);
                $assinatura->update(['gateway_subscription_id' => $dados['preference_id']]);
            }

            return ApiResponse::success([
                'assinatura_id' => $assinatura->id,
                'init_point'    => config('mercadopago.sandbox')
                    ? ($dados['sandbox_init_point'] ?? $dados['init_point'])
                    : $dados['init_point'],
                'trial_termina_em' => $trialTerminaEm->toIso8601String(),
            ], null, 201);
        } catch (\Throwable $e) {
            $assinatura->delete();
            return ApiResponse::error('Erro ao criar checkout no Mercado Pago. Tente novamente.', [], 502);
        }
    }

    /**
     * Retorna a assinatura do titular logado.
     */
    public function minha(Request $request)
    {
        $assinatura = Assinatura::where('titular_id', $request->user()->id)
            ->with(['plano', 'pagamentos'])
            ->latest()
            ->first();

        if (!$assinatura) {
            return ApiResponse::notFound('Nenhuma assinatura encontrada.');
        }

        return ApiResponse::success($this->formatarAssinatura($assinatura));
    }

    /**
     * Cancela a assinatura do titular logado.
     */
    public function cancelar(Request $request)
    {
        $assinatura = Assinatura::where('titular_id', $request->user()->id)
            ->whereIn('status', ['trial', 'ativa'])
            ->firstOrFail();

        if ($assinatura->gateway_subscription_id && $assinatura->plano->periodicidade === 'mensal') {
            $this->mp->cancelarAssinatura($assinatura->gateway_subscription_id);
        }

        $assinatura->update(['status' => 'cancelada']);

        return ApiResponse::success(['message' => 'Assinatura cancelada com sucesso.']);
    }

    private function formatarAssinatura(Assinatura $assinatura): array
    {
        return [
            'id'                   => $assinatura->id,
            'status'               => $assinatura->status,
            'licencas_contratadas' => $assinatura->licencas_contratadas,
            'trial_termina_em'     => $assinatura->trial_termina_em?->toIso8601String(),
            'proxima_cobranca_em'  => $assinatura->proxima_cobranca_em?->toIso8601String(),
            'plano'                => [
                'id'                => $assinatura->plano->id,
                'nome'              => $assinatura->plano->nome,
                'periodicidade'     => $assinatura->plano->periodicidade,
                'preco_fixo'        => $assinatura->plano->preco_fixo,
                'preco_por_licenca' => $assinatura->plano->preco_por_licenca,
            ],
            'historico_pagamentos' => $assinatura->pagamentos->map(fn ($p) => [
                'id'                 => $p->id,
                'valor'              => $p->valor,
                'status'             => $p->status,
                'metodo'             => $p->metodo,
                'pago_em'            => $p->pago_em?->toIso8601String(),
                'gateway_payment_id' => $p->gateway_payment_id,
            ]),
        ];
    }
}
