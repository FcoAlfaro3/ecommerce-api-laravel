<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Catálogo de zapatería: tenis, zapatos formales y botas.
     * Las imágenes son fotos gratuitas de Unsplash.
     */
    public function run(): void
    {
        $img = fn (string $id) => "https://images.unsplash.com/{$id}?auto=format&fit=crop&w=1000&q=80";

        $products = [
            // Tenis
            [
                'name' => 'Tenis Runner Rojo',
                'description' => 'Tenis para correr con malla transpirable, suela con amortiguación y diseño ligero para entrenar todos los días.',
                'price' => 74.99,
                'stock' => 30,
                'image_url' => $img('photo-1542291026-7eec264c27ff'),
            ],
            [
                'name' => 'Tenis Running Blanco y Naranja',
                'description' => 'Tenis deportivos blancos con detalles naranja, suela flexible y talón reforzado.',
                'price' => 79.99,
                'stock' => 18,
                'image_url' => $img('photo-1560769629-975ec94e6a86'),
            ],
            [
                'name' => 'Tenis Casual Café',
                'description' => 'Tenis de estilo casual en color café, cómodos para caminar y fáciles de combinar con jeans.',
                'price' => 54.99,
                'stock' => 22,
                'image_url' => $img('photo-1549298916-b41d501d3772'),
            ],
            [
                'name' => 'Tenis Verde Olivo',
                'description' => 'Tenis casuales en verde olivo con suela blanca de goma, ligeros y resistentes.',
                'price' => 49.99,
                'stock' => 3,
                'image_url' => $img('photo-1539185441755-769473a23570'),
            ],
            [
                'name' => 'Tenis Blanco Perforado',
                'description' => 'Tenis blanco con paneles laterales perforados que dejan respirar el pie. Limpio y versátil.',
                'price' => 64.99,
                'stock' => 25,
                'image_url' => $img('photo-1608231387042-66d1773070a5'),
            ],
            [
                'name' => 'Tenis Blanco Clásico Bajo',
                'description' => 'El clásico tenis blanco de corte bajo, todo en un color, para usar con cualquier ropa.',
                'price' => 89.99,
                'stock' => 35,
                'image_url' => $img('photo-1579338559194-a162d19bf842'),
            ],
            [
                'name' => 'Tenis Negro de Cuero',
                'description' => 'Tenis de cuero negro con detalle blanco en el costado y suela cómoda para todo el día.',
                'price' => 84.99,
                'stock' => 20,
                'image_url' => $img('photo-1543508282-6319a3e2621f'),
            ],

            // Zapatos formales
            [
                'name' => 'Zapato Formal Negro',
                'description' => 'Zapato de vestir negro con acabado pulido, ideal para la oficina y eventos formales.',
                'price' => 109.99,
                'stock' => 15,
                'image_url' => $img('photo-1668069226492-508742b03147'),
            ],
            [
                'name' => 'Mocasín de Cuero Café',
                'description' => 'Mocasín café para caballero, sin agujetas, de cuero suave y suela cómoda.',
                'price' => 99.99,
                'stock' => 12,
                'image_url' => $img('photo-1533867617858-e7b97e060509'),
            ],
            [
                'name' => 'Zapato de Vestir Café',
                'description' => 'Zapato de vestir en cuero café brillante con agujetas, para trajes y ocasiones especiales.',
                'price' => 119.99,
                'stock' => 10,
                'image_url' => $img('photo-1563434649554-58f91d22ec2c'),
            ],

            // Botas
            [
                'name' => 'Bota de Cuero Café con Agujetas',
                'description' => 'Bota de cuero café con agujetas, suela gruesa y buen agarre para uso diario.',
                'price' => 139.99,
                'stock' => 10,
                'image_url' => $img('photo-1608256246200-53e635b5b65f'),
            ],
            [
                'name' => 'Bota de Cuero Negra',
                'description' => 'Bota negra de cuero con acabado pulido, fácil de combinar con ropa casual o formal.',
                'price' => 149.99,
                'stock' => 8,
                'image_url' => $img('photo-1605732440685-d0654d81aa30'),
            ],
        ];

        foreach ($products as $product) {
            Product::create($product + ['active' => true]);
        }
    }
}
