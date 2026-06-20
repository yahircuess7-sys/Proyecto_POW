<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Product;

class DatabaseSeeder extends Seeder
{
    /**
     * Inyectar los datos en la base de datos.
     */
    public function run(): void
    {
        // 1. Inyectando Usuarios
        User::create([
            'name' => 'Administrador Yahir',
            'email' => 'admin@eltornillo.com',
            'password' => Hash::make('12345678'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        User::create([
            'name' => 'Cajero Carlos',
            'email' => 'cajero@eltornillo.com',
            'password' => Hash::make('12345678'),
            'role' => 'cajero',
            'status' => 'active',
        ]);

        // 2. Inyectando Productos de Ferretería
        $products = [
            [
                'sku' => 'CEM-001',
                'name' => 'Cemento Cruz Azul 50kg',
                'description' => 'Saco de cemento gris de alta resistencia',
                'category' => 'Materiales de Construcción',
                'price' => 8.50,
                'stock' => 150,
                'unit' => 'Saco'
            ],
            [
                'sku' => 'CLA-002',
                'name' => 'Clavos de Acero 2 pulgadas',
                'description' => 'Caja de clavos estándar para madera',
                'category' => 'Clavería',
                'price' => 2.25,
                'stock' => 50,
                'unit' => 'Caja'
            ],
            [
                'sku' => 'HER-003',
                'name' => 'Martillo Truper 16oz',
                'description' => 'Martillo de uña curva mango de fibra',
                'category' => 'Herramientas',
                'price' => 12.00,
                'stock' => 25,
                'unit' => 'Unidad'
            ],
            [
                'sku' => 'PIN-004',
                'name' => 'Pintura Blanca 1 Galón',
                'description' => 'Pintura acrílica lavable para interiores',
                'category' => 'Pinturas',
                'price' => 15.50,
                'stock' => 30,
                'unit' => 'Galón'
            ]
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}