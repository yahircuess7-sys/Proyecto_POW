<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Customer;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use App\Services\TenantManager;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerAndSupplierManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    private function createBusinessWithAdminAndCashier(string $prefix = 'alpha'): array
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

    public function test_customer_creation_editing_and_tenant_isolation(): void
    {
        [$bizA, $adminA, $cashierA] = $this->createBusinessWithAdminAndCashier('empresaA');
        [$bizB, $adminB] = $this->createBusinessWithAdminAndCashier('empresaB');

        app(TenantManager::class)->setTenant($bizA);

        // 1. Cajero de Empresa A crea cliente
        $response = $this->actingAs($cashierA)->post(route('customers.store'), [
            'document_type' => 'DNI',
            'document_number' => '0928374651',
            'name' => 'Constructora Alvarado S.A.',
            'email' => 'compras@alvarado.com',
            'phone' => '+593998877665',
            'address' => 'Av. de las Américas 123',
            'notes' => 'Cliente preferencial con descuento en pernos.',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('customers.index'));
        $response->assertSessionHas('success');

        $customerA = Customer::where('document_number', '0928374651')->first();
        $this->assertNotNull($customerA);
        $this->assertEquals($bizA->id, $customerA->business_id);
        $this->assertEquals('Constructora Alvarado S.A.', $customerA->name);
        $this->assertEquals(Customer::STATUS_ACTIVE, $customerA->status);

        // 2. Modificación de cliente por Admin A
        $updateResp = $this->actingAs($adminA)->put(route('customers.update', $customerA->id), [
            'document_type' => 'DNI',
            'document_number' => '0928374651',
            'name' => 'Constructora Alvarado & Hijos S.A.',
            'email' => 'gerencia@alvarado.com',
            'phone' => '+593998877665',
            'address' => 'Av. de las Américas 123 y Colón',
            'status' => 'active',
        ]);

        $updateResp->assertRedirect(route('customers.index'));
        $customerA->refresh();
        $this->assertEquals('Constructora Alvarado & Hijos S.A.', $customerA->name);

        // 3. Desactivación lógica
        $toggleResp = $this->actingAs($adminA)->patch(route('customers.toggle-status', $customerA->id));
        $toggleResp->assertRedirect();
        $customerA->refresh();
        $this->assertEquals(Customer::STATUS_INACTIVE, $customerA->status);

        // 4. Aislamiento Multi-Tenant: Empresa B no puede ver ni editar al cliente de Empresa A
        app(TenantManager::class)->setTenant($bizB);

        $unauthorizedShow = $this->actingAs($adminB)->get(route('customers.show', $customerA->id));
        $this->assertTrue(in_array($unauthorizedShow->status(), [403, 404]));

        $unauthorizedEdit = $this->actingAs($adminB)->put(route('customers.update', $customerA->id), [
            'document_type' => 'DNI',
            'document_number' => '0928374651',
            'name' => 'Intrusión Ilegal',
        ]);
        $this->assertTrue(in_array($unauthorizedEdit->status(), [403, 404]));

        // 5. Unicidad por negocio: Empresa B puede registrar el MISMO documento sin colisión
        $bizBCustomerResp = $this->actingAs($adminB)->post(route('customers.store'), [
            'document_type' => 'DNI',
            'document_number' => '0928374651',
            'name' => 'Cliente en Empresa B',
            'status' => 'active',
        ]);
        $bizBCustomerResp->assertRedirect(route('customers.index'));

        $customerB = Customer::where('business_id', $bizB->id)
            ->where('document_number', '0928374651')
            ->first();
        $this->assertNotNull($customerB);
        $this->assertNotEquals($customerA->id, $customerB->id);
    }

    public function test_supplier_permissions_and_protection(): void
    {
        [$biz, $admin, $cashier] = $this->createBusinessWithAdminAndCashier('ferro');
        app(TenantManager::class)->setTenant($biz);

        // 1. Cajero no tiene permiso para crear proveedores (403)
        $cashierResp = $this->actingAs($cashier)->post(route('suppliers.store'), [
            'tax_id' => '1790011223001',
            'name' => 'Aceros del Pacífico S.A.',
        ]);
        $cashierResp->assertForbidden();

        // 2. Admin crea proveedor exitosamente
        $adminResp = $this->actingAs($admin)->post(route('suppliers.store'), [
            'tax_id' => '1790011223001',
            'name' => 'Aceros del Pacífico S.A.',
            'contact_name' => 'Ing. Roberto Mendoza',
            'email' => 'ventas@acerospacifico.com',
            'phone' => '+59322998877',
            'address' => 'Parque Industrial Lote 45',
            'notes' => 'Descuento 5% por compras superiores a $1,000.',
            'status' => 'active',
        ]);

        $adminResp->assertRedirect(route('suppliers.index'));

        $supplier = Supplier::where('business_id', $biz->id)
            ->where('tax_id', '1790011223001')
            ->first();

        $this->assertNotNull($supplier);
        $this->assertEquals('Aceros del Pacífico S.A.', $supplier->name);
        $this->assertEquals('Ing. Roberto Mendoza', $supplier->contact_name);

        // 3. Admin desactiva proveedor
        $toggleResp = $this->actingAs($admin)->patch(route('suppliers.toggle-status', $supplier->id));
        $toggleResp->assertRedirect();
        $supplier->refresh();
        $this->assertEquals(Supplier::STATUS_INACTIVE, $supplier->status);
    }

    public function test_purchase_registers_inventory_in_movement_and_updates_product_cost(): void
    {
        [$biz, $admin] = $this->createBusinessWithAdminAndCashier('compraBiz');
        app(TenantManager::class)->setTenant($biz);

        // Crear proveedor
        $supplier = Supplier::create([
            'business_id' => $biz->id,
            'tax_id' => '1788899900001',
            'name' => 'Distribuidora Ferretera Nacional',
            'status' => Supplier::STATUS_ACTIVE,
        ]);

        // Crear producto con stock inicial 10 y costo $5.00
        $product = Product::create([
            'business_id' => $biz->id,
            'name' => 'Tornillo Hexagonal 3 Pulgadas',
            'category' => 'Fijaciones',
            'sku' => 'TOR-HEX-' . rand(1000, 9999),
            'cost' => 5.00,
            'price' => 8.50,
            'stock' => 10,
            'min_stock' => 5,
            'status' => Product::STATUS_ACTIVE,
        ]);

        // Registrar compra de 50 unidades a $4.20 c/u
        $response = $this->actingAs($admin)->post(route('purchases.store'), [
            'supplier_id' => $supplier->id,
            'purchase_date' => '2026-11-04',
            'supplier_invoice_number' => '001-002-987654',
            'notes' => 'Pedido mensual de fijaciones.',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 50,
                    'unit_cost' => 4.20,
                ],
            ],
        ]);

        $purchase = Purchase::where('supplier_id', $supplier->id)->latest('id')->first();
        $this->assertNotNull($purchase);
        $response->assertRedirect(route('purchases.show', $purchase->id));

        // Subtotal = 50 * 4.20 = 210.00
        // Impuesto 12% = 25.20
        // Total = 235.20
        $this->assertEquals(210.00, (float) $purchase->subtotal);
        $this->assertEquals(25.20, (float) $purchase->tax);
        $this->assertEquals(235.20, (float) $purchase->total);
        $this->assertEquals(Purchase::STATUS_COMPLETED, $purchase->status);

        // Verificar actualización en producto
        $product->refresh();
        $this->assertEquals(60, $product->stock, 'El stock debió pasar de 10 a 60 unidades.');
        $this->assertEquals(4.20, (float) $product->cost, 'El costo unitario debió actualizarse al precio de compra.');
        $this->assertEquals($supplier->id, $product->supplier_id, 'El producto debió quedar asociado al proveedor.');

        // Verificar movimiento en Kardex / Inventario
        $movement = InventoryMovement::where('reference_type', Purchase::class)
            ->where('reference_id', $purchase->id)
            ->first();

        $this->assertNotNull($movement);
        $this->assertEquals(InventoryMovement::TYPE_IN, $movement->type);
        $this->assertEquals(50, $movement->quantity);
        $this->assertEquals(10, $movement->previous_stock);
        $this->assertEquals(60, $movement->new_stock);
        $this->assertEquals(4.20, (float) $movement->unit_cost);

        // Verificar que no se puede eliminar un proveedor con compras asociadas
        $deleteResp = $this->actingAs($admin)->delete(route('suppliers.destroy', $supplier->id));
        $deleteResp->assertSessionHas('error');
        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id]);
    }

    public function test_sale_associates_customer_and_prevents_unsafe_deletion(): void
    {
        [$biz, $admin, $cashier] = $this->createBusinessWithAdminAndCashier('ventaBiz');
        app(TenantManager::class)->setTenant($biz);

        $customer = Customer::create([
            'business_id' => $biz->id,
            'document_type' => 'RUC',
            'document_number' => '1792345678001',
            'name' => 'Distribuidora Eléctrica Quito',
            'status' => Customer::STATUS_ACTIVE,
        ]);

        $product = Product::create([
            'business_id' => $biz->id,
            'name' => 'Cable Gemelo 2x14 AWG',
            'category' => 'Electricidad',
            'sku' => 'CAB-GEM-' . rand(1000, 9999),
            'cost' => 15.00,
            'price' => 25.00,
            'stock' => 100,
            'status' => Product::STATUS_ACTIVE,
        ]);

        // Registrar venta asociada al cliente
        $saleResp = $this->actingAs($cashier)->postJson(route('sales.store'), [
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_document' => $customer->document_number,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'unit_price' => 25.00,
                ],
            ],
        ]);

        $saleResp->assertOk();
        $data = $saleResp->json();
        $this->assertTrue($data['success']);

        $sale = Sale::find($data['sale_id']);
        $this->assertNotNull($sale);
        $this->assertEquals($customer->id, $sale->customer_id);
        $this->assertEquals($customer->name, $sale->customer_name);
        $this->assertEquals($customer->document_number, $sale->customer_document);

        // Verificar métricas del cliente
        $this->assertEquals(1, $customer->totalPurchasesCount());
        $this->assertEquals((float) $sale->total, (float) $customer->totalSpent());

        // Verificar que el cliente no puede eliminarse físicamente porque tiene historial de facturación
        $deleteResp = $this->actingAs($admin)->delete(route('customers.destroy', $customer->id));
        $deleteResp->assertSessionHas('error');
        $this->assertDatabaseHas('customers', ['id' => $customer->id]);
    }
}
