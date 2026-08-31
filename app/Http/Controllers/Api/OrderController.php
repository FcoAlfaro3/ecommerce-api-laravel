<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PayOrderRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\CardException;
use Stripe\PaymentIntent;

class OrderController extends Controller
{
    public function __construct(protected StripeService $stripeService) {}

    #[OA\Get(
        path: '/api/orders',
        tags: ['Órdenes'],
        summary: 'Historial de compras del usuario autenticado',
        description: 'Devuelve las órdenes del cliente autenticado, ordenadas de la más reciente a la más antigua.',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Listado paginado de órdenes del cliente'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $orders = $request->user()
            ->orders()
            ->with(['items', 'payment'])
            ->latest()
            ->paginate(10);

        return OrderResource::collection($orders);
    }

    #[OA\Get(
        path: '/api/orders/{order}',
        tags: ['Órdenes'],
        summary: 'Detalle de una orden propia',
        description: 'Devuelve el detalle completo (ítems y pago) de una orden que pertenezca al cliente autenticado.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'order', in: 'path', required: true, description: 'ID de la orden', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detalle de la orden'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'La orden pertenece a otro cliente'),
            new OA\Response(response: 404, description: 'Orden no encontrada'),
        ]
    )]
    public function show(Request $request, Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        return response()->json([
            'order' => new OrderResource($order->load(['items', 'payment'])),
        ]);
    }

    #[OA\Post(
        path: '/api/orders',
        tags: ['Órdenes'],
        summary: 'Crear una orden de compra',
        description: 'Crea una orden a partir de una lista de productos y cantidades. Valida el stock disponible, descuenta el inventario, guarda una fotografía (snapshot) del nombre y precio de cada producto, y crea un PaymentIntent en Stripe por el total de la orden. La respuesta incluye el "client_secret" necesario para confirmar el pago desde un frontend, o puede confirmarse directamente desde el endpoint POST /api/orders/{order}/confirm-payment.',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['items'],
                properties: [
                    new OA\Property(
                        property: 'items',
                        type: 'array',
                        items: new OA\Items(properties: [
                            new OA\Property(property: 'product_id', type: 'integer', example: 1),
                            new OA\Property(property: 'quantity', type: 'integer', example: 2),
                        ])
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Orden creada exitosamente, incluye client_secret de Stripe'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 422, description: 'Error de validación o stock insuficiente'),
            new OA\Response(response: 502, description: 'Error al comunicarse con Stripe'),
        ]
    )]
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $order = DB::transaction(function () use ($request) {
            $items = $request->validated('items');
            $total = 0;
            $lockedProducts = [];

            // Se bloquean las filas de productos (lockForUpdate) para evitar
            // condiciones de carrera si dos compras del mismo producto
            // ocurren al mismo tiempo.
            foreach ($items as $item) {
                $product = Product::query()->lockForUpdate()->findOrFail($item['product_id']);

                if (! $product->hasStockFor((int) $item['quantity'])) {
                    throw ValidationException::withMessages([
                        'items' => "Stock insuficiente para el producto \"{$product->name}\" (disponible: {$product->stock}).",
                    ]);
                }

                $lockedProducts[] = ['product' => $product, 'quantity' => (int) $item['quantity']];
                $total += $product->price * $item['quantity'];
            }

            $order = Order::create([
                'user_id' => $request->user()->id,
                'status' => Order::STATUS_PENDING,
                'total' => $total,
            ]);

            foreach ($lockedProducts as $entry) {
                /** @var Product $product */
                $product = $entry['product'];
                $quantity = $entry['quantity'];

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $quantity,
                    'unit_price' => $product->price,
                    'subtotal' => $product->price * $quantity,
                ]);

                $product->decrement('stock', $quantity);
            }

            return $order;
        });

        try {
            $paymentIntent = $this->stripeService->createPaymentIntent(
                amount: (float) $order->total,
                currency: 'usd',
                metadata: ['order_id' => (string) $order->id],
            );
        } catch (ApiErrorException $e) {
            report($e);

            return response()->json([
                'message' => 'La orden fue creada, pero no fue posible iniciar el pago con Stripe. Intente confirmarla nuevamente más tarde.',
                'order' => new OrderResource($order->load('items')),
            ], 502);
        }

        $order->payment()->create([
            'stripe_payment_intent_id' => $paymentIntent->id,
            'amount' => $order->total,
            'currency' => 'usd',
            'status' => Payment::STATUS_PENDING,
        ]);

        $order->load(['items', 'payment']);

        // Se añade client_secret solo en esta respuesta puntual (no forma
        // parte del recurso Order en general): es lo que el frontend
        // (Stripe.js / SDK móvil) necesita para confirmar el pago.
        $orderData = (new OrderResource($order))->resolve();
        $orderData['client_secret'] = $paymentIntent->client_secret;

        return response()->json([
            'message' => 'Orden creada exitosamente. Complete el pago con el client_secret proporcionado.',
            'order' => $orderData,
        ], 201);
    }

    #[OA\Post(
        path: '/api/orders/{order}/confirm-payment',
        tags: ['Órdenes', 'Pagos'],
        summary: 'Confirmar o sincronizar el pago de una orden',
        description: 'Si se envía "payment_method" (ej. un token de prueba de Stripe como "pm_card_visa"), confirma el PaymentIntent directamente desde la API — útil para probar el flujo completo desde Swagger UI sin un frontend. Si se omite, simplemente sincroniza el estado local con el estado actual del PaymentIntent en Stripe (útil cuando la confirmación ya ocurrió en el cliente vía Stripe.js).',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'order', in: 'path', required: true, description: 'ID de la orden', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'payment_method', type: 'string', example: 'pm_card_visa', description: 'Token de método de pago de prueba de Stripe. Opcional.'),
            ])
        ),
        responses: [
            new OA\Response(response: 200, description: 'Pago confirmado o sincronizado exitosamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 402, description: 'El pago fue rechazado por Stripe'),
            new OA\Response(response: 403, description: 'La orden pertenece a otro cliente'),
            new OA\Response(response: 404, description: 'Orden o pago no encontrado'),
            new OA\Response(response: 422, description: 'La orden ya fue procesada previamente'),
            new OA\Response(response: 502, description: 'Error al comunicarse con Stripe'),
        ]
    )]
    public function confirmPayment(PayOrderRequest $request, Order $order): JsonResponse
    {
        $this->authorize('pay', $order);

        $payment = $order->payment;

        if (! $payment) {
            return response()->json([
                'message' => 'Esta orden no tiene un pago asociado.',
            ], 404);
        }

        if (! $order->isPending()) {
            return response()->json([
                'message' => "Esta orden ya fue procesada (estado actual: {$order->status}).",
            ], 422);
        }

        $paymentMethod = $request->validated('payment_method');

        try {
            $paymentIntent = $paymentMethod
                ? $this->stripeService->confirmPaymentIntent($payment->stripe_payment_intent_id, $paymentMethod)
                : $this->stripeService->retrievePaymentIntent($payment->stripe_payment_intent_id);
        } catch (CardException $e) {
            $payment->update(['status' => Payment::STATUS_FAILED]);
            $order->update(['status' => Order::STATUS_FAILED]);

            return response()->json([
                'message' => 'El pago fue rechazado: '.$e->getError()->message,
            ], 402);
        } catch (ApiErrorException $e) {
            report($e);

            return response()->json([
                'message' => 'Error al comunicarse con Stripe.',
            ], 502);
        }

        $this->syncPaymentStatus($order, $payment, $paymentIntent);

        return response()->json([
            'message' => $paymentIntent->status === 'succeeded'
                ? 'Pago confirmado exitosamente.'
                : "El pago aún no se ha completado (estado en Stripe: {$paymentIntent->status}).",
            'order' => new OrderResource($order->fresh(['items', 'payment'])),
        ]);
    }

    /**
     * Refleja el estado de un PaymentIntent de Stripe en los registros
     * locales de payment y order.
     */
    protected function syncPaymentStatus(Order $order, Payment $payment, PaymentIntent $paymentIntent): void
    {
        $status = match ($paymentIntent->status) {
            'succeeded' => Payment::STATUS_SUCCEEDED,
            'canceled' => Payment::STATUS_FAILED,
            default => Payment::STATUS_PENDING,
        };

        $payment->update([
            'status' => $status,
            'stripe_charge_id' => $paymentIntent->latest_charge ?: $payment->stripe_charge_id,
        ]);

        if ($status === Payment::STATUS_SUCCEEDED) {
            $order->update(['status' => Order::STATUS_PAID]);
        } elseif ($status === Payment::STATUS_FAILED) {
            $order->update(['status' => Order::STATUS_FAILED]);
        }
    }
}
