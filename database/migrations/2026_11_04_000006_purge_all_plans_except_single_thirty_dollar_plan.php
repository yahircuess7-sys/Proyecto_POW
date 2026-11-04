<?php

use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Elimina todos los planes existentes y deja exclusivamente el Plan Oficial de $30.00 USD.
     */
    public function up(): void
    {
        $features = [
            'pos_sales' => true,
            'inventory_basic' => true,
            'reports_basic' => true,
            'export_csv' => true,
            'export_pdf' => true,
            'multi_cash_registers' => true,
            'advanced_reports' => true,
            'sale_returns' => true,
            'supplier_purchases' => true,
            'priority_support' => true,
            'audit_extended' => true,
            'purchases' => true,
            'returns' => true,
            'reports_export' => true,
        ];

        // 1. Obtener o crear el Plan Único Oficial de $30.00
        $officialPlan = Plan::where('slug', 'puntoagil')->first();

        if (! $officialPlan) {
            $officialPlan = Plan::create([
                'name' => 'Plan PuntoÁgil',
                'slug' => 'puntoagil',
                'description' => 'Acceso integral y completo a todos los módulos: POS, Inventario, Compras, Facturación, Devoluciones, Cajas y Reportes.',
                'price' => 30.00,
                'billing_cycle' => 'monthly',
                'trial_days' => 14,
                'max_users' => null, // Ilimitado
                'max_products' => null, // Ilimitado
                'max_cash_registers' => null, // Ilimitado
                'features' => $features,
                'is_active' => true,
                'sort_order' => 1,
            ]);
        } else {
            $officialPlan->update([
                'name' => 'Plan PuntoÁgil',
                'description' => 'Acceso integral y completo a todos los módulos: POS, Inventario, Compras, Facturación, Devoluciones, Cajas y Reportes.',
                'price' => 30.00,
                'billing_cycle' => 'monthly',
                'trial_days' => 14,
                'max_users' => null,
                'max_products' => null,
                'max_cash_registers' => null,
                'features' => $features,
                'is_active' => true,
                'sort_order' => 1,
            ]);
        }

        // 2. Apuntar todas las suscripciones de empresas al Plan Oficial
        DB::table('subscriptions')->update([
            'plan_id' => $officialPlan->id,
            'price' => 30.00,
            'billing_cycle' => 'monthly',
            'updated_at' => now(),
        ]);

        // 3. Eliminar físicamente todos los demás planes
        DB::table('plans')->where('id', '!=', $officialPlan->id)->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No destructivo
    }
};
