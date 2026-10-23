<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Crear tabla de negocios/empresas (Tenants)
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('tax_id')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->enum('status', ['active', 'suspended'])->default('active');
            $table->jsonb('settings')->nullable();
            $table->timestamps();
        });

        // 2. Sembrar el negocio inicial para asociar los datos existentes de forma no destructiva
        $defaultBusinessId = DB::table('businesses')->insertGetId([
            'name' => 'PuntoÁgil Central',
            'slug' => 'puntoagil-central',
            'tax_id' => '0999999999001',
            'email' => 'admin@puntoagil.com',
            'phone' => '+593 99 000 0000',
            'address' => 'Sede Principal - Matriz',
            'status' => 'active',
            'settings' => json_encode([
                'currency' => 'USD',
                'tax_percentage' => 12,
                'timezone' => 'America/Guayaquil',
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Crear tabla pivote business_user (Membresías multiempresa de usuarios)
        Schema::create('business_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 30)->default('cajero'); // owner, admin, cajero
            $table->boolean('is_default')->default(true);
            $table->timestamps();

            $table->unique(['business_id', 'user_id']);
            $table->index('user_id');
            $table->index('business_id');
        });

        // 4. Vincular usuarios existentes al negocio principal
        $existingUsers = DB::table('users')->get();
        foreach ($existingUsers as $user) {
            DB::table('business_user')->insert([
                'business_id' => $defaultBusinessId,
                'user_id' => $user->id,
                'role' => ($user->role === 'admin' ? 'owner' : 'cajero'),
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 5. Agregar current_business_id a users
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('current_business_id')
                ->nullable()
                ->after('status')
                ->constrained('businesses')
                ->nullOnDelete();
        });

        DB::table('users')->update(['current_business_id' => $defaultBusinessId]);

        // 6. Migrar tabla products a multiempresa
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('business_id')
                ->nullable()
                ->after('id')
                ->constrained('businesses')
                ->cascadeOnDelete();
        });

        DB::table('products')->update(['business_id' => $defaultBusinessId]);
        DB::statement('ALTER TABLE products ALTER COLUMN business_id SET NOT NULL');

        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_sku_unique');
            $table->unique(['business_id', 'sku'], 'products_business_sku_unique');
            $table->index(['business_id', 'category'], 'products_business_category_idx');
            $table->index(['business_id', 'stock'], 'products_business_stock_idx');
        });

        // 7. Migrar tabla sales a multiempresa
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('business_id')
                ->nullable()
                ->after('id')
                ->constrained('businesses')
                ->cascadeOnDelete();
        });

        DB::table('sales')->update(['business_id' => $defaultBusinessId]);
        DB::statement('ALTER TABLE sales ALTER COLUMN business_id SET NOT NULL');

        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique('sales_invoice_number_unique');
            $table->unique(['business_id', 'invoice_number'], 'sales_business_invoice_unique');
            $table->index(['business_id', 'created_at'], 'sales_business_created_idx');
        });

        // 8. Migrar tabla sale_items a multiempresa
        Schema::table('sale_items', function (Blueprint $table) {
            $table->foreignId('business_id')
                ->nullable()
                ->after('id')
                ->constrained('businesses')
                ->cascadeOnDelete();
        });

        DB::table('sale_items')->update(['business_id' => $defaultBusinessId]);
        DB::statement('ALTER TABLE sale_items ALTER COLUMN business_id SET NOT NULL');

        Schema::table('sale_items', function (Blueprint $table) {
            $table->index(['business_id'], 'sale_items_business_idx');
        });

        // 9. Políticas RLS en Supabase (Defensa en profundidad para roles directos/API de Supabase)
        DB::statement('ALTER TABLE products ENABLE ROW LEVEL SECURITY');
        DB::statement("
            DO $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM pg_policies WHERE tablename = 'products' AND policyname = 'products_tenant_isolation'
                ) THEN
                    CREATE POLICY products_tenant_isolation ON products FOR ALL USING (
                        business_id = NULLIF(current_setting('app.current_business_id', true), '')::bigint
                        OR NULLIF(current_setting('app.current_business_id', true), '') IS NULL
                    );
                END IF;
            END $$;
        ");

        DB::statement('ALTER TABLE sales ENABLE ROW LEVEL SECURITY');
        DB::statement("
            DO $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM pg_policies WHERE tablename = 'sales' AND policyname = 'sales_tenant_isolation'
                ) THEN
                    CREATE POLICY sales_tenant_isolation ON sales FOR ALL USING (
                        business_id = NULLIF(current_setting('app.current_business_id', true), '')::bigint
                        OR NULLIF(current_setting('app.current_business_id', true), '') IS NULL
                    );
                END IF;
            END $$;
        ");

        DB::statement('ALTER TABLE sale_items ENABLE ROW LEVEL SECURITY');
        DB::statement("
            DO $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM pg_policies WHERE tablename = 'sale_items' AND policyname = 'sale_items_tenant_isolation'
                ) THEN
                    CREATE POLICY sale_items_tenant_isolation ON sale_items FOR ALL USING (
                        business_id = NULLIF(current_setting('app.current_business_id', true), '')::bigint
                        OR NULLIF(current_setting('app.current_business_id', true), '') IS NULL
                    );
                END IF;
            END $$;
        ");
    }

    public function down(): void
    {
        // 1. Eliminar políticas RLS
        DB::statement('DROP POLICY IF EXISTS sale_items_tenant_isolation ON sale_items');
        DB::statement('ALTER TABLE sale_items DISABLE ROW LEVEL SECURITY');

        DB::statement('DROP POLICY IF EXISTS sales_tenant_isolation ON sales');
        DB::statement('ALTER TABLE sales DISABLE ROW LEVEL SECURITY');

        DB::statement('DROP POLICY IF EXISTS products_tenant_isolation ON products');
        DB::statement('ALTER TABLE products DISABLE ROW LEVEL SECURITY');

        // 2. Revertir sale_items
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropIndex('sale_items_business_idx');
            $table->dropForeign(['business_id']);
            $table->dropColumn('business_id');
        });

        // 3. Revertir sales
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('sales_business_created_idx');
            $table->dropUnique('sales_business_invoice_unique');
            $table->dropForeign(['business_id']);
            $table->dropColumn('business_id');
            $table->unique('invoice_number', 'sales_invoice_number_unique');
        });

        // 4. Revertir products
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_business_stock_idx');
            $table->dropIndex('products_business_category_idx');
            $table->dropUnique('products_business_sku_unique');
            $table->dropForeign(['business_id']);
            $table->dropColumn('business_id');
            $table->unique('sku', 'products_sku_unique');
        });

        // 5. Revertir users
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['current_business_id']);
            $table->dropColumn('current_business_id');
        });

        // 6. Eliminar tablas business_user y businesses
        Schema::dropIfExists('business_user');
        Schema::dropIfExists('businesses');
    }
};
