<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Stripe\Exception\SignatureVerificationException;
use UnexpectedValueException;

/**
 * Recibe las notificaciones que Stripe envía directamente a este servidor
 * cuando el estado de un PaymentIntent cambia. Es la forma recomendada en
 * producción de mantener sincronizados los pagos (alternativa al endpoint
 * manual /api/orders/{order}/confirm-payment, pensado para desarrollo local).
 */
class StripeWebhookController extends Controller
{
    public function __construct(protected StripeService $stripeService) {}

    #[OA\Post(
        path: '/api/stripe/webhook',
        tags: ['Pagos'],
        summary: 'Webhook de Stripe (uso exclusivo de Stripe)',
        description: 'Endpoint invocado directamente por Stripe para notificar eventos de pago (payment_intent.succeeded, payment_intent.payment_failed). La firma de la petición se verifica con STRIPE_WEBHOOK_SECRET. No requiere autenticación de usuario, pero sí una firma válida de Stripe. No debe invocarse manualmente.',
        responses: [
            new OA\Response(response: 200, description: 'Evento procesado correctamente'),
            new OA\Response(response: 400, description: 'Firma de webhook inválida o payload malformado'),
        ]
    )]
    public function handle(Request $request): JsonResponse
    {
        try {
            $event = $this->stripeService->constructWebhookEvent(
                $request->getContent(),
                (string) $request->header('Stripe-Signature')
            );
        } catch (SignatureVerificationException|UnexpectedValueException $e) {
            report($e);

            return response()->json([
                'message' => 'Firma de webhook inválida.',
            ], 400);
        }

        $paymentIntent = $event->data->object;

        $payment = Payment::where('stripe_payment_intent_id', $paymentIntent->id)->first();

        if (! $payment) {
            // El evento no corresponde a ningún pago registrado localmente;
            // se responde 200 igualmente para que Stripe no reintente el envío.
            return response()->json(['message' => 'Evento recibido: sin orden asociada.']);
        }

        match ($event->type) {
            'payment_intent.succeeded' => $this->markAsPaid($payment),
            'payment_intent.payment_failed', 'payment_intent.canceled' => $this->markAsFailed($payment),
            default => null,
        };

        return response()->json(['message' => 'Evento procesado.']);
    }

    protected function markAsPaid(Payment $payment): void
    {
        $payment->update(['status' => Payment::STATUS_SUCCEEDED]);
        $payment->order->update(['status' => Order::STATUS_PAID]);
    }

    protected function markAsFailed(Payment $payment): void
    {
        $payment->update(['status' => Payment::STATUS_FAILED]);
        $payment->order->update(['status' => Order::STATUS_FAILED]);
    }
}
