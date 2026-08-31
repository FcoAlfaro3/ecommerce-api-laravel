<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PayOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La autorización (solo el dueño de la orden) se valida mediante
        // OrderPolicy dentro del controlador.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Opcional: un método de pago de prueba de Stripe (ej. "pm_card_visa")
            // para confirmar el PaymentIntent directamente desde la API/Swagger.
            // Si se omite, el endpoint solo sincroniza el estado ya confirmado
            // en el frontend con Stripe.
            'payment_method' => ['sometimes', 'nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'payment_method.string' => 'El método de pago debe ser una cadena de texto válida.',
        ];
    }
}
