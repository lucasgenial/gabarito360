<?php

namespace App\Services;

use App\Models\Assinatura;
use App\Models\Plano;
use App\Models\Usuario;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Client\PreApproval\PreApprovalClient;
use MercadoPago\Client\PreApprovalPlan\PreApprovalPlanClient;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\MercadoPagoConfig;

class MercadoPagoService
{
    public function __construct()
    {
        MercadoPagoConfig::setAccessToken(config('mercadopago.access_token'));

        if (config('mercadopago.sandbox')) {
            MercadoPagoConfig::setRuntimeEnviroment(MercadoPagoConfig::LOCAL);
        }
    }

    /**
     * Cria uma preferência de checkout (usado para planos anuais — pagamento único).
     * Retorna a URL de checkout gerada pelo Mercado Pago.
     */
    public function criarPreferenciaCheckout(Plano $plano, Usuario $usuario, int $licencas): array
    {
        $valor = $plano->valorPara($licencas);

        $client = new PreferenceClient();
        $preference = $client->create([
            'items' => [[
                'id'          => (string) $plano->id,
                'title'       => $plano->nome,
                'quantity'    => 1,
                'unit_price'  => $valor,
                'currency_id' => 'BRL',
            ]],
            'payer' => [
                'email' => $usuario->email,
                'name'  => $usuario->nome,
            ],
            'back_urls'   => config('mercadopago.back_urls'),
            'auto_return' => 'approved',
            'external_reference' => $usuario->id . ':' . $plano->id . ':' . $licencas,
            'notification_url' => config('app.url') . '/api/v1/webhooks/mercadopago',
        ]);

        return [
            'init_point'        => $preference->init_point,
            'sandbox_init_point' => $preference->sandbox_init_point,
            'preference_id'     => $preference->id,
        ];
    }

    /**
     * Cria um plano de pré-aprovação recorrente e uma assinatura no Mercado Pago.
     * Usado para planos mensais (recorrentes). Retorna URL de checkout da assinatura.
     */
    public function criarAssinaturaRecorrente(Plano $plano, Usuario $usuario, int $licencas): array
    {
        $valor = $plano->valorPara($licencas);

        $planClient = new PreApprovalPlanClient();
        $plan = $planClient->create([
            'reason'           => $plano->nome,
            'auto_recurring'   => [
                'frequency'         => 1,
                'frequency_type'    => 'months',
                'transaction_amount' => $valor,
                'currency_id'       => 'BRL',
            ],
            'payment_methods_allowed' => [
                'payment_types' => [
                    ['id' => 'credit_card'],
                    ['id' => 'debit_card'],
                ],
            ],
            'back_url' => config('mercadopago.back_urls.success'),
        ]);

        $subClient = new PreApprovalClient();
        $subscription = $subClient->create([
            'preapproval_plan_id' => $plan->id,
            'reason'              => $plano->nome,
            'payer_email'         => $usuario->email,
            'external_reference'  => $usuario->id . ':' . $plano->id . ':' . $licencas,
            'back_url'            => config('mercadopago.back_urls.success'),
            'notification_url'    => config('app.url') . '/api/v1/webhooks/mercadopago',
        ]);

        return [
            'subscription_id' => $subscription->id,
            'init_point'      => $subscription->init_point,
            'status'          => $subscription->status,
        ];
    }

    /**
     * Cancela uma assinatura recorrente no Mercado Pago.
     */
    public function cancelarAssinatura(string $gatewaySubscriptionId): bool
    {
        try {
            $client = new PreApprovalClient();
            $client->update($gatewaySubscriptionId, ['status' => 'cancelled']);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Busca detalhes de um pagamento pelo ID do gateway.
     */
    public function buscarPagamento(string $gatewayPaymentId): ?object
    {
        try {
            $client = new PaymentClient();
            return $client->get((int) $gatewayPaymentId);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Valida a assinatura do webhook usando o secret configurado.
     * O Mercado Pago envia o header x-signature com o HMAC do payload.
     */
    public function validarWebhook(string $payload, string $signature, string $requestId): bool
    {
        $secret = config('mercadopago.webhook_secret');
        if (empty($secret)) {
            return true; // Em dev sem secret configurado, aceita tudo
        }

        // Formato: ts=<timestamp>,v1=<hash>
        $parts = [];
        foreach (explode(',', $signature) as $part) {
            [$key, $val] = explode('=', $part, 2);
            $parts[$key] = $val;
        }

        if (!isset($parts['ts'], $parts['v1'])) {
            return false;
        }

        $manifest = "id:{$requestId};request-id:{$requestId};ts:{$parts['ts']};";
        $expected  = hash_hmac('sha256', $manifest, $secret);

        return hash_equals($expected, $parts['v1']);
    }
}
