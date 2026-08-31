<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'API E-commerce Segura con Swagger Completo',
    description: 'API RESTful para la gestión de un e-commerce: registro y autenticación de clientes mediante tokens, catálogo de productos, creación de órdenes de compra y procesamiento de pagos con Stripe.',
    contact: new OA\Contact(email: 'soporte@ecommerce-api.test')
)]
#[OA\Server(
    url: 'http://localhost:8000',
    description: 'Servidor local de desarrollo'
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Sanctum Personal Access Token',
    description: 'Token obtenido en /api/register o /api/login. Enviar como: Authorization: Bearer {token}'
)]
#[OA\Tag(name: 'Autenticación', description: 'Registro, inicio de sesión y gestión de la sesión del cliente')]
#[OA\Tag(name: 'Productos', description: 'Catálogo de productos del e-commerce')]
#[OA\Tag(name: 'Órdenes', description: 'Creación y consulta del historial de órdenes de compra')]
#[OA\Tag(name: 'Pagos', description: 'Procesamiento y confirmación de pagos con Stripe')]
abstract class Controller
{
    use AuthorizesRequests;
}
