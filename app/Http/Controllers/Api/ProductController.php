<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

class ProductController extends Controller
{
    #[OA\Get(
        path: '/api/products',
        tags: ['Productos'],
        summary: 'Listado público del catálogo de productos',
        description: 'Devuelve los productos activos, con paginación y búsqueda opcional por nombre o descripción. No requiere autenticación.',
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', required: false, description: 'Texto a buscar en nombre o descripción', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'Cantidad de resultados por página (por defecto 15)', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Listado paginado de productos'),
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $products = Product::query()
            ->active()
            ->search($request->query('search'))
            ->latest()
            ->paginate((int) $request->query('per_page', 15));

        return ProductResource::collection($products);
    }

    #[OA\Get(
        path: '/api/products/{product}',
        tags: ['Productos'],
        summary: 'Detalle de un producto',
        description: 'Devuelve la información completa de un producto por su identificador. No requiere autenticación.',
        parameters: [
            new OA\Parameter(name: 'product', in: 'path', required: true, description: 'ID del producto', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detalle del producto'),
            new OA\Response(response: 404, description: 'Producto no encontrado'),
        ]
    )]
    public function show(Product $product): JsonResponse
    {
        return response()->json([
            'product' => new ProductResource($product),
        ]);
    }

    #[OA\Post(
        path: '/api/products',
        tags: ['Productos'],
        summary: 'Crear un producto',
        description: 'Crea un nuevo producto en el catálogo. Requiere un token de un usuario administrador.',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'price', 'stock'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Laptop HP Pavilion 15'),
                    new OA\Property(property: 'description', type: 'string', example: 'Laptop de 15 pulgadas, 16GB RAM, 512GB SSD'),
                    new OA\Property(property: 'price', type: 'number', format: 'float', example: 699.99),
                    new OA\Property(property: 'stock', type: 'integer', example: 25),
                    new OA\Property(property: 'image_url', type: 'string', example: 'https://ejemplo.com/laptop.jpg'),
                    new OA\Property(property: 'active', type: 'boolean', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Producto creado exitosamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado (se requiere rol de administrador)'),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]
    public function store(StoreProductRequest $request): JsonResponse
    {
        $this->authorize('create', Product::class);

        $product = Product::create($request->validated());

        return response()->json([
            'message' => 'Producto creado exitosamente.',
            'product' => new ProductResource($product),
        ], 201);
    }

    #[OA\Put(
        path: '/api/products/{product}',
        tags: ['Productos'],
        summary: 'Actualizar un producto',
        description: 'Actualiza parcial o totalmente un producto existente. Requiere un token de un usuario administrador.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'product', in: 'path', required: true, description: 'ID del producto', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'name', type: 'string', example: 'Laptop HP Pavilion 15 (2026)'),
                new OA\Property(property: 'description', type: 'string', example: 'Actualización de memoria a 32GB RAM'),
                new OA\Property(property: 'price', type: 'number', format: 'float', example: 749.99),
                new OA\Property(property: 'stock', type: 'integer', example: 18),
                new OA\Property(property: 'image_url', type: 'string', example: 'https://ejemplo.com/laptop.jpg'),
                new OA\Property(property: 'active', type: 'boolean', example: true),
            ])
        ),
        responses: [
            new OA\Response(response: 200, description: 'Producto actualizado exitosamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado (se requiere rol de administrador)'),
            new OA\Response(response: 404, description: 'Producto no encontrado'),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        $product->update($request->validated());

        return response()->json([
            'message' => 'Producto actualizado exitosamente.',
            'product' => new ProductResource($product),
        ]);
    }

    #[OA\Delete(
        path: '/api/products/{product}',
        tags: ['Productos'],
        summary: 'Eliminar un producto',
        description: 'Elimina un producto del catálogo. Requiere un token de un usuario administrador.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'product', in: 'path', required: true, description: 'ID del producto', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Producto eliminado exitosamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado (se requiere rol de administrador)'),
            new OA\Response(response: 404, description: 'Producto no encontrado'),
        ]
    )]
    public function destroy(Product $product): JsonResponse
    {
        $this->authorize('delete', $product);

        $product->delete();

        return response()->json([
            'message' => 'Producto eliminado exitosamente.',
        ]);
    }
}
