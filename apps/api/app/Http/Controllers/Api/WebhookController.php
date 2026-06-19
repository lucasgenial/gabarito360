<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assinatura;
use App\Models\Pagamento;
use App\Services\MercadoPagoService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(private MercadoPagoService $mp) {}

    /**
     * Endpoint de webhook para eventos do Mercado Pago.
     * Deve ser isento de autenticação Sanctum.
     */
    public function mercadoPago(Request $request): Response
    {
        $signature = $request->header('x-signature', '');
        $requestId = $request->header('x-request-id', '');
        $payload   = $request->getContent();

        if (!$this->mp->validarWebhook($payload, $signature, $requestId)) {
            Log::warning('MP Webhook: assinatura inválida', ['request_id' => $requestId]);
            return response('Unauthorized', 401);
        }

        $data = $request->json()->all();
        $tipo = $data['type'] ?? $data['action'] ?? null;

        Log::info('MP Webhook recebido', ['type' => $tipo, 'data' => $data]);

        match ($tipo) {
            'payment'             => $this->processarPagamento($data),
            'preapproval'         => $this->processarPreapproval($data),
            'payment.created',
            'payment.updated'     => $this->processarPagamento($data),
            default               => null,
        };

        return response('OK', 200);
    }

    private function processarPagamento(array $data): void
    {
        $paymentId = (string) ($data['data']['id'] ?? $data['id'] ?? null);
        if (!$paymentId) {
            return;
        }

        $pagamentoMP = $this->mp->buscarPagamento($paymentId);
        if (!$pagamentoMP) {
            return;
        }

        $externalRef = $pagamentoMP->external_reference ?? null;
        if (!$externalRef) {
            return;
        }

        // external_reference = "usuario_id:plano_id:licencas"
        [$usuarioId] = explode(':', $externalRef);

        $assinatura = Assinatura::where('titular_id', $usuarioId)
            ->whereIn('status', ['trial', 'ativa', 'atrasada'])
            ->latest()
            ->first();

        if (!$assinatura) {
            return;
        }

        $statusMP = $pagamentoMP->status ?? 'pending';
        $metodo   = $this->resolverMetodo($pagamentoMP->payment_type_id ?? '');

        $pagamento = Pagamento::firstOrCreate(
            ['gateway_payment_id' => $paymentId],
            [
                'assinatura_id' => $assinatura->id,
                'valor'         => $pagamentoMP->transaction_amount ?? 0,
                'status'        => 'pendente',
                'metodo'        => $metodo,
                'created_at'    => now(),
            ]
        );

        $novoStatus = match ($statusMP) {
            'approved'   => 'aprovado',
            'rejected'   => 'rejeitado',
            'refunded'   => 'reembolsado',
            default      => 'pendente',
        };

        $pagamento->update([
            'status'  => $novoStatus,
            'pago_em' => $statusMP === 'approved' ? Carbon::parse($pagamentoMP->date_approved) : null,
        ]);

        if ($statusMP === 'approved') {
            $proxima = $assinatura->plano->periodicidade === 'anual'
                ? Carbon::now()->addYear()
                : Carbon::now()->addMonth();

            $assinatura->update([
                'status'               => 'ativa',
                'proxima_cobranca_em'  => $proxima,
            ]);
        }
    }

    private function processarPreapproval(array $data): void
    {
        $subscriptionId = (string) ($data['data']['id'] ?? null);
        if (!$subscriptionId) {
            return;
        }

        $statusMP = $data['status'] ?? null;

        $assinatura = Assinatura::where('gateway_subscription_id', $subscriptionId)->first();
        if (!$assinatura) {
            return;
        }

        if ($statusMP === 'cancelled') {
            $assinatura->update(['status' => 'cancelada']);
        }
    }

    private function resolverMetodo(string $tipoMP): string
    {
        return match ($tipoMP) {
            'bank_transfer'  => 'pix',
            'ticket'         => 'boleto',
            'credit_card',
            'debit_card'     => 'cartao',
            default          => 'cartao',
        };
    }
}
