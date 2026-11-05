<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Business;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\SubscriptionWebhookEvent;
use App\Models\User;
use App\Services\TenantManager;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SubscriptionAndSuperAdminTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        app(TenantManager::class)->setTenant(null);
    }

    protected function tearDown(): void
    {
        $officialPlan = Plan::where('slug', 'puntoagil')->first();
        if ($officialPlan) {
            Subscription::where('plan_id', '!=', $officialPlan->id)
                ->update(['plan_id' => $officialPlan->id]);
            Plan::where('id', '!=', $officialPlan->id)->delete();
        }
        parent::tearDown();
    }

    private function createBusinessWithPlan(string $prefix, ?Plan $plan = null, array $planLimits = []): array
    {
        $business = Business::create([
            'name' => "Empresa SaaS {$prefix} " . uniqid(),
            'slug' => "empresa-saas-{$prefix}-" . uniqid(),
            'tax_id' => '17' . rand(100000000, 999999999) . '001',
            'status' => 'active',
            'settings' => [
                'currency' => 'USD',
                'tax_percentage' => 12.0,
                'timezone' => 'America/Guayaquil',
            ],
        ]);

        $owner = User::create([
            'name' => "Owner {$prefix}",
            'email' => "owner_{$prefix}_" . uniqid() . '@saas.test',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'status' => 'active',
            'current_business_id' => $business->id,
        ]);
        $owner->businesses()->attach($business->id, ['role' => 'owner', 'is_default' => true]);

        if (! $plan) {
            $plan = Plan::create(array_merge([
                'name' => "Plan Test {$prefix}",
                'slug' => "plan-test-{$prefix}-" . uniqid(),
                'description' => 'Plan de prueba',
                'price' => 30.00,
                'billing_cycle' => 'monthly',
                'trial_days' => 14,
                'max_users' => 2,
                'max_products' => 3,
                'max_cash_registers' => 1,
                'features' => ['purchases' => true, 'returns' => true],
                'is_active' => true,
                'sort_order' => 99,
            ], $planLimits));
        }

        $subscription = Subscription::create([
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => 'monthly',
            'price' => $plan->price,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'payment_provider' => 'stripe',
            'provider_subscription_id' => 'sub_' . uniqid(),
            'provider_customer_id' => 'cus_' . uniqid(),
        ]);

        return [$business, $owner, $plan, $subscription];
    }

    private function createSuperAdmin(): User
    {
        return User::create([
            'name' => 'Super Admin Global ' . uniqid(),
            'email' => 'superadmin_' . uniqid() . '@puntoagil.test',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'is_super_admin' => true,
            'status' => 'active',
        ]);
    }

    /**
     * Test Módulo 14: Verificación estricta en servidor de límites de cuota (productos y usuarios).
     */
    public function test_tenant_plan_limits_enforced_on_server(): void
    {
        [$biz, $owner, $plan, $sub] = $this->createBusinessWithPlan('limits', null, [
            'max_users' => 2,
            'max_products' => 2,
        ]);
        app(TenantManager::class)->setTenant($biz);

        // Crear 2 productos (alcanza el límite de 2)
        Product::create([
            'business_id' => $biz->id,
            'name' => 'Producto 1 ' . uniqid(),
            'sku' => 'SKU1-' . uniqid(),
            'category' => 'General',
            'unit' => 'unit',
            'cost' => 5.0,
            'price' => 10.0,
            'stock' => 10,
            'min_stock' => 2,
            'status' => 'active',
        ]);

        Product::create([
            'business_id' => $biz->id,
            'name' => 'Producto 2 ' . uniqid(),
            'sku' => 'SKU2-' . uniqid(),
            'category' => 'General',
            'unit' => 'unit',
            'cost' => 5.0,
            'price' => 10.0,
            'stock' => 10,
            'min_stock' => 2,
            'status' => 'active',
        ]);

        $this->assertEquals(2, $biz->products()->count());
        $this->assertFalse($biz->canAddProduct());

        // Intentar registrar un 3er producto vía HTTP POST -> Debe ser rechazado por límite de plan
        $this->actingAs($owner);
        $response = $this->post(route('inventory.store'), [
            'name' => 'Producto Excedente',
            'category' => 'General',
            'sku' => 'SKU-EXC-' . uniqid(),
            'cost' => 10.0,
            'price' => 20.0,
            'stock' => 5,
            'min_stock' => 1,
            'unit' => 'unit',
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals(2, $biz->products()->count(), 'El servidor impidió registrar productos excediendo la cuota del plan.');

        // Verificar límite de usuarios: actualmente tiene 1 usuario (el owner), puede añadir 1 más pero no 2
        $this->assertTrue($biz->canAddUser());
        $secondUser = User::create([
            'name' => 'Usuario Dos',
            'email' => 'user2_' . uniqid() . '@test.com',
            'password' => Hash::make('password123'),
            'role' => 'cajero',
            'status' => 'active',
            'current_business_id' => $biz->id,
        ]);
        $secondUser->businesses()->attach($biz->id, ['role' => 'cajero']);

        $this->assertEquals(2, $biz->users()->count());
        $this->assertFalse($biz->canAddUser());

        // Intentar crear un 3er usuario por controlador -> Debe ser rechazado
        $userResponse = $this->post(route('admin.users.store'), [
            'name' => 'Tercer Usuario No Permitido',
            'email' => 'user3_' . uniqid() . '@test.com',
            'role' => 'cajero',
            'status' => 'active',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $userResponse->assertSessionHas('error');
        $this->assertEquals(2, $biz->users()->count());
    }

    /**
     * Test Módulo 14: Cambio de plan de suscripción y validación de downgrades bloqueados por uso actual.
     */
    public function test_tenant_subscription_plan_change_and_downgrade_validation(): void
    {
        [$biz, $owner, $initialPlan, $sub] = $this->createBusinessWithPlan('change', null, [
            'max_users' => 10,
            'max_products' => 100,
            'price' => 45.0,
        ]);
        app(TenantManager::class)->setTenant($biz);

        // Añadimos 3 usuarios en la empresa
        for ($i = 1; $i <= 2; $i++) {
            $u = User::create([
                'name' => "User {$i} " . uniqid(),
                'email' => "user_{$i}_" . uniqid() . '@test.com',
                'password' => Hash::make('pass'),
                'role' => 'cajero',
                'status' => 'active',
            ]);
            $u->businesses()->attach($biz->id, ['role' => 'cajero']);
        }
        $this->assertEquals(3, $biz->users()->count());

        // Crear un plan inferior que solo permite 2 usuarios
        $inferiorPlan = Plan::create([
            'name' => 'Plan Básico Restringido ' . uniqid(),
            'slug' => 'plan-basico-' . uniqid(),
            'price' => 15.00,
            'billing_cycle' => 'monthly',
            'trial_days' => 0,
            'max_users' => 2, // Menor que los 3 actuales
            'max_products' => 50,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        // Intentar downgrade -> Debe fallar por exceso de usuarios
        $this->actingAs($owner);
        $downgradeResp = $this->post(route('admin.subscription.change-plan'), [
            'plan_id' => $inferiorPlan->id,
            'billing_cycle' => 'monthly',
        ]);

        $downgradeResp->assertSessionHas('error');
        $this->assertEquals($initialPlan->id, $biz->fresh()->currentSubscription()->plan_id);

        // Crear un plan superior (Ilimitado)
        $superiorPlan = Plan::create([
            'name' => 'Plan Empresarial Ilimitado ' . uniqid(),
            'slug' => 'plan-ilimitado-' . uniqid(),
            'price' => 89.00,
            'billing_cycle' => 'monthly',
            'trial_days' => 0,
            'max_users' => null, // Ilimitado
            'max_products' => null,
            'is_active' => true,
            'sort_order' => 10,
        ]);

        // Upgrade exitoso
        $upgradeResp = $this->post(route('admin.subscription.change-plan'), [
            'plan_id' => $superiorPlan->id,
            'billing_cycle' => 'yearly',
        ]);

        $upgradeResp->assertSessionHas('success');
        $updatedSub = $biz->fresh()->currentSubscription();
        $this->assertEquals($superiorPlan->id, $updatedSub->plan_id);
        $this->assertEquals('yearly', $updatedSub->billing_cycle);
    }

    /**
     * Test Módulo 14: Webhooks de pago con firma, idempotencia y sanitización de datos sensibles.
     */
    public function test_payment_webhook_idempotency_and_card_data_sanitization(): void
    {
        [$biz, $owner, $plan, $sub] = $this->createBusinessWithPlan('webhook');
        $sub->update([
            'status' => Subscription::STATUS_PAST_DUE,
            'provider_subscription_id' => 'sub_stripe_test_' . uniqid(),
        ]);

        $eventId = 'evt_test_' . uniqid();
        $rawPayload = [
            'id' => $eventId,
            'type' => 'invoice.payment_succeeded',
            'created' => time(),
            'data' => [
                'object' => [
                    'id' => 'in_stripe_invoice_' . uniqid(),
                    'subscription' => $sub->provider_subscription_id,
                    'customer' => $sub->provider_customer_id,
                    'amount_paid' => 3000,
                    // Datos sensibles que NUNCA deben persistirse en texto plano según PCI-DSS
                    'card_number' => '4242424242424242',
                    'cvv' => '999',
                    'client_secret' => 'sk_live_secret_token_12345',
                ],
            ],
        ];

        // 1. Enviar el evento por primera vez
        $resp1 = $this->postJson(route('webhooks.subscriptions', ['provider' => 'stripe']), $rawPayload);
        $resp1->assertOk();
        $resp1->assertJson(['status' => 'success']);

        // Comprobar que la suscripción se reactivó
        $this->assertEquals(Subscription::STATUS_ACTIVE, $sub->fresh()->status);

        // Comprobar que el evento se registró en la base de datos y los datos sensibles se redactaron
        $storedEvent = SubscriptionWebhookEvent::where('event_id', $eventId)->first();
        $this->assertNotNull($storedEvent);
        $this->assertEquals('processed', $storedEvent->status);
        $storedPayload = $storedEvent->payload;
        $this->assertEquals('[REDACTED_SECURE]', $storedPayload['data']['object']['card_number']);
        $this->assertEquals('[REDACTED_SECURE]', $storedPayload['data']['object']['cvv']);
        $this->assertEquals('[REDACTED_SECURE]', $storedPayload['data']['object']['client_secret']);

        // 2. Enviar el mismo evento nuevamente -> Idempotencia debe retornar duplicate o ignored
        $resp2 = $this->postJson(route('webhooks.subscriptions', ['provider' => 'stripe']), $rawPayload);
        $resp2->assertOk();
        $resp2->assertJson(['status' => 'ignored']);
    }

    /**
     * Test Módulo 15: Panel Super Admin, métricas globales, conmutación de estado de empresa y protección contra autorevocación.
     */
    public function test_superadmin_dashboard_metrics_and_tenant_management(): void
    {
        $superAdmin = $this->createSuperAdmin();
        [$biz, $owner, $plan, $sub] = $this->createBusinessWithPlan('sa1');

        // 1. Un usuario regular no puede entrar al dashboard de Super Admin
        $this->actingAs($owner);
        $this->get(route('admin.superadmin.dashboard'))->assertForbidden();
        $this->get(route('admin.superadmin.businesses'))->assertForbidden();
        $this->get(route('admin.superadmin.plans'))->assertForbidden();

        // 2. Super Admin accede correctamente al dashboard y métricas
        app(TenantManager::class)->setTenant(null);
        $this->actingAs($superAdmin);
        $dashResp = $this->get(route('admin.superadmin.dashboard'));
        $dashResp->assertOk();
        $dashResp->assertViewHas('totalBusinesses');
        $dashResp->assertViewHas('mrr');
        $dashResp->assertSeeText($biz->name);

        // 3. Super Admin puede suspender y reactivar una empresa
        $toggleResp = $this->patch(route('admin.businesses.toggle-status', $biz));
        $toggleResp->assertRedirect();
        $this->assertEquals('suspended', $biz->fresh()->status);

        // Reactivar
        $this->patch(route('admin.businesses.toggle-status', $biz));
        $this->assertEquals('active', $biz->fresh()->status);

        // 4. Super Admin puede anular/sobrescribir administrativamente la suscripción de una empresa
        $newPlan = Plan::create([
            'name' => 'Plan Admin Override ' . uniqid(),
            'slug' => 'plan-override-' . uniqid(),
            'price' => 99.00,
            'billing_cycle' => 'monthly',
            'trial_days' => 0,
            'is_active' => true,
            'sort_order' => 50,
        ]);

        $subOverrideResp = $this->post(route('admin.superadmin.businesses.subscription', $biz), [
            'plan_id' => $newPlan->id,
            'status' => 'trialing',
            'notes' => 'Cortesía de soporte extendido',
        ]);
        $subOverrideResp->assertSessionHas('success');
        $this->assertEquals($newPlan->id, $biz->fresh()->currentSubscription()->plan_id);
        $this->assertEquals('trialing', $biz->fresh()->currentSubscription()->status);

        // 5. Super Admin puede crear un nuevo plan en la plataforma
        $createPlanResp = $this->post(route('admin.superadmin.plans.store'), [
            'name' => 'Plan Creado Por SA ' . uniqid(),
            'description' => 'Plan de expansión',
            'price' => 59.99,
            'billing_cycle' => 'monthly',
            'trial_days' => 7,
            'max_users' => 5,
            'max_products' => 500,
            'max_cash_registers' => 2,
            'features' => ['purchases' => 1, 'returns' => 1],
        ]);
        $createPlanResp->assertRedirect(route('admin.superadmin.plans'));
        $this->assertTrue(Plan::where('price', 59.99)->exists());

        // 6. Protección: Si es el único Super Admin de la plataforma, no puede auto-revocarse
        // Aseguramos que solo haya 1 super admin activo
        User::where('id', '!=', $superAdmin->id)->update(['is_super_admin' => false]);
        $revokeSelfResp = $this->patch(route('admin.superadmin.users.toggle-super', $superAdmin));
        $revokeSelfResp->assertSessionHas('error');
        $this->assertTrue($superAdmin->fresh()->is_super_admin, 'El sistema protegió que no se revoque al único Super Admin existente.');
    }

    /**
     * Test Módulo 16: Configuración del negocio, opciones de ticket y bitácora de auditoría inmutable con exportación CSV.
     */
    public function test_business_settings_audit_immutability_and_csv_export(): void
    {
        [$biz, $owner] = $this->createBusinessWithPlan('settings_audit');
        app(TenantManager::class)->setTenant($biz);

        $this->actingAs($owner);

        // 1. Actualizar configuración comercial (datos fiscales, slogan, receipt_format)
        $newSlogan = 'Tu punto de venta más confiable';
        $uniqueTaxId = 'J031' . rand(1000000000, 9999999999);
        $updateResp = $this->put(route('admin.business.update'), [
            'name' => 'Ferretería Central Renovada',
            'tax_id' => $uniqueTaxId,
            'email' => 'contacto@ferreteria.com',
            'phone' => '0999999999',
            'address' => 'Av. Amazonas y Colón',
            'slogan' => $newSlogan,
            'currency' => 'USD',
            'tax_percentage' => 15.0,
            'timezone' => 'America/Guayaquil',
            'receipt_format' => 'ticket_58mm',
            'receipt_footer_note' => 'Gracias por su compra.',
        ]);

        $updateResp->assertRedirect(route('admin.business.settings'));
        $updateResp->assertSessionHas('success');

        $freshBiz = $biz->fresh();
        $this->assertEquals('Ferretería Central Renovada', $freshBiz->name);
        $this->assertEquals($newSlogan, $freshBiz->settings['slogan']);
        $this->assertEquals('ticket_58mm', $freshBiz->settings['receipt_format']);
        $this->assertEquals(15.0, $freshBiz->settings['tax_percentage']);

        // 2. Verificar que la operación sensible quedó registrada en la bitácora de auditoría inmutable
        $auditLog = AuditLog::where('business_id', $biz->id)
            ->where('action', 'business.updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertEquals($owner->id, $auditLog->user_id);
        $this->assertStringContainsString('Ferretería Central Renovada', $auditLog->description);

        // 2.1. Comprobar inmutabilidad en la capa de modelo (prohibición de update y delete)
        try {
            $auditLog->update(['description' => 'Intento de alteración']);
            $this->fail('AuditLog permitió una actualización cuando debería ser inmutable.');
        } catch (\LogicException $e) {
            $this->assertStringContainsString('inmutables', $e->getMessage());
        }

        try {
            $auditLog->delete();
            $this->fail('AuditLog permitió una eliminación cuando debería ser inmutable.');
        } catch (\LogicException $e) {
            $this->assertStringContainsString('inmutables', $e->getMessage());
        }

        // 3. Exportar bitácora a CSV y comprobar headers y contenido
        $exportResp = $this->get(route('admin.audit.export'));
        $exportResp->assertOk();
        $this->assertStringContainsString('text/csv', strtolower($exportResp->headers->get('Content-Type')));
        $content = $exportResp->getContent();
        $this->assertStringContainsString('Acción Registrada', $content);
        $this->assertStringContainsString('business.updated', $content);
        $this->assertStringContainsString($owner->email, $content);
    }
}
