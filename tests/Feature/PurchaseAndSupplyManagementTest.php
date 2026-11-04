<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use App\Services\TenantManager;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PurchaseAndSupplyManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    private function createBusinessWithAdminAndCashier(string $prefix = 'm9'): array
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
            'email' => "admin_{$prefix}_" . uniqid() . '@test.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'status' => 'active',
            'current_business_id' => $business->id,
        ]);
        $admin->businesses()->attach($business->id, ['role' => 'owner', 'is_default' => true]);

        $cashier = User::create([
            'name' => "Cajero {$prefix}",
            'email' => "cajero_{$prefix}_" . uniqid() . '@test.com',
            'password' => Hash::make('password123'),
            'role' => 'cajero',
            'status' => 'active',
            'current_business_id' => $business->id,
        ]);
        $cashier->businesses()->attach($business->id, ['role' => 'cajero', 'is_default' => true]);

        return [$business, $admin, $cashier];
    }

    public function test_pending_purchase_does_not_affect_inventory_until_explicitly_received(): void
    {
        [$biz, $admin] = $this->createBusinessWithAdminAndCashier('pendingBiz');
        app(TenantManager::class)->setTenant($biz);

        $supplier = Supplier::create([
            'business_id' => $biz->id,
            'tax_id' => '17' . rand(100000000, 999999999) . '001',
            'name' => 'Importadora Ferretera del Sur',
            'status' => Supplier::STATUS_ACTIVE,
        ]);

        $product = Product::create([
            'business_id' => $biz->id,
            'name' => 'Disco de Corte Fino 4 1/2',
            'category' => 'Abrasivos',
            'sku' => 'DIS-COR-' . rand(1000, 9999),
            'cost' => 1.20,
            'price' => 2.50,
            'stock' => 25,
            'min_stock' => 10,
            'status' => Product::STATUS_ACTIVE,
        ]);

        // 1. Registrar compra como PENDIENTE (status = 'pending')
        $response = $this->actingAs($admin)->post(route('purchases.store'), [
            'supplier_id' => $supplier->id,
            'status' => Purchase::STATUS_PENDING,
            'purchase_date' => '2026-11-04',
            'supplier_invoice_number' => 'FAC-001-9988',
            'discount' => 5.00,
            'notes' => 'Pedido en tránsito.',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 100,
                    'unit_cost' => 0.95,
                    'discount' => 0.00,
                ],
            ],
        ]);

        $purchase = Purchase::where('supplier_id', $supplier->id)->latest('id')->first();
        $this->assertNotNull($purchase);
        $response->assertRedirect(route('purchases.show', $purchase->id));

        $this->assertEquals(Purchase::STATUS_PENDING, $purchase->status);
        $this->assertNull($purchase->received_at);
        $this->assertNull($purchase->received_by);

        // 2. CRUCIAL: El stock NO debe cambiar mientras esté pendiente
        $product->refresh();
        $this->assertEquals(25, $product->stock, 'El stock no debió alterarse en una compra pendiente.');

        // Tampoco debe existir ningún movimiento en Kardex
        $movementCount = InventoryMovement::where('reference_type', Purchase::class)
            ->where('reference_id', $purchase->id)
            ->count();
        $this->assertEquals(0, $movementCount, 'No deben existir movimientos de inventario para compras pendientes.');

        // 3. Confirmar la recepción física de la mercancía
        $receiveResp = $this->actingAs($admin)->patch(route('purchases.receive', $purchase->id));
        $receiveResp->assertRedirect(route('purchases.show', $purchase->id));
        $receiveResp->assertSessionHas('success');

        $purchase->refresh();
        $this->assertEquals(Purchase::STATUS_RECEIVED, $purchase->status);
        $this->assertNotNull($purchase->received_at);
        $this->assertEquals($admin->id, $purchase->received_by);

        // 4. Ahora sí, el stock debe haber incrementado
        $product->refresh();
        $this->assertEquals(125, $product->stock, 'El stock debió pasar de 25 a 125 tras la recepción.');
        $this->assertEquals(0.95, (float) $product->cost, 'El costo debió actualizarse a $0.95.');

        // Debe haberse generado el movimiento en Kardex
        $movement = InventoryMovement::where('reference_type', Purchase::class)
            ->where('reference_id', $purchase->id)
            ->first();
        $this->assertNotNull($movement);
        $this->assertEquals(InventoryMovement::TYPE_IN, $movement->type);
        $this->assertEquals(100, $movement->quantity);
        $this->assertEquals(25, $movement->previous_stock);
        $this->assertEquals(125, $movement->new_stock);
    }

    public function test_prevents_duplicate_reception_idempotency(): void
    {
        [$biz, $admin] = $this->createBusinessWithAdminAndCashier('duplicateBiz');
        app(TenantManager::class)->setTenant($biz);

        $supplier = Supplier::create([
            'business_id' => $biz->id,
            'tax_id' => '17' . rand(100000000, 999999999) . '001',
            'name' => 'Aceros del Norte',
            'status' => Supplier::STATUS_ACTIVE,
        ]);

        $product = Product::create([
            'business_id' => $biz->id,
            'name' => 'Clavos 2 1/2 Pulgadas',
            'category' => 'Fijaciones',
            'sku' => 'CLA-25-' . rand(1000, 9999),
            'cost' => 2.00,
            'price' => 3.50,
            'stock' => 50,
            'status' => Product::STATUS_ACTIVE,
        ]);

        // Registrar compra ya recibida inmediatamente
        $this->actingAs($admin)->post(route('purchases.store'), [
            'supplier_id' => $supplier->id,
            'status' => Purchase::STATUS_RECEIVED,
            'purchase_date' => '2026-11-04',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 20,
                    'unit_cost' => 1.80,
                ],
            ],
        ]);

        $purchase = Purchase::where('supplier_id', $supplier->id)->latest('id')->first();
        $product->refresh();
        $this->assertEquals(70, $product->stock);

        // Intentar recibirla nuevamente
        $secondReceiveResp = $this->actingAs($admin)->patch(route('purchases.receive', $purchase->id));
        $secondReceiveResp->assertSessionHas('error');

        // El stock NO debe cambiar de 70
        $product->refresh();
        $this->assertEquals(70, $product->stock, 'El stock se duplicaría si no existiera protección de idempotencia.');
    }

    public function test_cancelling_pending_purchase_and_protection_against_cancelling_received(): void
    {
        [$biz, $admin] = $this->createBusinessWithAdminAndCashier('cancelBiz');
        app(TenantManager::class)->setTenant($biz);

        $supplier = Supplier::create([
            'business_id' => $biz->id,
            'tax_id' => '17' . rand(100000000, 999999999) . '001',
            'name' => 'Herramientas Globales',
            'status' => Supplier::STATUS_ACTIVE,
        ]);

        $product = Product::create([
            'business_id' => $biz->id,
            'name' => 'Flexómetro 5 Metros',
            'category' => 'Herramientas',
            'sku' => 'FLE-05M-' . rand(1000, 9999),
            'cost' => 3.00,
            'price' => 6.00,
            'stock' => 10,
            'status' => Product::STATUS_ACTIVE,
        ]);

        // 1. Crear compra pendiente
        $this->actingAs($admin)->post(route('purchases.store'), [
            'supplier_id' => $supplier->id,
            'status' => Purchase::STATUS_PENDING,
            'purchase_date' => '2026-11-04',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 15,
                    'unit_cost' => 2.50,
                ],
            ],
        ]);

        $purchase = Purchase::where('supplier_id', $supplier->id)->latest('id')->first();

        // 2. Cancelar orden pendiente
        $cancelResp = $this->actingAs($admin)->patch(route('purchases.cancel', $purchase->id));
        $cancelResp->assertRedirect(route('purchases.show', $purchase->id));
        $cancelResp->assertSessionHas('success');

        $purchase->refresh();
        $this->assertEquals(Purchase::STATUS_CANCELLED, $purchase->status);

        // El stock permanece intacto
        $product->refresh();
        $this->assertEquals(10, $product->stock);

        // No se puede recibir una compra cancelada
        $receiveCancelledResp = $this->actingAs($admin)->patch(route('purchases.receive', $purchase->id));
        $receiveCancelledResp->assertSessionHas('error');
    }

    public function test_discounts_and_financial_totals_calculation(): void
    {
        [$biz, $admin] = $this->createBusinessWithAdminAndCashier('calcBiz');
        app(TenantManager::class)->setTenant($biz);

        $supplier = Supplier::create([
            'business_id' => $biz->id,
            'tax_id' => '17' . rand(100000000, 999999999) . '001',
            'name' => 'Distribuidora Mayorista Pro',
            'status' => Supplier::STATUS_ACTIVE,
        ]);

        $product1 = Product::create([
            'business_id' => $biz->id,
            'name' => 'Pintura Látex Blanca 1 Galón',
            'category' => 'Pinturas',
            'sku' => 'PIN-LAT-' . rand(1000, 9999),
            'cost' => 10.00,
            'price' => 18.00,
            'stock' => 5,
            'status' => Product::STATUS_ACTIVE,
        ]);

        $product2 = Product::create([
            'business_id' => $biz->id,
            'name' => 'Brocha 3 Pulgadas',
            'category' => 'Pinturas',
            'sku' => 'BRO-3P-' . rand(1000, 9999),
            'cost' => 2.00,
            'price' => 4.00,
            'stock' => 10,
            'status' => Product::STATUS_ACTIVE,
        ]);

        // Compra:
        // Item 1: 10 galones * $10 = $100, descuento $10 -> subtotal neto = $90
        // Item 2: 5 brochas * $2 = $10, descuento $0 -> subtotal neto = $10
        // Descuento global = $10
        // Subtotal bruto = $110
        // Total descuentos = $20 ($10 item1 + $10 global)
        // Subtotal neto imponible = $90
        // IVA 12% de $90 = $10.80
        // Total = $100.80
        $this->actingAs($admin)->post(route('purchases.store'), [
            'supplier_id' => $supplier->id,
            'status' => Purchase::STATUS_RECEIVED,
            'purchase_date' => '2026-11-04',
            'discount' => 10.00,
            'items' => [
                [
                    'product_id' => $product1->id,
                    'quantity' => 10,
                    'unit_cost' => 10.00,
                    'discount' => 10.00,
                ],
                [
                    'product_id' => $product2->id,
                    'quantity' => 5,
                    'unit_cost' => 2.00,
                    'discount' => 0.00,
                ],
            ],
        ]);

        $purchase = Purchase::where('supplier_id', $supplier->id)->latest('id')->first();
        $this->assertNotNull($purchase);

        $this->assertEquals(110.00, (float) $purchase->subtotal);
        $this->assertEquals(20.00, (float) $purchase->discount);
        $this->assertEquals(10.80, (float) $purchase->tax);
        $this->assertEquals(100.80, (float) $purchase->total);
    }
}
