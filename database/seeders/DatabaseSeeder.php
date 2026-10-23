<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Inyectar los datos en la base de datos de manera idempotente y multiempresa.
     */
    public function run(): void
    {
        // 1. Inyectando Empresa Matriz
        $business = Business::firstOrCreate(
            ['slug' => 'puntoagil-central'],
            [
                'name' => 'PuntoÁgil Central',
                'tax_id' => '0999999999001',
                'email' => 'admin@puntoagil.com',
                'phone' => '+593 99 000 0000',
                'address' => 'Sede Principal - Matriz',
                'status' => 'active',
                'settings' => [
                    'currency' => 'USD',
                    'tax_percentage' => 12,
                    'timezone' => 'America/Guayaquil',
                ],
            ]
        );

        // 2. Inyectando Usuarios y Membresías
        $admin = User::firstOrCreate(
            ['email' => 'admin@puntoagil.com'],
            [
                'name' => 'Administrador Yahir',
                'password' => Hash::make('12345678'),
                'role' => 'admin',
                'status' => 'active',
                'current_business_id' => $business->id,
            ]
        );

        if (! $admin->businesses()->where('business_id', $business->id)->exists()) {
            $admin->businesses()->attach($business->id, [
                'role' => 'owner',
                'is_default' => true,
            ]);
        }

        $cajero = User::firstOrCreate(
            ['email' => 'cajero@puntoagil.com'],
            [
                'name' => 'Cajero Carlos',
                'password' => Hash::make('12345678'),
                'role' => 'cajero',
                'status' => 'active',
                'current_business_id' => $business->id,
            ]
        );

        if (! $cajero->businesses()->where('business_id', $business->id)->exists()) {
            $cajero->businesses()->attach($business->id, [
                'role' => 'cajero',
                'is_default' => true,
            ]);
        }

        // 3. Inyectando Productos Iniciales
        $products = [
            [
                'sku' => 'CEM-001',
                'name' => 'Cemento Cruz Azul 50kg',
                'description' => 'Saco de cemento gris de alta resistencia',
                'category' => 'Materiales de Construcción',
                'price' => 8.50,
                'stock' => 150,
                'unit' => 'Saco',
            ],
            [
                'sku' => 'CLA-002',
                'name' => 'Clavos de Acero 2 pulgadas',
                'description' => 'Caja de clavos estándar para madera',
                'category' => 'Clavería',
                'price' => 2.25,
                'stock' => 50,
                'unit' => 'Caja',
            ],
            [
                'sku' => 'HER-003',
                'name' => 'Martillo Truper 16oz',
                'description' => 'Martillo de uña curva mango de fibra',
                'category' => 'Herramientas',
                'price' => 12.00,
                'stock' => 25,
                'unit' => 'Unidad',
            ],
            [
                'sku' => 'PIN-004',
                'name' => 'Pintura Blanca 1 Galón',
                'description' => 'Pintura acrílica lavable para interiores',
                'category' => 'Pinturas',
                'price' => 15.50,
                'stock' => 30,
                'unit' => 'Galón',
            ],
        ];

        foreach ($products as $p) {
            Product::withoutBusinessScope()->firstOrCreate(
                ['business_id' => $business->id, 'sku' => $p['sku']],
                array_merge($p, ['business_id' => $business->id])
            );
        }
    }
}
