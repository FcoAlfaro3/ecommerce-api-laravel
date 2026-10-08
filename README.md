# API E-commerce Segura con Swagger Completo

API RESTful construida con **Laravel 12** para una zapatería en línea: registro de clientes, catálogo de productos (tenis, zapatos formales y botas) y procesamiento de compras mediante **Stripe**. Incluye autenticación por tokens (Laravel Sanctum), documentación interactiva con **Swagger / OpenAPI** y manejo consistente de errores en formato JSON.

## Stack técnico

- PHP 8.2+
- Laravel 12
- MySQL
- Laravel Sanctum (autenticación por token)
- `darkaonline/l5-swagger` (documentación OpenAPI 3, con atributos PHP `#[OA\...]`)
- `stripe/stripe-php` (pasarela de pago)

## Requisitos previos

- PHP >= 8.2 con las extensiones `pdo_mysql`, `openssl`, `mbstring`, `curl`, `json`, `zip`, `fileinfo`
- Composer 2
- MySQL 8 o MariaDB (por ejemplo, con XAMPP)
- Una cuenta de Stripe en modo *test* (gratuita) para las llaves de la pasarela de pago

## Instalación

### 1. Clonar el repositorio e instalar dependencias

```bash
git clone https://github.com/<usuario>/<repositorio>.git ecommerce-api
cd ecommerce-api
composer install
```

### 2. Configurar el entorno

```bash
cp .env.example .env
php artisan key:generate
```

En Windows (CMD) el primer comando es `copy .env.example .env`.

Edita `.env` y configura:

