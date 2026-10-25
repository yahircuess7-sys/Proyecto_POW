<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Añadir columnas complementarias a la tabla products
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'barcode')) {
                $table->string('barcode', 100)->nullable()->after('sku');
            }
            if (! Schema::hasColumn('products', 'cost')) {
                $table->decimal('cost', 10, 2)->default(0.00)->after('description');
            }
            if (! Schema::hasColumn('products', 'min_stock')) {
                $table->integer('min_stock')->default(5)->after('stock');
            }
            if (! Schema::hasColumn('products', 'status')) {
                $table->string('status', 20)->default('active')->after('min_stock');
            }
        });

        // Índices en products para búsqueda por código de barras y estado
        Schema::table('products', function (Blueprint $table) {
            $table->index(['business_id', 'barcode'], 'products_business_barcode_idx');
            $table->index(['business_id', 'status'], 'products_business_status_idx');
        });

        // Asegurar valores iniciales consistentes en productos existentes
        DB::table('products')->whereNull('cost')->update(['cost' => 0.00]);
        DB::table('products')->whereNull('min_stock')->update(['min_stock' => 5]);
        DB::table('products')->whereNull('status')->update(['status' => 'active']);

        // 2. Crear tabla de trazabilidad de inventario (inventory_movements)
        if (! Schema::hasTable('inventory_movements')) {
            Schema::create('inventory_movements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('type', 30); // 'initial', 'in', 'out', 'adjustment', 'sale'
                $table->integer('quantity'); // Positivo o negativo según la dirección del cambio
                $table->integer('previous_stock');
                $table->integer('new_stock');
                $table->decimal('unit_cost', 10, 2)->nullable();
                $table->string('reason', 255);
                $table->string('reference_type', 100)->nullable();
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->timestamps();

                $table->index(['business_id', 'product_id', 'created_at'], 'inv_mov_biz_prod_created_idx');
                $table->index(['business_id', 'type'], 'inv_mov_biz_type_idx');
                $table->index(['business_id', 'user_id'], 'inv_mov_biz_user_idx');
                $table->index(['reference_type', 'reference_id'], 'inv_mov_ref_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_business_barcode_idx');
            $table->dropIndex('products_business_status_idx');

            if (Schema::hasColumn('products', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('products', 'min_stock')) {
                $table->dropColumn('min_stock');
            }
            if (Schema::hasColumn('products', 'cost')) {
                $table->dropColumn('cost');
            }
            if (Schema::hasColumn('products', 'barcode')) {
                $table->dropColumn('barcode');
            }
        });
    }
};
