<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Cajas físicas (cash_registers)
        if (! Schema::hasTable('cash_registers')) {
            Schema::create('cash_registers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
                $table->string('name', 100);
                $table->string('status', 20)->default('closed'); // open, closed
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['business_id', 'status'], 'cash_reg_biz_status_idx');
            });
        }

        // 2. Turnos / Arqueos de caja (cash_shifts)
        if (! Schema::hasTable('cash_shifts')) {
            Schema::create('cash_shifts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
                $table->foreignId('cash_register_id')->constrained('cash_registers')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('closed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('opened_at');
                $table->timestamp('closed_at')->nullable();
                $table->decimal('initial_cash', 12, 2)->default(0.00);
                $table->decimal('expected_cash', 12, 2)->default(0.00);
                $table->decimal('closing_cash', 12, 2)->nullable();
                $table->decimal('difference', 12, 2)->default(0.00);
                $table->string('status', 20)->default('open'); // open, closed
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['business_id', 'user_id', 'status'], 'cash_shift_biz_user_status_idx');
                $table->index(['business_id', 'status'], 'cash_shift_biz_status_idx');
            });
        }

        // 3. Movimientos de caja (cash_movements - entradas y salidas manuales)
        if (! Schema::hasTable('cash_movements')) {
            Schema::create('cash_movements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
                $table->foreignId('cash_shift_id')->constrained('cash_shifts')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('type', 10); // in, out
                $table->decimal('amount', 12, 2);
                $table->string('reason', 255);
                $table->timestamps();

                $table->index(['business_id', 'cash_shift_id'], 'cash_mov_biz_shift_idx');
            });
        }

        // 4. Extensión de sales para POS avanzado, caja y devoluciones
        Schema::table('sales', function (Blueprint $table) {
            if (! Schema::hasColumn('sales', 'cash_shift_id')) {
                $table->foreignId('cash_shift_id')->nullable()->after('user_id')->constrained('cash_shifts')->nullOnDelete();
            }
            if (! Schema::hasColumn('sales', 'payment_method')) {
                $table->string('payment_method', 50)->default('cash')->after('customer_document');
            }
            if (! Schema::hasColumn('sales', 'discount')) {
                $table->decimal('discount', 10, 2)->default(0.00)->after('subtotal');
            }
            if (! Schema::hasColumn('sales', 'paid_amount')) {
                $table->decimal('paid_amount', 10, 2)->nullable()->after('total');
            }
            if (! Schema::hasColumn('sales', 'change_amount')) {
                $table->decimal('change_amount', 10, 2)->default(0.00)->after('paid_amount');
            }
            if (! Schema::hasColumn('sales', 'status')) {
                $table->string('status', 20)->default('completed')->after('change_amount'); // completed, partially_refunded, refunded, cancelled
            }
            if (! Schema::hasColumn('sales', 'idempotency_key')) {
                $table->string('idempotency_key', 100)->nullable()->after('status');
            }
        });

        // 5. Extensión de sale_items para trazabilidad de costo, descuento y devolución
        Schema::table('sale_items', function (Blueprint $table) {
            if (! Schema::hasColumn('sale_items', 'unit_cost')) {
                $table->decimal('unit_cost', 10, 2)->default(0.00)->after('unit_price');
            }
            if (! Schema::hasColumn('sale_items', 'discount')) {
                $table->decimal('discount', 10, 2)->default(0.00)->after('unit_cost');
            }
            if (! Schema::hasColumn('sale_items', 'refunded_quantity')) {
                $table->integer('refunded_quantity')->default(0)->after('quantity');
            }
        });

        // 6. Devoluciones de venta (sale_returns)
        if (! Schema::hasTable('sale_returns')) {
            Schema::create('sale_returns', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
                $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('cash_shift_id')->nullable()->constrained('cash_shifts')->nullOnDelete();
                $table->string('return_number', 50);
                $table->decimal('total_refunded', 10, 2);
                $table->string('refund_method', 50)->default('cash'); // cash, credit_note, card_reversal
                $table->text('reason');
                $table->timestamps();

                $table->unique(['business_id', 'return_number'], 'sale_ret_biz_number_unique');
                $table->index(['business_id', 'sale_id'], 'sale_ret_biz_sale_idx');
            });
        }

        // 7. Ítems de devolución (sale_return_items)
        if (! Schema::hasTable('sale_return_items')) {
            Schema::create('sale_return_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
                $table->foreignId('sale_return_id')->constrained('sale_returns')->cascadeOnDelete();
                $table->foreignId('sale_item_id')->constrained('sale_items')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->integer('quantity');
                $table->decimal('unit_price', 10, 2);
                $table->decimal('subtotal', 10, 2);
                $table->timestamps();

                $table->index(['business_id', 'sale_return_id'], 'sale_ret_items_ret_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_return_items');
        Schema::dropIfExists('sale_returns');

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['unit_cost', 'discount', 'refunded_quantity']);
        });

        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'cash_shift_id')) {
                $table->dropForeign(['cash_shift_id']);
                $table->dropColumn('cash_shift_id');
            }
            $table->dropColumn([
                'payment_method',
                'discount',
                'paid_amount',
                'change_amount',
                'status',
                'idempotency_key',
            ]);
        });

        Schema::dropIfExists('cash_movements');
        Schema::dropIfExists('cash_shifts');
        Schema::dropIfExists('cash_registers');
    }
};