- **Base de datos:** `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` según tu instalación de MySQL.
- **Stripe:** `STRIPE_KEY` y `STRIPE_SECRET` con tus llaves de prueba, disponibles en el [Dashboard de Stripe](https://dashboard.stripe.com/test/apikeys).

Crea la base de datos:

```sql
CREATE DATABASE ecommerce_api CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 3. Migraciones y seeders

```bash
php artisan migrate --seed
```

Esto crea las tablas (`users`, `products`, `orders`, `order_items`, `payments` y `personal_access_tokens`) y las puebla con:

- Un usuario **administrador**: `admin@example.com` / `password123` (puede crear, editar y eliminar productos)
- Un usuario **cliente**: `cliente@example.com` / `password123`
- 12 productos del catálogo de zapatería

Para reiniciar la base de datos con los datos iniciales: `php artisan migrate:fresh --seed`.

### 4. Generar la documentación Swagger

```bash
php artisan l5-swagger:generate
```

Con `L5_SWAGGER_GENERATE_ALWAYS=true` (incluido en `.env.example`) la documentación se regenera automáticamente en cada petición durante el desarrollo, por lo que este paso solo es necesario para producción o si se desactiva esa opción.

### 5. Levantar el servidor

```bash
php artisan serve
```

- API disponible en: `http://127.0.0.1:8000/api`
- Documentación Swagger UI en: **`http://127.0.0.1:8000/api/documentation`**

## Frontend

Esta API es consumida por una tienda en Next.js 16 (App Router), que implementa el catálogo, la autenticación con cookie httpOnly, el carrito, el checkout con Stripe y el historial de compras.

## Autenticación

La API usa **tokens personales de Laravel Sanctum**. Tras registrarse (`POST /api/register`) o iniciar sesión (`POST /api/login`) se recibe un token que debe enviarse en cada petición protegida:

```
Authorization: Bearer {token}
```

En Swagger UI: haz clic en **Authorize** e ingresa `Bearer {token}`.

## Endpoints principales

| Método | Endpoint                              | Auth | Rol requerido | Descripción                                    |
|--------|----------------------------------------|:----:|:--------------:|-------------------------------------------------|
| POST   | `/api/register`                       | No   | -              | Registrar un nuevo cliente                       |
| POST   | `/api/login`                          | No   | -              | Iniciar sesión y obtener token                   |
| POST   | `/api/logout`                         | Sí   | Cualquiera     | Cerrar sesión (revoca el token actual)           |
| GET    | `/api/me`                             | Sí   | Cualquiera     | Datos del usuario autenticado                    |
| GET    | `/api/products`                       | No   | -              | Listado público de productos (`search`, `per_page`) |
| GET    | `/api/products/{product}`             | No   | -              | Detalle de un producto                           |
| POST   | `/api/products`                       | Sí   | Administrador  | Crear producto                                   |
| PUT    | `/api/products/{product}`             | Sí   | Administrador  | Actualizar producto                              |
| DELETE | `/api/products/{product}`             | Sí   | Administrador  | Eliminar producto                                |
| GET    | `/api/orders`                         | Sí   | Cualquiera     | Historial de compras del usuario autenticado     |
| POST   | `/api/orders`                         | Sí   | Cualquiera     | Crear una orden y su PaymentIntent en Stripe     |
| GET    | `/api/orders/{order}`                 | Sí   | Dueño          | Detalle de una orden propia                      |
| POST   | `/api/orders/{order}/confirm-payment` | Sí   | Dueño          | Confirmar o sincronizar el pago con Stripe       |
| POST   | `/api/stripe/webhook`                 | No (firma verificada) | -  | Webhook de Stripe                       |

## Flujo de compra con Stripe

1. El cliente se registra o inicia sesión y obtiene un token.
2. El cliente consulta el catálogo (`GET /api/products`).
3. El cliente crea una orden:

   ```json
   POST /api/orders
   {
     "items": [
       { "product_id": 1, "quantity": 2 },
       { "product_id": 3, "quantity": 1 }
     ]
   }
   ```

   El servidor, dentro de una transacción de base de datos: valida el stock disponible, bloquea las filas de producto involucradas (`lockForUpdate`) para evitar condiciones de carrera, crea la orden y sus ítems (guardando una fotografía del nombre y precio de cada producto), descuenta el stock, y crea un `PaymentIntent` en Stripe por el total. La respuesta incluye la orden y el `client_secret` necesario para completar el pago desde un frontend (Stripe.js / Elements / SDK móvil).

4. **Completar el pago**, dos formas posibles:

   - **Desde un frontend real:** usar el `client_secret` con Stripe.js/Elements o el SDK móvil de Stripe.
   - **Para probar directamente desde Swagger UI**, sin necesidad de un frontend:

     ```json
     POST /api/orders/{order}/confirm-payment
     { "payment_method": "pm_card_visa" }
     ```

     Esto confirma el `PaymentIntent` server-side usando un [método de pago de prueba de Stripe](https://docs.stripe.com/testing#cards) y actualiza el estado de la orden y del pago inmediatamente. Tarjetas de prueba útiles:

     | Token de prueba          | Resultado                     |
     |---------------------------|--------------------------------|
     | `pm_card_visa`             | Pago exitoso                   |
     | `pm_card_chargeDeclined`   | Pago rechazado (402)           |

5. **Sincronización automática en producción:** configura un endpoint de webhook en el [Dashboard de Stripe](https://dashboard.stripe.com/test/webhooks) apuntando a `POST /api/stripe/webhook`, con los eventos `payment_intent.succeeded` y `payment_intent.payment_failed`. Copia el "Signing secret" en `STRIPE_WEBHOOK_SECRET`. Para probar webhooks en local sin exponer tu máquina, usa el [Stripe CLI](https://docs.stripe.com/stripe-cli):

   ```bash
   stripe listen --forward-to localhost:8000/api/stripe/webhook
   ```

## Manejo de errores

Todas las respuestas de error siguen un formato JSON consistente, definido centralmente en `bootstrap/app.php`:

```json
{
  "message": "Los datos proporcionados no son válidos.",
  "errors": { "email": ["El correo electrónico ya está registrado."] }
}
```

| Código | Caso                                                     |
|--------|-----------------------------------------------------------|
| 401    | No autenticado                                             |
| 402    | Pago rechazado por Stripe                                  |
| 403    | Acción no autorizada (ej. producto sin rol admin, orden ajena) |
| 404    | Recurso no encontrado                                       |
| 422    | Error de validación / stock insuficiente / orden ya procesada |
| 502    | Error al comunicarse con Stripe                             |
| 500    | Error interno del servidor                                  |

## Base de datos

| Tabla         | Descripción                                                                 |
|---------------|-------------------------------------------------------------------------------|
| `users`       | Clientes registrados (incluye `is_admin` para la gestión del catálogo)       |
| `products`    | Catálogo de productos                                                        |
| `orders`      | Órdenes de compra (`pending`, `paid`, `failed`, `cancelled`)                 |
| `order_items` | Detalle de productos por orden, con snapshot de nombre y precio de compra    |
| `payments`    | Registro de transacciones de Stripe (`PaymentIntent`) asociadas a cada orden |

## Decisiones de arquitectura

- **Autenticación:** Laravel Sanctum (tokens personales), por ser el estándar de Laravel para APIs consumidas por SPA/móvil sin la complejidad de OAuth2 de Passport.
- **Autorización:** `ProductPolicy` y `OrderPolicy` (descubiertas por convención) separan la autorización de la validación de datos, que queda en los Form Requests.
- **Snapshot de OrderItem:** se guarda `product_name` y `unit_price` al momento de la compra para que el historial no cambie si el producto se edita o elimina después.
- **PaymentIntent sin confirmación automática:** se crea en estado pendiente al generar la orden, y se confirma por separado (vía frontend, vía `confirm-payment`, o vía webhook), reflejando el flujo real recomendado por Stripe.
- **Bloqueo de filas (`lockForUpdate`)** al descontar stock, para evitar sobreventa si dos compras concurrentes afectan el mismo producto.

## Pruebas manuales rápidas

```bash
# Registro
curl -X POST http://127.0.0.1:8000/api/register \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"name":"Jane Doe","email":"jane@example.com","password":"secret123","password_confirmation":"secret123"}'

# Listar productos
curl http://127.0.0.1:8000/api/products

# Crear una orden (reemplaza TOKEN por el token recibido al registrarte)
curl -X POST http://127.0.0.1:8000/api/orders \
  -H "Authorization: Bearer TOKEN" -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"items":[{"product_id":1,"quantity":2}]}'

# Confirmar el pago (reemplaza ORDER_ID)
curl -X POST http://127.0.0.1:8000/api/orders/ORDER_ID/confirm-payment \
  -H "Authorization: Bearer TOKEN" -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"payment_method":"pm_card_visa"}'
```
