<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabla de Planes de Suscripción del SaaS (Módulo 14)
        if (! Schema::hasTable('plans')) {
            Schema::create('plans', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->decimal('price', 10, 2)->default(0.00);
                $table->string('billing_cycle', 20)->default('monthly'); // monthly, yearly
                $table->unsignedInteger('trial_days')->default(0);
                $table->unsignedInteger('max_users')->nullable(); // null = ilimitado
                $table->unsignedInteger('max_products')->nullable(); // null = ilimitado
                $table->unsignedInteger('max_cash_registers')->nullable(); // null = ilimitado
                $table->jsonb('features')->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }

        // 2. Tabla de Suscripciones por Empresa (Tenants)
        if (! Schema::hasTable('subscriptions')) {
            Schema::create('subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
                $table->foreignId('plan_id')->constrained('plans')->restrictOnDelete();
                $table->string('status', 30)->default('active'); // trialing, active, pending, past_due, cancelled, suspended
                $table->string('billing_cycle', 20)->default('monthly');
                $table->decimal('price', 10, 2)->default(0.00);
                $table->timestamp('starts_at')->useCurrent();
                $table->timestamp('ends_at')->nullable();
                $table->timestamp('trial_ends_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->string('payment_provider', 50)->default('manual');
                $table->string('provider_subscription_id', 150)->nullable();
                $table->string('provider_customer_id', 150)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['business_id', 'status']);
                $table->index('provider_subscription_id');
            });
        }

        // 3. Tabla para Registro e Idempotencia de Webhooks de Pago
        if (! Schema::hasTable('subscription_webhook_events')) {
            Schema::create('subscription_webhook_events', function (Blueprint $table) {
                $table->id();
                $table->string('provider', 50);
                $table->string('event_id', 150)->unique();
                $table->string('event_type', 100);
                $table->jsonb('payload')->nullable();
                $table->string('status', 30)->default('processed');
                $table->text('error_message')->nullable();
                $table->timestamp('processed_at')->useCurrent();
                $table->timestamps();

                $table->index(['provider', 'event_type']);
            });
        }

        // 4. Sembrar Planes Predefinidos de la Plataforma PuntoÁgil
        $plansCount = DB::table('plans')->count();
        if ($plansCount === 0) {
            $starterPlanId = DB::table('plans')->insertGetId([
                'name' => 'Emprendedor / Básico',
                'slug' => 'emprendedor',
                'description' => 'Ideal para pequeños negocios, ferreterías familiares y puestos de mostrador.',
                'price' => 15.00,
                'billing_cycle' => 'monthly',
                'trial_days' => 14,
                'max_users' => 2,
                'max_products' => 150,
                'max_cash_registers' => 1,
                'features' => json_encode([
                    'pos_sales' => true,
                    'inventory_basic' => true,
                    'reports_basic' => true,
                    'export_csv' => true,
                    'export_pdf' => true,
                    'multi_cash_registers' => false,
                    'advanced_reports' => false,
                ]),
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $proPlanId = DB::table('plans')->insertGetId([
                'name' => 'Profesional / Crecimiento',
                'slug' => 'profesional',
                'description' => 'Para comercios en expansión con múltiples cajeros y control exhaustivo de inventario.',
                'price' => 35.00,
                'billing_cycle' => 'monthly',
                'trial_days' => 14,
                'max_users' => 6,
                'max_products' => 1500,
                'max_cash_registers' => 3,
                'features' => json_encode([
                    'pos_sales' => true,
                    'inventory_basic' => true,
                    'reports_basic' => true,
                    'export_csv' => true,
                    'export_pdf' => true,
                    'multi_cash_registers' => true,
                    'advanced_reports' => true,
                    'sale_returns' => true,
                    'supplier_purchases' => true,
                ]),
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $enterprisePlanId = DB::table('plans')->insertGetId([
                'name' => 'Empresarial / Ilimitado',
                'slug' => 'empresarial',
                'description' => 'Control total corporativo con usuarios, productos y cajas registradoras ilimitadas.',
                'price' => 79.00,
                'billing_cycle' => 'monthly',
                'trial_days' => 30,
                'max_users' => null, // ilimitado
                'max_products' => null, // ilimitado
                'max_cash_registers' => null, // ilimitado
                'features' => json_encode([
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
                ]),
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Asignar suscripción Pro/Empresarial por defecto a las empresas existentes sin suscripción
            $businesses = DB::table('businesses')->get();
            foreach ($businesses as $b) {
                DB::table('subscriptions')->insert([
                    'business_id' => $b->id,
                    'plan_id' => $proPlanId,
                    'status' => 'active',
                    'billing_cycle' => 'monthly',
                    'price' => 35.00,
                    'starts_at' => now(),
                    'ends_at' => now()->addYear(),
                    'trial_ends_at' => null,
                    'payment_provider' => 'manual',
                    'notes' => 'Suscripción inicial asignada en migración de fase 4',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_webhook_events');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plans');
    }
};
