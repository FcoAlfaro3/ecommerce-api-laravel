<?php

namespace App\Services;

use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\PaymentIntent;
use Stripe\StripeClient;
use Stripe\Webhook;

/**
 * Encapsula toda la interacción con la API de Stripe para que los
 * controladores no dependan directamente del SDK.
 */
class StripeService
{
    protected StripeClient $client;

    public function __construct()
    {
        $this->client = new StripeClient((string) config('services.stripe.secret'));
    }

    /**
     * Crea un PaymentIntent por el monto de una orden (en la moneda indicada).
     * No se confirma automáticamente: queda a la espera de que el frontend
     * (Stripe.js / SDK móvil) o el endpoint de confirmación manual lo complete.
     *
     * @param  array<string, string>  $metadata
     */
    public function createPaymentIntent(float $amount, string $currency, array $metadata = []): PaymentIntent
    {
        return $this->client->paymentIntents->create([
            'amount' => $this->toCents($amount),
            'currency' => $currency,
            'payment_method_types' => ['card'],
            'metadata' => $metadata,
        ]);
    }

    /**
     * Confirma un PaymentIntent existente usando un método de pago de Stripe
     * (por ejemplo, un token de prueba como "pm_card_visa"). Permite probar
     * el flujo de pago completo directamente desde Swagger UI sin depender
     * de un frontend con Stripe.js.
     */
    public function confirmPaymentIntent(string $paymentIntentId, string $paymentMethod): PaymentIntent
    {
        return $this->client->paymentIntents->confirm($paymentIntentId, [
            'payment_method' => $paymentMethod,
        ]);
    }

    /**
     * Recupera el estado actual de un PaymentIntent desde Stripe (útil para
     * sincronizar el estado local cuando la confirmación ocurrió en el cliente).
     */
    public function retrievePaymentIntent(string $paymentIntentId): PaymentIntent
    {
        return $this->client->paymentIntents->retrieve($paymentIntentId);
    }

    /**
     * Verifica la firma de un evento entrante del webhook de Stripe y lo
     * convierte en un objeto Event.
     *
     * @throws SignatureVerificationException
     */
    public function constructWebhookEvent(string $payload, string $signatureHeader): Event
    {
        return Webhook::constructEvent(
            $payload,
            $signatureHeader,
            (string) config('services.stripe.webhook_secret')
        );
    }

    /**
     * Stripe trabaja los montos en la unidad mínima de la moneda (centavos
     * para USD). Convierte un monto decimal (ej. 49.99) a centavos (4999).
     */
    protected function toCents(float $amount): int
    {
        return (int) round($amount * 100);
    }
}
