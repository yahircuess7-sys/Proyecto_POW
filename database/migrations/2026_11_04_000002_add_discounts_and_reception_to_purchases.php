<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            if (! Schema::hasColumn('purchases', 'discount')) {
                $table->decimal('discount', 10, 2)->default(0.00)->after('subtotal');
            }
            if (! Schema::hasColumn('purchases', 'received_at')) {
                $table->timestamp('received_at')->nullable()->after('status');
            }
            if (! Schema::hasColumn('purchases', 'received_by')) {
                $table->foreignId('received_by')
                    ->nullable()
                    ->after('received_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_items', 'discount')) {
                $table->decimal('discount', 10, 2)->default(0.00)->after('unit_cost');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_items', 'discount')) {
                $table->dropColumn('discount');
            }
        });

        Schema::table('purchases', function (Blueprint $table) {
            if (Schema::hasColumn('purchases', 'received_by')) {
                $table->dropForeign(['received_by']);
                $table->dropColumn(['received_by']);
            }
            if (Schema::hasColumn('purchases', 'received_at')) {
                $table->dropColumn(['received_at']);
            }
            if (Schema::hasColumn('purchases', 'discount')) {
                $table->dropColumn(['discount']);
            }
        });
    }
};
