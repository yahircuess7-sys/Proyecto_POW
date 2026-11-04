<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabla de Clientes (customers)
        if (! Schema::hasTable('customers')) {
            Schema::create('customers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
                $table->string('document_type', 30)->default('DNI'); // DNI, Cédula, RUC, RFC, Pasaporte
                $table->string('document_number', 50);
                $table->string('name');
                $table->string('email')->nullable();
                $table->string('phone', 50)->nullable();
                $table->string('address', 500)->nullable();
                $table->string('status', 20)->default('active'); // active, inactive
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['business_id', 'document_number'], 'customers_business_doc_unique');
                $table->index(['business_id', 'status'], 'customers_biz_status_idx');
                $table->index(['business_id', 'name'], 'customers_biz_name_idx');
            });
        }

        // 2. Tabla de Proveedores (suppliers)
        if (! Schema::hasTable('suppliers')) {
            Schema::create('suppliers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
                $table->string('tax_id', 50); // RUC, RFC, NIT, Cédula fiscal
                $table->string('name');
                $table->string('contact_name')->nullable();
                $table->string('email')->nullable();
                $table->string('phone', 50)->nullable();
                $table->string('address', 500)->nullable();
                $table->string('status', 20)->default('active'); // active, inactive
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['business_id', 'tax_id'], 'suppliers_business_tax_unique');
                $table->index(['business_id', 'status'], 'suppliers_biz_status_idx');
                $table->index(['business_id', 'name'], 'suppliers_biz_name_idx');
            });
        }

        // 3. Vincular Clientes a Ventas (sales)
        Schema::table('sales', function (Blueprint $table) {
            if (! Schema::hasColumn('sales', 'customer_id')) {
                $table->foreignId('customer_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('customers')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('sales', 'customer_name')) {
                $table->string('customer_name')->nullable()->after('customer_id');
            }
            if (! Schema::hasColumn('sales', 'customer_document')) {
                $table->string('customer_document', 50)->nullable()->after('customer_name');
            }
        });

        // 4. Vincular Proveedores a Productos (opcional: proveedor preferido)
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'supplier_id')) {
                $table->foreignId('supplier_id')
                    ->nullable()
                    ->after('business_id')
                    ->constrained('suppliers')
                    ->nullOnDelete();
            }
        });

        // 5. Tabla de Compras a Proveedores (purchases)
        if (! Schema::hasTable('purchases')) {
            Schema::create('purchases', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
                $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('purchase_number', 50);
                $table->string('supplier_invoice_number', 100)->nullable();
                $table->date('purchase_date');
                $table->decimal('subtotal', 10, 2)->default(0.00);
                $table->decimal('tax', 10, 2)->default(0.00);
                $table->decimal('total', 10, 2)->default(0.00);
                $table->string('status', 20)->default('completed'); // completed, pending, cancelled
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['business_id', 'purchase_number'], 'purchases_biz_number_unique');
                $table->index(['business_id', 'supplier_id'], 'purchases_biz_supplier_idx');
                $table->index(['business_id', 'status'], 'purchases_biz_status_idx');
                $table->index(['business_id', 'purchase_date'], 'purchases_biz_date_idx');
            });
        }

        // 6. Tabla de Ítems de Compra (purchase_items)
        if (! Schema::hasTable('purchase_items')) {
            Schema::create('purchase_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
                $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->integer('quantity');
                $table->decimal('unit_cost', 10, 2);
                $table->decimal('subtotal', 10, 2);
                $table->timestamps();

                $table->index(['business_id', 'purchase_id'], 'purchase_items_biz_purchase_idx');
                $table->index(['business_id', 'product_id'], 'purchase_items_biz_product_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'supplier_id')) {
                $table->dropForeign(['supplier_id']);
                $table->dropColumn('supplier_id');
            }
        });

        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'customer_id')) {
                $table->dropForeign(['customer_id']);
                $table->dropColumn(['customer_id', 'customer_name', 'customer_document']);
            }
        });

        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('customers');
    }
};
