<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ArchitectureAndSecurityTest extends TestCase
{
    public function test_guest_cannot_access_protected_routes(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');

        $response = $this->get('/admin/users');
        $response->assertRedirect('/login');
    }

    public function test_cashier_is_forbidden_from_admin_modules(): void
    {
        $cashier = User::where('role', 'cajero')->first();
        if (! $cashier) {
            $this->markTestSkipped('No cashier user found in database.');
        }

        $response = $this->actingAs($cashier)->get('/admin/users');
        $response->assertStatus(403);

        $response = $this->actingAs($cashier)->get('/reports');
        $response->assertStatus(403);
    }

    public function test_admin_can_access_admin_modules(): void
    {
        $admin = User::where('role', 'admin')->first();
        if (! $admin) {
            $this->markTestSkipped('No admin user found in database.');
        }

        $response = $this->actingAs($admin)->get('/admin/users');
        $response->assertStatus(200);

        $response = $this->actingAs($admin)->get('/reports');
        $response->assertStatus(200);
    }

    public function test_login_rejects_inactive_user(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $email = 'inactivo_' . uniqid() . '@puntoagil.com';
        User::where('email', 'like', 'inactivo_%')->delete();

        $inactiveUser = User::create([
            'name' => 'Usuario Desactivado',
            'email' => $email,
            'password' => Hash::make('password123'),
            'role' => 'cajero',
            'status' => 'inactive',
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => $email,
            'password' => 'password123',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();

        $inactiveUser->delete();
    }

    public function test_product_description_is_saved_and_persisted(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $admin = User::where('role', 'admin')->first();
        if (! $admin) {
            $this->markTestSkipped('No admin user found in database.');
        }

        $skuTest = 'TEST-' . time();
        $response = $this->actingAs($admin)->post('/inventory', [
            'sku' => $skuTest,
            'name' => 'Artículo Técnico de Prueba',
            'category' => 'Herramientas y Equipos',
            'description' => 'Descripción técnica de alta resistencia galvanizada.',
            'price' => 3.50,
            'stock' => 20,
            'unit' => 'Unidades',
        ]);

        $response->assertRedirect(route('inventory.index'));

        $product = Product::where('sku', $skuTest)->first();
        $this->assertNotNull($product);
        $this->assertEquals('Descripción técnica de alta resistencia galvanizada.', $product->description);

        $product->delete();
    }

    public function test_all_redesigned_views_render_successfully(): void
    {
        $this->get('/')->assertStatus(200);
        $this->get('/login')->assertStatus(200);

        $admin = User::where('role', 'admin')->first();
        if (! $admin) {
            $this->markTestSkipped('No admin user found in database.');
        }

        $this->actingAs($admin)->get('/dashboard')->assertStatus(200);
        $this->actingAs($admin)->get('/inventory')->assertStatus(200);
        $this->actingAs($admin)->get('/inventory/create')->assertStatus(200);
        $this->actingAs($admin)->get('/sales')->assertStatus(200);
        $this->actingAs($admin)->get('/admin/users')->assertStatus(200);
        $this->actingAs($admin)->get('/admin/users/create')->assertStatus(200);
        $this->actingAs($admin)->get('/reports')->assertStatus(200);
    }
}
