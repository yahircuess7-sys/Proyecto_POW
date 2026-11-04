<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Aplica el modelo de suscripción de Plan Único de $30.00 / mes para PuntoÁgil.
     */
    public function up(): void
    {
        // 1. Desactivar planes previos con otros importes
        DB::table('plans')->update(['is_active' => false]);

        $features = json_encode([
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
        ]);

        // 2. Crear o actualizar el Plan Único de $30 USD
        $existingPlan = DB::table('plans')->where('slug', 'puntoagil')->first();

        if ($existingPlan) {
            DB::table('plans')->where('id', $existingPlan->id)->update([
                'name' => 'Plan PuntoÁgil',
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
                'updated_at' => now(),
            ]);
            $singlePlanId = $existingPlan->id;
        } else {
            $singlePlanId = DB::table('plans')->insertGetId([
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
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 3. Migrar todas las suscripciones de empresas a la tarifa única de $30.00
        DB::table('subscriptions')->update([
            'plan_id' => $singlePlanId,
            'price' => 30.00,
            'billing_cycle' => 'monthly',
            'updated_at' => now(),
        ]);
    }

    /**
     * Revierte los cambios reactivando los planes anteriores.
     */
    public function down(): void
    {
        DB::table('plans')->whereIn('slug', ['emprendedor', 'profesional', 'empresarial'])->update(['is_active' => true]);
    }
};
