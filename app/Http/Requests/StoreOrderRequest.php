<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Cualquier cliente autenticado puede crear una orden (auth:sanctum
        // ya se aplica a nivel de ruta).
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => 'Debe incluir al menos un producto en la orden.',
            'items.array' => 'El formato de los productos no es válido.',
            'items.min' => 'Debe incluir al menos un producto en la orden.',
            'items.*.product_id.required' => 'Cada ítem debe especificar un producto.',
            'items.*.product_id.integer' => 'El identificador del producto no es válido.',
            'items.*.product_id.exists' => 'Uno de los productos seleccionados no existe.',
            'items.*.quantity.required' => 'Cada ítem debe especificar una cantidad.',
            'items.*.quantity.integer' => 'La cantidad debe ser un número entero.',
            'items.*.quantity.min' => 'La cantidad debe ser al menos 1.',
        ];
    }
}
