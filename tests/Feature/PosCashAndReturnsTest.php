<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\CashMovement;
use App\Models\CashRegister;
use App\Models\CashShift;
use App\Models\Customer;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\User;
use App\Services\TenantManager;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class PosCashAndReturnsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    private function createBusinessWithAdminAndCashier(string $prefix = 'pos'): array
    {
        $business = Business::create([
            'name' => "Ferretería {$prefix} " . uniqid(),
            'slug' => "ferreteria-{$prefix}-" . uniqid(),
            'tax_id' => '17' . rand(100000000, 999999999) . '001',
            'status' => 'active',
            'settings' => [
                'tax_percentage' => 12.0,
                'currency' => 'USD',
            ],
        ]);

        $admin = User::create([
            'name' => "Admin {$prefix}",
            'email' => "admin_{$prefix}_" . uniqid() . '@puntoagil.test',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'status' => 'active',
            'current_business_id' => $business->id,
        ]);
        $admin->businesses()->attach($business->id, ['role' => 'owner', 'is_default' => true]);

        $cashier = User::create([
            'name' => "Cajero {$prefix}",
            'email' => "cajero_{$prefix}_" . uniqid() . '@puntoagil.test',
            'password' => Hash::make('password123'),
            'role' => 'cajero',
            'status' => 'active',
            'current_business_id' => $business->id,
        ]);
        $cashier->businesses()->attach($business->id, ['role' => 'cajero', 'is_default' => true]);

        return [$business, $admin, $cashier];
    }

    public function test_cash_register_shift_lifecycle_open_movement_and_close_with_arqueo(): void
    {
        [$biz, $admin, $cashier] = $this->createBusinessWithAdminAndCashier('caja1');
        app(TenantManager::class)->setTenant($biz);

        // 1. Crear caja registradora
        $register = CashRegister::create([
            'business_id' => $biz->id,
            'name' => 'Caja Mostrador 1',
            'status' => CashRegister::STATUS_ACTIVE,
        ]);

        // 2. Apertura de turno por cajero con fondo inicial de $100.00
        $openResponse = $this->actingAs($cashier)->post(route('cash.open'), [
            'cash_register_id' => $register->id,
            'initial_cash' => 100.00,
            'notes' => 'Apertura turno matutino',
        ]);

        $openResponse->assertRedirect(route('cash.current'));
        $shift = CashShift::where('business_id', $biz->id)->where('user_id', $cashier->id)->first();
        $this->assertNotNull($shift);
        $this->assertEquals(CashShift::STATUS_OPEN, $shift->status);
        $this->assertEquals(100.00, (float) $shift->initial_cash);

        // Intentar abrir otro turno en la misma caja debe ser rechazado
        $duplicateOpen = $this->actingAs($admin)->post(route('cash.open'), [
            'cash_register_id' => $register->id,
            'initial_cash' => 50.00,
        ]);
        $duplicateOpen->assertSessionHasErrors('cash_register_id');

        // 3. Registrar entrada autorizada de efectivo: +$30.00
        $movementIn = $this->actingAs($cashier)->post(route('cash.movements.store', $shift->id), [
            'type' => 'in',
            'amount' => 30.00,
            'reason' => 'Recarga de monedas para cambio',
        ]);
        $movementIn->assertSessionHas('success');

        // 4. Registrar salida autorizada de efectivo: -$15.00
        $movementOut = $this->actingAs($cashier)->post(route('cash.movements.store', $shift->id), [
            'type' => 'out',
            'amount' => 15.00,
            'reason' => 'Pago de taxi para entrega urgente',
        ]);
        $movementOut->assertSessionHas('success');

        // Esperado en gaveta: 100 + 30 - 15 = 115.00
        $this->assertEquals(115.00, $shift->calculateExpectedCash());

        // 5. Cierre de turno y Arqueo:
        // Si hay diferencia pero falta justificación, debe solicitar notas explicativas
        $closeWithoutNotes = $this->actingAs($cashier)->post(route('cash.close', $shift->id), [
            'closing_cash' => 110.00, // Hay faltante de -$5.00
            'notes' => '',
        ]);
        $closeWithoutNotes->assertSessionHasErrors('notes');

        // Cierre con justificación válida
        $closeValid = $this->actingAs($cashier)->post(route('cash.close', $shift->id), [
            'closing_cash' => 110.00,
            'notes' => 'Faltante de $5.00 justificado por error en vuelto entregado',
        ]);
        $closeValid->assertRedirect(route('cash.show', $shift->id));

        $shift->refresh();
        $this->assertEquals(CashShift::STATUS_CLOSED, $shift->status);
        $this->assertEquals(115.00, (float) $shift->expected_cash);
        $this->assertEquals(110.00, (float) $shift->closing_cash);
        $this->assertEquals(-5.00, (float) $shift->difference);
    }

    public function test_pos_sale_with_idempotency_prevents_duplicate_records(): void
    {
        [$biz, $admin, $cashier] = $this->createBusinessWithAdminAndCashier('pos1');
        app(TenantManager::class)->setTenant($biz);

        $product = Product::create([
            'business_id' => $biz->id,
            'sku' => 'TEST-MART-' . rand(1000, 9999),
            'name' => 'Martillo de Uña 16oz',
            'category' => 'Herramientas Manuales',
            'unit' => 'unidad',
            'cost' => 6.00,
            'price' => 12.00,
            'stock' => 20,
            'min_stock' => 3,
            'status' => Product::STATUS_ACTIVE,
        ]);

        $customer = Customer::create([
            'business_id' => $biz->id,
            'document_type' => 'Cédula',
            'document_number' => '17' . rand(10000000, 99999999),
            'name' => 'Juan Pérez Cliente',
            'status' => Customer::STATUS_ACTIVE,
        ]);

        $idempotencyKey = 'idemp-' . Str::uuid();

        // 1. Primera llamada de venta en POS
        $payload = [
            'idempotency_key' => $idempotencyKey,
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_document' => $customer->document_number,
            'payment_method' => 'cash',
            'discount' => 2.00,
            'paid_amount' => 50.00,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'unit_price' => 12.00,
                ],
            ],
        ];

        // Subtotal = 2 * 12 = 24.00
        // Descuento = 2.00 -> Base imponible = 22.00
        // IVA 12% = 22.00 * 0.12 = 2.64
        // Total = 24.64
        // Paid = 50.00 -> Cambio = 25.36

        $response1 = $this->actingAs($cashier)->postJson(route('sales.store'), $payload);
        $response1->assertOk();
        $response1->assertJson(['success' => true]);

        $saleId1 = $response1->json('sale_id');
        $this->assertNotNull($saleId1);

        $product->refresh();
        $this->assertEquals(18, $product->stock); // 20 - 2 = 18

        $sale = Sale::find($saleId1);
        $this->assertEquals(24.64, (float) $sale->total);
        $this->assertEquals(25.36, (float) $sale->change_amount);
        $this->assertEquals($idempotencyKey, $sale->idempotency_key);

        // 2. Reintento idéntico con la misma clave de idempotencia (simula reintento de red o doble clic)
        $response2 = $this->actingAs($cashier)->postJson(route('sales.store'), $payload);
        $response2->assertOk();
        $response2->assertJson([
            'success' => true,
            'sale_id' => $saleId1,
            'idempotent' => true,
        ]);

        // El stock NO debe descontarse nuevamente
        $product->refresh();
        $this->assertEquals(18, $product->stock);

        // Solo debe existir una venta registrada con esa clave
        $this->assertEquals(1, Sale::where('idempotency_key', $idempotencyKey)->count());
    }

    public function test_sale_return_restores_inventory_and_updates_sale_status(): void
    {
        [$biz, $admin, $cashier] = $this->createBusinessWithAdminAndCashier('ret1');
        app(TenantManager::class)->setTenant($biz);

        $product = Product::create([
            'business_id' => $biz->id,
            'sku' => 'TEST-BROC-' . rand(1000, 9999),
            'name' => 'Broca para Concreto 1/2"',
            'category' => 'Accesorios',
            'unit' => 'unidad',
            'cost' => 2.50,
            'price' => 5.00,
            'stock' => 10,
            'min_stock' => 2,
            'status' => Product::STATUS_ACTIVE,
        ]);

        // Registrar una venta de 4 brocas
        $saleResponse = $this->actingAs($cashier)->postJson(route('sales.store'), [
            'payment_method' => 'card',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 4,
                    'unit_price' => 5.00,
                ],
            ],
        ]);
        $saleResponse->assertOk();
        $saleId = $saleResponse->json('sale_id');
        $sale = Sale::with('items')->find($saleId);

        $product->refresh();
        $this->assertEquals(6, $product->stock); // 10 - 4 = 6

        $saleItem = $sale->items->first();
        $this->assertEquals(4, $saleItem->availableForRefund());

        // 1. Devolución parcial de 1 broca
        $returnResp1 = $this->actingAs($cashier)->post(route('sales.returns.store', $sale->id), [
            'refund_method' => 'card_reversal',
            'reason' => 'Cliente compró broca de medida equivocada',
            'items' => [
                [
                    'sale_item_id' => $saleItem->id,
                    'quantity' => 1,
                ],
            ],
        ]);
        $returnResp1->assertSessionHas('success');

        // El stock debe aumentar en 1
        $product->refresh();
        $this->assertEquals(7, $product->stock); // 6 + 1 = 7

        $sale->refresh();
        $this->assertEquals(Sale::STATUS_PARTIALLY_REFUNDED, $sale->status);
        $saleItem->refresh();
        $this->assertEquals(1, $saleItem->refunded_quantity);
        $this->assertEquals(3, $saleItem->availableForRefund());

        // 2. Intentar devolver más de lo disponible (quedan 3 disponibles, intentar devolver 4)
        $invalidReturn = $this->actingAs($cashier)->post(route('sales.returns.store', $sale->id), [
            'refund_method' => 'card_reversal',
            'reason' => 'Exceso de cantidad',
            'items' => [
                [
                    'sale_item_id' => $saleItem->id,
                    'quantity' => 4,
                ],
            ],
        ]);
        $invalidReturn->assertSessionHasErrors('error');

        // 3. Devolver los 3 restantes -> la venta pasa a estado refunded (devuelta total)
        $returnResp2 = $this->actingAs($cashier)->post(route('sales.returns.store', $sale->id), [
            'refund_method' => 'credit_note',
            'reason' => 'Devolución del remanente',
            'items' => [
                [
                    'sale_item_id' => $saleItem->id,
                    'quantity' => 3,
                ],
            ],
        ]);
        $returnResp2->assertSessionHas('success');

        $product->refresh();
        $this->assertEquals(10, $product->stock); // 7 + 3 = 10 (restituido totalmente)

        $sale->refresh();
        $this->assertEquals(Sale::STATUS_REFUNDED, $sale->status);
        $this->assertFalse($sale->canBeRefunded());
    }

    public function test_reports_and_dashboard_metrics_accuracy_and_tenant_isolation(): void
    {
        [$bizA, $adminA] = $this->createBusinessWithAdminAndCashier('repA');
        [$bizB, $adminB] = $this->createBusinessWithAdminAndCashier('repB');

        app(TenantManager::class)->setTenant($bizA);

        $productA = Product::create([
            'business_id' => $bizA->id,
            'sku' => 'PROD-A-' . rand(1000, 9999),
            'name' => 'Sierra Circular 7-1/4"',
            'category' => 'Herramientas Eléctricas',
            'cost' => 50.00,
            'price' => 80.00,
            'stock' => 15,
            'min_stock' => 2,
            'status' => Product::STATUS_ACTIVE,
        ]);

        // Registrar venta en Empresa A
        $this->actingAs($adminA)->postJson(route('sales.store'), [
            'payment_method' => 'cash',
            'paid_amount' => 100.00,
            'items' => [
                [
                    'product_id' => $productA->id,
                    'quantity' => 1,
                    'unit_price' => 80.00,
                ],
            ],
        ]);

        // Consulta de Dashboard de Empresa A
        $dashResponseA = $this->actingAs($adminA)->get(route('dashboard'));
        $dashResponseA->assertOk();
        $dashResponseA->assertViewHas('dailyTotal', function ($val) {
            return (float) $val > 0;
        });
        $dashResponseA->assertViewHas('monthlyGrossProfit', function ($val) {
            // Ganancia bruta = 80 - 50 = 30
            return (float) $val >= 30.00;
        });

        // Reportes contables de Empresa A
        $reportsRespA = $this->actingAs($adminA)->get(route('reports.index'));
        $reportsRespA->assertOk();
        $reportsRespA->assertViewHas('grossMargin', function ($val) {
            return (float) $val >= 30.00;
        });

        // Comprobar aislamiento multitenant: Admin B no debe ver las ventas de A
        app(TenantManager::class)->setTenant($bizB);
        $reportsRespB = $this->actingAs($adminB)->get(route('reports.index'));
        $reportsRespB->assertOk();
        $reportsRespB->assertViewHas('grossMargin', 0.0);
        $reportsRespB->assertViewHas('totalSales', 0.0);
    }

    public function test_thermal_ticket_renders_in_80mm_and_58mm_with_cordobas_currency(): void
    {
        [$bizA, $adminA, $cashierA] = $this->createBusinessWithAdminAndCashier('ticketA');
        [$bizB, $adminB] = $this->createBusinessWithAdminAndCashier('ticketB');

        app(TenantManager::class)->setTenant($bizA);

        $product = Product::create([
            'business_id' => $bizA->id,
            'sku' => 'TCK-' . rand(1000, 9999),
            'name' => 'Candado Laminado 40mm',
            'category' => 'Cerrajería',
            'cost' => 85.00,
            'price' => 150.00,
            'stock' => 25,
            'min_stock' => 5,
            'status' => Product::STATUS_ACTIVE,
        ]);

        $saleResp = $this->actingAs($cashierA)->postJson(route('sales.store'), [
            'payment_method' => 'cash',
            'paid_amount' => 200.00,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 150.00],
            ],
        ]);
        $saleResp->assertOk();
        $sale = Sale::findOrFail($saleResp->json('sale_id'));

        $ticket80 = $this->actingAs($cashierA)->get(route('sales.ticket', ['sale' => $sale->id, 'size' => '80mm']));
        $ticket80->assertOk();
        $ticket80->assertSee($sale->invoice_number);
        $ticket80->assertSee('Candado Laminado 40mm');
        $ticket80->assertSee('C$');

        $ticket58 = $this->actingAs($cashierA)->get(route('sales.ticket', ['sale' => $sale->id, 'size' => '58mm']));
        $ticket58->assertOk();
        $ticket58->assertSee('58mm');

        // Aislamiento multi-tenant: Admin B no puede ver el ticket de Empresa A
        app(TenantManager::class)->setTenant($bizB);
        $forbiddenResp = $this->actingAs($adminB)->get(route('sales.ticket', $sale->id));
        $this->assertTrue(in_array($forbiddenResp->status(), [403, 404], true));
    }

    public function test_onboarding_checklist_shows_for_new_business_and_hides_when_completed(): void
    {
        $business = Business::create([
            'name' => 'Ferretería Juanito ' . uniqid(),
            'slug' => 'ferreteria-juanito-' . uniqid(),
            'status' => 'active',
            'settings' => [
                'currency' => 'NIO',
                'tax_percentage' => 15.0,
            ],
        ]);

        $owner = User::create([
            'name' => 'Juanito Propietario',
            'email' => 'juanito_' . uniqid() . '@puntoagil.test',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'status' => 'active',
            'current_business_id' => $business->id,
        ]);
        $owner->businesses()->attach($business->id, ['role' => 'owner', 'is_default' => true]);

        app(TenantManager::class)->setTenant($business);

        // 1. Recién creado: solo el paso 1 está completo y el checklist es visible
        $dashInitial = $this->actingAs($owner)->get(route('dashboard'));
        $dashInitial->assertOk();
        $dashInitial->assertViewHas('showOnboarding', true);
        $dashInitial->assertViewHas('completedStepsCount', 1);
        $dashInitial->assertSeeText('Primeros Pasos en 3 Minutos');

        // 2. Configurar datos del ticket
        $this->actingAs($owner)->put(route('admin.business.update'), [
            'name' => $business->name,
            'phone' => '+505 8888-9999',
            'tax_percentage' => 15,
            'receipt_footer' => '¡Gracias por comprar en Ferretería Juanito!',
        ])->assertRedirect();

        // 3. Crear primer producto
        $product = Product::create([
            'business_id' => $business->id,
            'sku' => 'ONB-' . rand(1000, 9999),
            'name' => 'Cinta Métrica 3m',
            'category' => 'Medición',
            'cost' => 60.00,
            'price' => 110.00,
            'stock' => 10,
            'min_stock' => 2,
            'status' => Product::STATUS_ACTIVE,
        ]);

        // 4. Abrir primera caja registradora
        $register = CashRegister::create([
            'business_id' => $business->id,
            'name' => 'Caja Principal',
            'status' => CashRegister::STATUS_ACTIVE,
        ]);
        $this->actingAs($owner)->post(route('cash.open'), [
            'cash_register_id' => $register->id,
            'initial_cash' => 500.00,
        ])->assertRedirect(route('cash.current'));

        // 5. Realizar primera venta en el POS
        $this->actingAs($owner)->postJson(route('sales.store'), [
            'payment_method' => 'cash',
            'paid_amount' => 200.00,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 110.00],
            ],
        ])->assertOk();

        // Al completar los 5 pasos, el checklist se oculta automáticamente
        $dashCompleted = $this->actingAs($owner)->get(route('dashboard'));
        $dashCompleted->assertOk();
        $dashCompleted->assertViewHas('completedStepsCount', 5);
        $dashCompleted->assertViewHas('showOnboarding', false);
        $dashCompleted->assertDontSeeText('Primeros Pasos en 3 Minutos');
    }
}
