<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Assinatura;
use App\Services\MercadoPagoService;
use Illuminate\Http\Request;

class AssinaturaController extends Controller
{
    /** Contratação assistida: não cria assinatura nem chama o gateway. */
    public function checkout(Request $request)
    {
        return ApiResponse::error('Para contratar, fale com nossa equipe pelo Fale Conosco.', [
            'contato_url' => rtrim(config('emails.url_web'), '/') . '/fale-conosco',
        ], 409);
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
    public function cancelar(Request $request, MercadoPagoService $mp)
    {
        $assinatura = Assinatura::where('titular_id', $request->user()->id)
            ->whereIn('status', ['trial', 'ativa'])
            ->firstOrFail();

        if ($assinatura->gateway_subscription_id && $assinatura->plano->periodicidade === 'mensal') {
            $mp->cancelarAssinatura($assinatura->gateway_subscription_id);
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
