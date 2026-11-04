<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\Subscription;
use App\Models\User;
use App\Services\TenantManager;
use App\Support\NicaraguaFiscalHelper;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class SecurityAndNicaraguaFiscalTest extends TestCase
{
    protected array $createdBusinesses = [];
    protected array $createdUsers = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        app(TenantManager::class)->setTenant(null);
    }

    protected function tearDown(): void
    {
        // Limpieza no destructiva de registros creados exclusivamente durante la prueba
        foreach ($this->createdBusinesses as $bizId) {
            DB::table('sale_return_items')->where('business_id', $bizId)->delete();
            DB::table('sale_returns')->where('business_id', $bizId)->delete();
            DB::table('sale_items')->where('business_id', $bizId)->delete();
            DB::table('sales')->where('business_id', $bizId)->delete();
            DB::table('inventory_movements')->where('business_id', $bizId)->delete();
            DB::table('products')->where('business_id', $bizId)->delete();
            DB::table('subscriptions')->where('business_id', $bizId)->delete();
            DB::table('audit_logs')->where('business_id', $bizId)->delete();
            DB::table('business_user')->where('business_id', $bizId)->delete();
            DB::table('businesses')->where('id', $bizId)->delete();
        }

        foreach ($this->createdUsers as $userId) {
            DB::table('users')->where('id', $userId)->delete();
        }

        parent::tearDown();
    }

    private function createTestTenant(array $settings = [], string $subscriptionStatus = Subscription::STATUS_ACTIVE): array
    {
        $business = Business::create([
            'name' => 'Distribuidora Managua Test ' . uniqid(),
            'slug' => 'dist-managua-' . uniqid(),
            'tax_id' => 'J0310000000001',
            'address' => 'Km 4.5 Carretera Norte',
            'status' => 'active',
            'settings' => array_merge([
                'currency' => 'NIO',
                'tax_regime' => 'regimen_general',
                'tax_percentage' => 15.0,
                'timezone' => 'America/Managua',
                'municipality' => 'Managua, Nicaragua',
            ], $settings),
        ]);
        $this->createdBusinesses[] = $business->id;

        $owner = User::create([
            'name' => 'Propietario Nicaragua ' . uniqid(),
            'email' => 'owner_ni_' . uniqid() . '@puntoagil.test',
            'password' => Hash::make('PasswordSegura123!'),
            'role' => User::ROLE_OWNER,
            'status' => 'active',
            'current_business_id' => $business->id,
        ]);
        $this->createdUsers[] = $owner->id;
        $owner->businesses()->attach($business->id, ['role' => User::ROLE_OWNER, 'is_default' => true]);

        $plan = Plan::where('slug', 'puntoagil')->first() ?: Plan::first();

        $subscription = Subscription::create([
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => $subscriptionStatus,
            'billing_cycle' => 'monthly',
            'price' => 30.00,
            'starts_at' => now(),
            'ends_at' => in_array($subscriptionStatus, [Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIALING], true)
                ? now()->addMonth()
                : now()->subDay(),
            'payment_provider' => 'manual',
        ]);

        return [$business, $owner, $subscription];
    }

    /**
     * Módulo 17: Verifica cabeceras HTTP de seguridad y limitación de intentos de login (RateLimiter).
     */
    public function test_http_security_headers_and_login_rate_limiting(): void
    {
        // 1. Verificar cabeceras de defensa en profundidad
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');

        // 2. Verificar protección contra fuerza bruta en /login (máximo 5 intentos fallidos)
        $targetEmail = 'bruteforce_' . uniqid() . '@puntoagil.test';
        RateLimiter::clear($targetEmail . '|127.0.0.1');

        for ($i = 1; $i <= 5; $i++) {
            $this->from('/login')->post('/login', [
                'email' => $targetEmail,
                'password' => 'WrongPassword' . $i,
            ]);
        }

        // El 6to intento debe ser bloqueado por RateLimiter
        $blockedResponse = $this->from('/login')->post('/login', [
            'email' => $targetEmail,
            'password' => 'WrongPassword6',
        ]);
        $blockedResponse->assertRedirect('/login');
        $blockedResponse->assertSessionHasErrors('email');
        $errorMsg = session('errors')->first('email');
        $this->assertStringContainsString('Demasiados intentos', $errorMsg);
    }

    /**
     * Módulo 17: Verifica que todas las tablas en el esquema public de Supabase PostgreSQL tengan RLS activo
     * y que una suscripción vencida o suspendida bloquee operaciones en el servidor.
     */
    public function test_supabase_rls_enabled_and_suspended_subscription_blocks_pos_operations(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            $stats = DB::selectOne("
                SELECT count(*) as total,
                       sum(case when rowsecurity then 1 else 0 end) as rls_enabled
                FROM pg_tables
                WHERE schemaname = 'public'
            ");
            $this->assertEquals((int) $stats->total, (int) $stats->rls_enabled, 'Todas las tablas del esquema public deben tener ROW LEVEL SECURITY habilitado.');
        }

        // Crear empresa con suscripción suspendida
        [$biz, $owner, $sub] = $this->createTestTenant([], Subscription::STATUS_SUSPENDED);

        // Intentar acceder al POS con suscripción suspendida -> Redirige a /admin/subscription
        $posResp = $this->actingAs($owner)->get(route('pos.create'));
        $posResp->assertRedirect(route('admin.subscription.index'));
        $posResp->assertSessionHas('error');

        // Petición JSON al POS con suscripción suspendida -> 403 Forbidden
        $jsonResp = $this->actingAs($owner)->postJson(route('sales.store'), ['items' => []]);
        $jsonResp->assertStatus(403);

        // El propietario sí puede entrar a /admin/subscription para consultar el estado de su licencia
        $subPageResp = $this->actingAs($owner)->get(route('admin.subscription.index'));
        $subPageResp->assertOk();
    }

    /**
     * Módulo 19: Validación Fiscal de Nicaragua (Ley No. 822 y Código Tributario Ley No. 562).
     * Verifica formato de Cédula/RUC, IVA 15% en Régimen General, 0% en Cuota Fija, restitución de IVA en devoluciones e inmutabilidad física.
     */
    public function test_nicaragua_fiscal_rules_iva_15_percent_cuota_fija_and_immutable_documents(): void
    {
        // 1. Validación de formatos de Cédula y RUC de Nicaragua
        $this->assertTrue(NicaraguaFiscalHelper::isValidNicaraguaCedula('001-010190-0001A'));
        $this->assertTrue(NicaraguaFiscalHelper::isValidNicaraguaCedula('0010101900001A'));
        $this->assertFalse(NicaraguaFiscalHelper::isValidNicaraguaCedula('12345'));

        $this->assertTrue(NicaraguaFiscalHelper::isValidNicaraguaRuc('J0310000000001'));
        $this->assertTrue(NicaraguaFiscalHelper::isValidNicaraguaRuc('0010101900001A'));
        $this->assertFalse(NicaraguaFiscalHelper::isValidNicaraguaRuc('INVALID-RUC'));

        // 2. Régimen General en Nicaragua (Responsable Recaudador IVA = 15% según Art. 109 Ley 822)
        [$bizGeneral, $ownerGeneral] = $this->createTestTenant([
            'currency' => 'NIO',
            'tax_regime' => 'regimen_general',
            'tax_percentage' => 15.0,
        ]);
        app(TenantManager::class)->setTenant($bizGeneral);

        $product = Product::create([
            'business_id' => $bizGeneral->id,
            'sku' => 'NIC-CEM-' . rand(1000, 9999),
            'name' => 'Bolsa de Cemento Canal 42.5kg',
            'category' => 'Construcción',
            'unit' => 'bolsa',
            'cost' => 280.00,
            'price' => 350.00,
            'stock' => 50,
            'min_stock' => 5,
            'status' => Product::STATUS_ACTIVE,
        ]);

        // Venta de 2 bolsas a C$ 350.00 = Subtotal C$ 700.00 + IVA 15% (C$ 105.00) = Total C$ 805.00
        $saleResp = $this->actingAs($ownerGeneral)->postJson(route('sales.store'), [
            'customer_name' => 'Constructora Segovia S.A.',
            'customer_document' => 'J0310000009999',
            'payment_method' => 'cash',
            'paid_amount' => 1000.00,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'unit_price' => 350.00,
                ],
            ],
        ]);

        $saleResp->assertOk();
        $saleGeneral = Sale::with('items')->findOrFail($saleResp->json('sale_id'));
        $this->assertEquals(700.00, (float) $saleGeneral->subtotal);
        $this->assertEquals(105.00, (float) $saleGeneral->tax, 'El IVA calculado bajo Régimen General en Nicaragua debe ser exactamente 15% (C$ 105.00).');
        $this->assertEquals(805.00, (float) $saleGeneral->total);

        // 3. Devolución parcial de 1 bolsa -> Verifica restitución proporcional de IVA (Art. 123 Ley 822)
        $saleItem = $saleGeneral->items->first();
        $returnResp = $this->actingAs($ownerGeneral)->post(route('sales.returns.store', $saleGeneral->id), [
            'refund_method' => 'credit_note',
            'reason' => 'Devolución de 1 bolsa por ajuste de pedido',
            'items' => [
                [
                    'sale_item_id' => $saleItem->id,
                    'quantity' => 1,
                ],
            ],
        ]);
        $returnResp->assertSessionHas('success');

        $saleReturn = SaleReturn::where('sale_id', $saleGeneral->id)->firstOrFail();
        $this->assertEquals(350.00, (float) $saleReturn->total_refunded);
        $this->assertEquals(52.50, $saleReturn->restitutedTaxAmount(), 'El IVA restituido por 1 bolsa debe ser C$ 52.50 (15% de 350.00).');
        $this->assertEquals(402.50, $saleReturn->totalWithTax());

        // 4. Comprobar prohibición de eliminación física de ventas y notas de devolución (Art. 103 Ley 562)
        try {
            $saleGeneral->delete();
            $this->fail('Se permitió eliminar físicamente una venta confirmada.');
        } catch (\LogicException $e) {
            $this->assertStringContainsString('inmutables', $e->getMessage());
        }

        try {
            $saleReturn->delete();
            $this->fail('Se permitió eliminar físicamente una nota de devolución.');
        } catch (\LogicException $e) {
            $this->assertStringContainsString('no pueden eliminarse', $e->getMessage());
        }

        // 5. Régimen Simplificado de Cuota Fija (Art. 251 Ley 822 -> No traslada IVA, 0%)
        $bizGeneral->update([
            'settings' => array_merge($bizGeneral->settings, [
                'tax_regime' => 'cuota_fija',
                'tax_percentage' => 15.0, // Incluso si quedó un número, el servidor debe aplicar 0% en cuota fija
            ]),
        ]);
        app(TenantManager::class)->setTenant($bizGeneral->fresh());

        $cuotaFijaResp = $this->actingAs($ownerGeneral)->postJson(route('sales.store'), [
            'payment_method' => 'cash',
            'paid_amount' => 400.00,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => 350.00,
                ],
            ],
        ]);
        $cuotaFijaResp->assertOk();
        $saleCuotaFija = Sale::findOrFail($cuotaFijaResp->json('sale_id'));
        $this->assertEquals(350.00, (float) $saleCuotaFija->subtotal);
        $this->assertEquals(0.00, (float) $saleCuotaFija->tax, 'En Régimen de Cuota Fija no se debe trasladar IVA (0.00).');
        $this->assertEquals(350.00, (float) $saleCuotaFija->total);
    }
}
