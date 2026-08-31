<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'name' => 'Laptop HP Pavilion 15',
                'description' => 'Laptop de 15.6", Intel Core i5, 16GB RAM, 512GB SSD.',
                'price' => 699.99,
                'stock' => 25,
                'image_url' => 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853',
            ],
            [
                'name' => 'Mouse Inalámbrico Logitech M170',
                'description' => 'Mouse óptico inalámbrico con conexión USB, 1000 DPI.',
                'price' => 14.99,
                'stock' => 120,
                'image_url' => 'https://images.unsplash.com/photo-1527864550417-7fd91fc51a46',
            ],
            [
                'name' => 'Teclado Mecánico RGB',
                'description' => 'Teclado mecánico con switches azules e iluminación RGB personalizable.',
                'price' => 49.99,
                'stock' => 60,
                'image_url' => 'https://images.unsplash.com/photo-1587829741301-dc798b83add3',
            ],
            [
                'name' => 'Monitor Samsung 24" Full HD',
                'description' => 'Monitor LED de 24 pulgadas, resolución 1920x1080, 75Hz.',
                'price' => 129.99,
                'stock' => 40,
                'image_url' => 'https://images.unsplash.com/photo-1527443224154-c4a3942d3acf',
            ],
            [
                'name' => 'Audífonos Bluetooth Sony WH-CH520',
                'description' => 'Audífonos inalámbricos con hasta 50 horas de batería.',
                'price' => 59.99,
                'stock' => 80,
                'image_url' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e',
            ],
            [
                'name' => 'Disco SSD NVMe 1TB',
                'description' => 'Unidad de estado sólido NVMe con velocidades de lectura de hasta 3500MB/s.',
                'price' => 79.99,
                'stock' => 70,
                'image_url' => 'https://images.unsplash.com/photo-1591405351990-4726e331f141',
            ],
            [
                'name' => 'Webcam Full HD 1080p',
                'description' => 'Cámara web con micrófono integrado, ideal para videollamadas.',
                'price' => 34.99,
                'stock' => 55,
                'image_url' => 'https://images.unsplash.com/photo-1587826080692-f439465e2f13',
            ],
            [
                'name' => 'Silla Ergonómica de Oficina',
                'description' => 'Silla ergonómica con soporte lumbar ajustable y reposabrazos.',
                'price' => 189.99,
                'stock' => 15,
                'image_url' => 'https://images.unsplash.com/photo-1580480055273-228ff5388ef8',
            ],
            [
                'name' => 'Impresora Multifuncional HP DeskJet',
                'description' => 'Impresora, escáner y copiadora a color con conexión Wi-Fi.',
                'price' => 89.99,
                'stock' => 30,
                'image_url' => 'https://images.unsplash.com/photo-1612815154858-60aa4c59eaa6',
            ],
            [
                'name' => 'Power Bank 20000mAh',
                'description' => 'Batería portátil de carga rápida con doble puerto USB.',
                'price' => 24.99,
                'stock' => 100,
                'image_url' => 'https://images.unsplash.com/photo-1609091839311-d5365f9ff1c5',
            ],
            [
                'name' => 'Smartwatch Deportivo',
                'description' => 'Reloj inteligente con monitor de ritmo cardíaco y GPS.',
                'price' => 99.99,
                'stock' => 45,
                'image_url' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30',
            ],
            [
                'name' => 'Router Wi-Fi 6 Dual Band',
                'description' => 'Router de alta velocidad con cobertura de hasta 150m².',
                'price' => 69.99,
                'stock' => 35,
                'image_url' => 'https://images.unsplash.com/photo-1544197150-b99a580bb7a8',
            ],
        ];

        foreach ($products as $product) {
            Product::create($product + ['active' => true]);
        }
    }
}
