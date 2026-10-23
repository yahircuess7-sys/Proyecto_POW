<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\TenantManager;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MultiTenantIsolationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_catalog_is_strictly_isolated_between_businesses(): void
    {
        // 1. Obtener negocio principal
        $centralBiz = Business::where('slug', 'puntoagil-central')->firstOrFail();
        $centralAdmin = User::where('email', 'admin@puntoagil.com')->firstOrFail();

        // 2. Crear un segundo negocio (Tenant B)
        $betaBiz = Business::create([
            'name' => 'Ferretería Beta Test',
            'slug' => 'beta-test-' . uniqid(),
            'tax_id' => '1799999999001',
            'status' => 'active',
        ]);

        $betaAdmin = User::create([
            'name' => 'Admin Beta',
            'email' => 'admin_beta_' . uniqid() . '@test.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'status' => 'active',
            'current_business_id' => $betaBiz->id,
        ]);

        $betaAdmin->businesses()->attach($betaBiz->id, [
            'role' => 'owner',
            'is_default' => true,
        ]);

        // 3. Crear producto en Empresa Beta
        $this->actingAs($betaAdmin);
        $skuBeta = 'BETA-' . time();
        $response = $this->post('/inventory', [
            'sku' => $skuBeta,
            'name' => 'Producto Exclusivo Beta',
            'category' => 'Equipos Especiales',
            'price' => 45.00,
            'stock' => 15,
            'unit' => 'Unidades',
        ]);
        $response->assertRedirect(route('inventory.index'));

        $betaProduct = Product::withoutBusinessScope()->where('sku', $skuBeta)->firstOrFail();
        $this->assertEquals($betaBiz->id, $betaProduct->business_id);

        // 4. Iniciar sesión como Administrador de Empresa Central y verificar aislamiento
        $this->actingAs($centralAdmin);

        // La consulta de inventario no debe listar el producto de la Empresa Beta
        $response = $this->get('/inventory');
        $response->assertStatus(200);
        $response->assertDontSee('Producto Exclusivo Beta');
        $response->assertDontSee($skuBeta);

        // Acceso directo por ID de otra empresa debe retornar 404
        $responseDirect = $this->get('/inventory/' . $betaProduct->id);
        $responseDirect->assertStatus(404);

        // 5. Limpieza
        $betaProduct->delete();
        $betaAdmin->delete();
        $betaBiz->delete();
    }

    public function test_sku_uniqueness_is_scoped_per_business(): void
    {
        $centralBiz = Business::where('slug', 'puntoagil-central')->firstOrFail();
        $centralAdmin = User::where('email', 'admin@puntoagil.com')->firstOrFail();

        $betaBiz = Business::create([
            'name' => 'Ferretería Gamma Test',
            'slug' => 'gamma-test-' . uniqid(),
            'status' => 'active',
        ]);

        $gammaAdmin = User::create([
            'name' => 'Admin Gamma',
            'email' => 'admin_gamma_' . uniqid() . '@test.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'status' => 'active',
            'current_business_id' => $betaBiz->id,
        ]);

        $gammaAdmin->businesses()->attach($betaBiz->id, [
            'role' => 'owner',
            'is_default' => true,
        ]);

        // Tomar un SKU que ya existe en Central (por ejemplo CEM-001)
        $existingCentralSku = 'CEM-001';

        // Gamma intenta registrar un producto con el mismo SKU que Central -> DEBE PERMITIRSE
        $this->actingAs($gammaAdmin);
        $response = $this->post('/inventory', [
            'sku' => $existingCentralSku,
            'name' => 'Cemento Marca Gamma',
            'category' => 'Materiales',
            'price' => 7.00,
            'stock' => 50,
            'unit' => 'Saco',
        ]);
        $response->assertRedirect(route('inventory.index'));

        // Pero si Gamma intenta duplicar el MISMO SKU dentro de su propio negocio -> DEBE FALLAR
        $responseDuplicate = $this->post('/inventory', [
            'sku' => $existingCentralSku,
            'name' => 'Otro Cemento Repetido',
            'category' => 'Materiales',
            'price' => 7.50,
            'stock' => 10,
            'unit' => 'Saco',
        ]);
        $responseDuplicate->assertSessionHasErrors(['sku']);

        // Limpieza
        Product::withoutBusinessScope()->where('business_id', $betaBiz->id)->delete();
        $gammaAdmin->delete();
        $betaBiz->delete();
    }

    public function test_pos_checkout_rejects_cross_tenant_products(): void
    {
        $centralBiz = Business::where('slug', 'puntoagil-central')->firstOrFail();
        $centralAdmin = User::where('email', 'admin@puntoagil.com')->firstOrFail();

        $alienBiz = Business::create([
            'name' => 'Empresa Externa',
            'slug' => 'alien-biz-' . uniqid(),
            'status' => 'active',
        ]);

        $alienProduct = Product::withoutBusinessScope()->create([
            'business_id' => $alienBiz->id,
            'sku' => 'ALIEN-' . time(),
            'name' => 'Repuesto Foráneo',
            'category' => 'Otros',
            'price' => 100.00,
            'stock' => 10,
            'unit' => 'Unidades',
        ]);

        // Usuario de Central intenta vender el producto de la Empresa Externa
        $this->actingAs($centralAdmin);
        $response = $this->postJson('/sales', [
            'items' => [
                [
                    'product_id' => $alienProduct->id,
                    'quantity' => 1,
                    'unit_price' => 100.00,
                ],
            ],
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
        ]);

        // Limpieza
        $alienProduct->delete();
        $alienBiz->delete();
    }

    public function test_user_management_is_isolated_per_business(): void
    {
        $centralBiz = Business::where('slug', 'puntoagil-central')->firstOrFail();
        $centralAdmin = User::where('email', 'admin@puntoagil.com')->firstOrFail();

        $deltaBiz = Business::create([
            'name' => 'Empresa Delta',
            'slug' => 'delta-biz-' . uniqid(),
            'status' => 'active',
        ]);

        $deltaUser = User::create([
            'name' => 'Cajero Delta Único',
            'email' => 'cajero_delta_' . uniqid() . '@test.com',
            'password' => Hash::make('password123'),
            'role' => 'cajero',
            'status' => 'active',
            'current_business_id' => $deltaBiz->id,
        ]);

        $deltaUser->businesses()->attach($deltaBiz->id, [
            'role' => 'cajero',
            'is_default' => true,
        ]);

        // Administrador Central no debe ver a Cajero Delta en su listado
        $this->actingAs($centralAdmin);
        $response = $this->get('/admin/users');
        $response->assertStatus(200);
        $response->assertDontSee($deltaUser->email);
        $response->assertDontSee('Cajero Delta Único');

        // Y no debe poder editar a Cajero Delta (404)
        $responseEdit = $this->get('/admin/users/' . $deltaUser->id . '/edit');
        $responseEdit->assertStatus(404);

        // Limpieza
        $deltaUser->delete();
        $deltaBiz->delete();
    }
}
