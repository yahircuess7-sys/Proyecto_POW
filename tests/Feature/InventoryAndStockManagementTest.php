<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\User;
use App\Services\TenantManager;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InventoryAndStockManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    private function createBusinessWithAdminAndCashier(): array
    {
        $business = Business::create([
            'name' => 'Ferretería Demo ' . uniqid(),
            'slug' => 'ferreteria-demo-' . uniqid(),
            'status' => 'active',
        ]);

        $admin = User::create([
            'name' => 'Admin Inventario',
            'email' => 'admin_inv_' . uniqid() . '@test.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'status' => 'active',
            'current_business_id' => $business->id,
        ]);
        $admin->businesses()->attach($business->id, ['role' => 'admin', 'is_default' => true]);

        $cashier = User::create([
            'name' => 'Cajero POS',
            'email' => 'cajero_pos_' . uniqid() . '@test.com',
            'password' => Hash::make('password123'),
            'role' => 'cajero',
            'status' => 'active',
            'current_business_id' => $business->id,
        ]);
        $cashier->businesses()->attach($business->id, ['role' => 'cajero', 'is_default' => true]);

        return [$business, $admin, $cashier];
    }

    public function test_product_creation_records_initial_stock_movement_and_audit_log(): void
    {
        [$business, $admin] = $this->createBusinessWithAdminAndCashier();
        app(TenantManager::class)->setTenant($business);

        $response = $this->actingAs($admin)->post(route('inventory.store'), [
            'name' => 'Taladro Percutor Industrial',
            'sku' => 'TAL-IND-' . rand(100, 999),
            'barcode' => '7509876543210',
            'category' => 'Herramientas Eléctricas',
            'description' => 'Motor sin escobillas 20V con 2 baterías.',
            'cost' => 65.50,
            'price' => 110.00,
            'stock' => 12,
            'min_stock' => 3,
            'unit' => 'Unidades',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('inventory.index'));

        $product = Product::where('name', 'Taladro Percutor Industrial')
            ->where('business_id', $business->id)
            ->first();

        $this->assertNotNull($product);
        $this->assertEquals(12, $product->stock);
        $this->assertEquals(65.50, (float) $product->cost);
        $this->assertEquals(110.00, (float) $product->price);
        $this->assertEquals(3, $product->min_stock);
        $this->assertEquals('7509876543210', $product->barcode);
        $this->assertEquals('active', $product->status);

        // Verifica que se creó el movimiento inicial de trazabilidad
        $movement = InventoryMovement::where('product_id', $product->id)->first();
        $this->assertNotNull($movement);
        $this->assertEquals('initial', $movement->type);
        $this->assertEquals(12, $movement->quantity);
        $this->assertEquals(0, $movement->previous_stock);
        $this->assertEquals(12, $movement->new_stock);
        $this->assertEquals($admin->id, $movement->user_id);

        $this->assertDatabaseHas('audit_logs', [
            'business_id' => $business->id,
            'action' => 'product.created',
        ]);
    }

    public function test_product_toggle_status_activates_and_deactivates_product_and_excludes_from_pos(): void
    {
        [$business, $admin, $cashier] = $this->createBusinessWithAdminAndCashier();
        app(TenantManager::class)->setTenant($business);

        $product = Product::create([
            'business_id' => $business->id,
            'sku' => 'TEST-STAT-' . uniqid(),
            'name' => 'Sierra Circular Test',
            'category' => 'Herramientas Eléctricas',
            'cost' => 40.00,
            'price' => 80.00,
            'stock' => 10,
            'min_stock' => 2,
            'status' => 'active',
            'unit' => 'Unidades',
        ]);

        // 1. Activo: aparece en el POS
        $posResponse = $this->actingAs($cashier)->get(route('pos.create'));
        $posResponse->assertStatus(200);
        $posResponse->assertSee($product->name);

        // 2. Desactivar producto mediante toggle-status
        $this->actingAs($admin)->patch(route('inventory.toggle-status', $product->id))
            ->assertRedirect();

        $product->refresh();
        $this->assertEquals('inactive', $product->status);
        $this->assertFalse($product->isActive());

        // 3. Ya no debe aparecer en el catálogo de productos disponibles en el POS
        session()->forget('success');
        $posResponseInactive = $this->actingAs($cashier)->get(route('pos.create'));
        $posResponseInactive->assertStatus(200);
        $this->assertFalse($posResponseInactive->viewData('products')->contains('id', $product->id));
        $posResponseInactive->assertDontSee($product->name);

        // 4. Intentar vender un producto desactivado por petición forzada retorna error 400
        $saleAttempt = $this->actingAs($cashier)->postJson(route('sales.store'), [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 80.00],
            ],
        ]);
        $saleAttempt->assertStatus(400);
        $this->assertStringContainsString('desactivado', $saleAttempt->json('message'));
    }

    public function test_manual_stock_adjustment_creates_traceable_movement_and_prevents_negative_stock(): void
    {
        [$business, $admin] = $this->createBusinessWithAdminAndCashier();
        app(TenantManager::class)->setTenant($business);

        $product = Product::create([
            'business_id' => $business->id,
            'sku' => 'TEST-ADJ-' . uniqid(),
            'name' => 'Cemento Gris Test',
            'category' => 'Construcción',
            'cost' => 5.00,
            'price' => 8.50,
            'stock' => 20,
            'min_stock' => 5,
            'status' => 'active',
            'unit' => 'Bolsas',
        ]);

        // Entrada manual de 10 unidades
        $this->actingAs($admin)->post(route('inventory.adjust', $product->id), [
            'type' => 'in',
            'amount' => 10,
            'reason' => 'Factura de compra importación #1042',
        ])->assertRedirect();

        $product->refresh();
        $this->assertEquals(30, $product->stock);

        $inMovement = InventoryMovement::where('product_id', $product->id)->where('type', 'in')->first();
        $this->assertNotNull($inMovement);
        $this->assertEquals(10, $inMovement->quantity);
        $this->assertEquals(20, $inMovement->previous_stock);
        $this->assertEquals(30, $inMovement->new_stock);
        $this->assertEquals('Factura de compra importación #1042', $inMovement->reason);

        // Salida por merma de 4 unidades
        $this->actingAs($admin)->post(route('inventory.adjust', $product->id), [
            'type' => 'out',
            'amount' => 4,
            'reason' => 'Bolsas rotas por humedad en bodega',
        ])->assertRedirect();

        $product->refresh();
        $this->assertEquals(26, $product->stock);

        // Intento de extraer más unidades que las existentes (stock negativo bloqueado)
        $this->actingAs($admin)->post(route('inventory.adjust', $product->id), [
            'type' => 'out',
            'amount' => 50, // Solo hay 26
            'reason' => 'Intento ilegal de stock negativo',
        ])->assertRedirect();

        // El stock se mantiene inalterado en 26
        $product->refresh();
        $this->assertEquals(26, $product->stock);

        // Ajuste físico a 15 unidades fijas
        $this->actingAs($admin)->post(route('inventory.adjust', $product->id), [
            'type' => 'adjustment',
            'amount' => 15,
            'reason' => 'Conteo de inventario de fin de mes',
        ])->assertRedirect();

        $product->refresh();
        $this->assertEquals(15, $product->stock);

        $adjMovement = InventoryMovement::where('product_id', $product->id)->where('type', 'adjustment')->first();
        $this->assertNotNull($adjMovement);
        $this->assertEquals(-11, $adjMovement->quantity); // 15 - 26 = -11
        $this->assertEquals(26, $adjMovement->previous_stock);
        $this->assertEquals(15, $adjMovement->new_stock);
    }

    public function test_pos_sale_checkout_deducts_stock_concurrently_and_records_sale_movement(): void
    {
        [$business, $admin, $cashier] = $this->createBusinessWithAdminAndCashier();
        app(TenantManager::class)->setTenant($business);

        $product = Product::create([
            'business_id' => $business->id,
            'sku' => 'TEST-SALE-' . uniqid(),
            'name' => 'Brocas de Tungsteno',
            'category' => 'Herramientas',
            'cost' => 2.00,
            'price' => 5.00,
            'stock' => 8,
            'min_stock' => 2,
            'status' => 'active',
            'unit' => 'Unidades',
        ]);

        // Venta de 3 unidades en POS
        $saleResponse = $this->actingAs($cashier)->postJson(route('sales.store'), [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 3, 'unit_price' => 5.00],
            ],
        ]);

        $saleResponse->assertStatus(200);
        $saleId = $saleResponse->json('sale_id');

        $product->refresh();
        $this->assertEquals(5, $product->stock);

        // Verifica que se registró el movimiento de inventario de venta
        $saleMovement = InventoryMovement::where('product_id', $product->id)
            ->where('type', 'sale')
            ->first();

        $this->assertNotNull($saleMovement);
        $this->assertEquals(-3, $saleMovement->quantity);
        $this->assertEquals(8, $saleMovement->previous_stock);
        $this->assertEquals(5, $saleMovement->new_stock);
        $this->assertEquals($saleId, $saleMovement->reference_id);

        // Intento de comprar más unidades de las disponibles (quedan 5, intenta comprar 6)
        $insufficientStockResponse = $this->actingAs($cashier)->postJson(route('sales.store'), [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 6, 'unit_price' => 5.00],
            ],
        ]);

        $insufficientStockResponse->assertStatus(400);
        $this->assertStringContainsString('Stock insuficiente', $insufficientStockResponse->json('message'));
        $this->assertEquals(5, $product->fresh()->stock);
    }

    public function test_csv_import_validates_file_and_creates_products_with_initial_movements(): void
    {
        [$business, $admin] = $this->createBusinessWithAdminAndCashier();
        app(TenantManager::class)->setTenant($business);

        $csvContent = "SKU;Nombre;Categoria;Precio;Costo;Stock;Stock_Minimo;Unidad;Codigo_Barras;Descripcion\n"
            . "IMP-001;Clavos de Acero 2 Pulgadas;Fijaciones;4.50;2.00;100;10;Kilos;75011112222;Caja de clavos templados\n"
            . "IMP-002;Lija para Madera #80;Abrasivos;1.25;0.60;50;5;Unidades;75033334444;Pliego de lija grano 80\n";

        $file = UploadedFile::fake()->createWithContent('catalogo_importacion.csv', $csvContent);

        $response = $this->actingAs($admin)->post(route('inventory.import.csv'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('inventory.index'));

        $product1 = Product::where('sku', 'IMP-001')->where('business_id', $business->id)->first();
        $this->assertNotNull($product1);
        $this->assertEquals('Clavos de Acero 2 Pulgadas', $product1->name);
        $this->assertEquals(100, $product1->stock);

        $movement1 = InventoryMovement::where('product_id', $product1->id)->where('type', 'initial')->first();
        $this->assertNotNull($movement1);
        $this->assertEquals(100, $movement1->quantity);

        $product2 = Product::where('sku', 'IMP-002')->where('business_id', $business->id)->first();
        $this->assertNotNull($product2);
        $this->assertEquals(50, $product2->stock);

        // Verificar exportación PDF y CSV responden con HTTP 200
        $this->actingAs($admin)->get(route('inventory.export.csv'))->assertStatus(200);
        $this->actingAs($admin)->get(route('inventory.export.pdf'))->assertStatus(200);
    }

    public function test_excel_template_download_and_spanish_headers_import_and_inline_quick_update(): void
    {
        [$business, $admin] = $this->createBusinessWithAdminAndCashier();
        app(TenantManager::class)->setTenant($business);

        // 1. Descargar plantilla Excel/CSV con 1 clic
        $templateResp = $this->actingAs($admin)->get(route('inventory.template.csv'));
        $templateResp->assertOk();
        $this->assertStringContainsString('Nombre;Codigo_SKU;Categoria;Precio_Compra;Precio_Venta;Stock_Inicial', $templateResp->getContent());

        // 2. Importar archivo con formato amigable de Excel (incluyendo línea sep=;)
        $excelCsvContent = "sep=;\n"
            . "Nombre;Codigo_SKU;Categoria;Precio_Compra;Precio_Venta;Stock_Inicial;Stock_Minimo;Unidad\n"
            . "Tubo PVC 1/2 Pulgada;PVC-050;Plomería;85.00;135.00;40;8;Unidades\n";

        $file = UploadedFile::fake()->createWithContent('plantilla_rellena.csv', $excelCsvContent);

        $importResp = $this->actingAs($admin)->post(route('inventory.import.csv'), [
            'file' => $file,
        ]);
        $importResp->assertRedirect(route('inventory.index'));
        $importResp->assertSessionHas('success');

        $product = Product::where('sku', 'PVC-050')->where('business_id', $business->id)->first();
        $this->assertNotNull($product);
        $this->assertEquals('Tubo PVC 1/2 Pulgada', $product->name);
        $this->assertEquals(85.00, (float) $product->cost);
        $this->assertEquals(135.00, (float) $product->price);
        $this->assertEquals(40, $product->stock);
        $this->assertEquals(8, $product->min_stock);

        // 3. Edición rápida en línea (quick-update) desde el listado
        $quickResp = $this->actingAs($admin)->patchJson(route('inventory.quick-update', $product->id), [
            'cost' => 92.50,
            'price' => 149.00,
            'min_stock' => 12,
        ]);
        $quickResp->assertOk();
        $quickResp->assertJson([
            'success' => true,
        ]);

        $product->refresh();
        $this->assertEquals(92.50, (float) $product->cost);
        $this->assertEquals(149.00, (float) $product->price);
        $this->assertEquals(12, $product->min_stock);
    }
}
