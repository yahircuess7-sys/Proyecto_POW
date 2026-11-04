<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tablas multitenant que poseen columna business_id.
     *
     * @var array<int, string>
     */
    protected array $tenantTables = [
        'products',
        'sales',
        'sale_items',
        'customers',
        'suppliers',
        'purchases',
        'purchase_items',
        'inventory_movements',
        'cash_registers',
        'cash_shifts',
        'cash_movements',
        'sale_returns',
        'sale_return_items',
        'audit_logs',
        'business_invitations',
        'business_user',
        'subscriptions',
    ];

    /**
     * Tablas globales y de infraestructura interna de Laravel en el esquema public.
     *
     * @var array<int, string>
     */
    protected array $systemTables = [
        'businesses',
        'users',
        'plans',
        'subscription_webhook_events',
        'sessions',
        'password_reset_token',
        'personal_access_tokens',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'failed_job',
        'migrations',
    ];

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // 1. Habilitar ROW LEVEL SECURITY en todas las tablas del esquema public
        $allTables = array_merge($this->tenantTables, $this->systemTables);

        foreach ($allTables as $table) {
            if (Schema::hasTable($table)) {
                DB::statement("ALTER TABLE \"{$table}\" ENABLE ROW LEVEL SECURITY");
            }
        }

        // 2. Aplicar políticas estrictas de aislamiento por business_id en tablas multitenant
        //    (Sin la cláusula permisiva OR current_setting IS NULL que permitía lecturas sin contexto en PostgREST)
        foreach ($this->tenantTables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'business_id')) {
                continue;
            }

            $policyName = "{$table}_tenant_isolation";

            DB::statement("DROP POLICY IF EXISTS \"{$policyName}\" ON \"{$table}\"");
            DB::statement("
                CREATE POLICY \"{$policyName}\" ON \"{$table}\"
                FOR ALL
                USING (business_id = NULLIF(current_setting('app.current_business_id', true), '')::bigint)
                WITH CHECK (business_id = NULLIF(current_setting('app.current_business_id', true), '')::bigint)
            ");
        }

        // 3. Revocar permisos directos de los roles públicos de PostgREST (anon, authenticated)
        //    ya que PuntoÁgil opera exclusivamente a través del backend Laravel autenticado.
        DB::statement("
            DO $$
            BEGIN
                IF EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'anon') THEN
                    REVOKE ALL ON ALL TABLES IN SCHEMA public FROM anon;
                    REVOKE ALL ON ALL SEQUENCES IN SCHEMA public FROM anon;
                END IF;
                IF EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'authenticated') THEN
                    REVOKE ALL ON ALL TABLES IN SCHEMA public FROM authenticated;
                    REVOKE ALL ON ALL SEQUENCES IN SCHEMA public FROM authenticated;
                END IF;
            END $$;
        ");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach ($this->tenantTables as $table) {
            if (Schema::hasTable($table)) {
                $policyName = "{$table}_tenant_isolation";
                DB::statement("DROP POLICY IF EXISTS \"{$policyName}\" ON \"{$table}\"");
            }
        }
    }
};
