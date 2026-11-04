<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthAndAccessControlTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_cashier_can_view_inventory_but_is_forbidden_from_mutating_products(): void
    {
        $business = Business::where('slug', 'puntoagil-central')->first() ?: Business::firstOrFail();

        $cashier = User::firstOrCreate(
            ['email' => 'cajero@puntoagil.com'],
            [
                'name' => 'Cajero Central',
                'password' => Hash::make('password123'),
                'role' => 'cajero',
                'status' => 'active',
                'current_business_id' => $business->id,
            ]
        );
        $cashier->update(['status' => 'active', 'role' => 'cajero', 'current_business_id' => $business->id]);
        if (! $cashier->businesses()->where('businesses.id', $business->id)->exists()) {
            $cashier->businesses()->attach($business->id, ['role' => 'cajero', 'is_default' => true]);
        }

        $product = Product::withoutBusinessScope()->where('business_id', $business->id)->first();
        if (! $product) {
            $product = Product::create([
                'business_id' => $business->id,
                'sku' => 'CAJ-PROD-' . uniqid(),
                'name' => 'Producto Demo Cajero',
                'category' => 'General',
                'cost' => 5.00,
                'price' => 10.00,
                'stock' => 20,
                'min_stock' => 2,
                'unit' => 'Unidades',
                'status' => 'active',
            ]);
        }

        // El cajero puede consultar el catálogo y el detalle de productos
        $this->actingAs($cashier)->get('/inventory')->assertStatus(200);
        $this->actingAs($cashier)->get('/inventory/' . $product->id)->assertStatus(200);

        // El servidor bloquea con HTTP 403 cualquier intento de creación, edición o eliminación por un cajero
        $this->actingAs($cashier)->get('/inventory/create')->assertStatus(403);
        $this->actingAs($cashier)->post('/inventory', [
            'name' => 'Intento No Autorizado',
            'category' => 'Herramientas',
            'price' => 10.00,
            'stock' => 5,
            'unit' => 'Unidades',
        ])->assertStatus(403);

        $this->actingAs($cashier)->get('/inventory/' . $product->id . '/edit')->assertStatus(403);
        $this->actingAs($cashier)->put('/inventory/' . $product->id, [
            'name' => 'Cambio Precio Ilegal',
            'category' => $product->category,
            'price' => 0.01,
            'stock' => $product->stock,
            'unit' => $product->unit,
        ])->assertStatus(403);

        $this->actingAs($cashier)->delete('/inventory/' . $product->id)->assertStatus(403);
    }

    public function test_role_hierarchy_distinguishes_owner_admin_cashier_and_super_admin(): void
    {
        $business = Business::where('slug', 'puntoagil-central')->firstOrFail();
        $owner = User::where('email', 'admin@puntoagil.com')->firstOrFail();

        // Crear un Administrador regular (no propietario) en la misma empresa
        $regularAdmin = User::create([
            'name' => 'Admin Sucursal',
            'email' => 'subadmin_' . uniqid() . '@puntoagil.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'status' => 'active',
            'current_business_id' => $business->id,
        ]);
        $regularAdmin->businesses()->attach($business->id, [
            'role' => 'admin',
            'is_default' => true,
        ]);

        // Crear un Super Admin global
        $superAdmin = User::create([
            'name' => 'Super Admin Plataforma',
            'email' => 'root_' . uniqid() . '@puntoagil.com',
            'password' => Hash::make('password123'),
            'role' => 'super_admin',
            'is_super_admin' => true,
            'status' => 'active',
            'current_business_id' => $business->id,
        ]);

        try {
            // 1. Verificar Gates de negocio y plataforma
            $this->assertTrue(Gate::forUser($superAdmin)->allows('manage-platform'));
            $this->assertFalse(Gate::forUser($owner)->allows('manage-platform'));
            $this->assertTrue(Gate::forUser($owner)->allows('manage-business-settings'));
            $this->assertFalse(Gate::forUser($regularAdmin)->allows('manage-business-settings'));

            // 2. El Administrador regular NO puede editar ni eliminar al Propietario del negocio (403)
            $this->actingAs($regularAdmin)
                ->get('/admin/users/' . $owner->id . '/edit')
                ->assertStatus(403);

            $this->actingAs($regularAdmin)
                ->delete('/admin/users/' . $owner->id)
                ->assertStatus(403);

            // 3. El Administrador regular NO puede crear otro Administrador ni un Propietario (403)
            $this->actingAs($regularAdmin)->post('/admin/users', [
                'name' => 'Intento Escalar Admin',
                'email' => 'escalado_' . uniqid() . '@puntoagil.com',
                'password' => 'password123',
                'role' => 'owner',
                'status' => 'active',
            ])->assertStatus(403);

            // 4. El Propietario SÍ puede editar y gestionar al Administrador regular
            $this->actingAs($owner)
                ->get('/admin/users/' . $regularAdmin->id . '/edit')
                ->assertStatus(200);
        } finally {
            $regularAdmin->businesses()->detach();
            $regularAdmin->delete();
            $superAdmin->delete();
        }
    }

    public function test_google_oauth_does_not_simulate_auth_when_unconfigured_and_validates_state_when_configured(): void
    {
        // 1. Sin credenciales configuradas: redirige a login con aviso sin autenticar a nadie
        config([
            'services.google.client_id' => '',
            'services.google.client_secret' => '',
        ]);

        $response = $this->get('/auth/google');
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error');
        $this->assertGuest();

        // 2. Con credenciales configuradas: redirige al endpoint oficial OAuth 2.0 de Google con state CSRF
        config([
            'services.google.client_id' => 'test-google-client-id.apps.googleusercontent.com',
            'services.google.client_secret' => 'test-google-secret',
            'services.google.redirect' => 'http://localhost/auth/google/callback',
        ]);

        $redirectResponse = $this->get('/auth/google');
        $redirectResponse->assertStatus(302);
        $targetUrl = $redirectResponse->headers->get('Location');
        $this->assertStringStartsWith('https://accounts.google.com/o/oauth2/v2/auth?', (string) $targetUrl);
        $this->assertStringContainsString('client_id=test-google-client-id.apps.googleusercontent.com', (string) $targetUrl);

        // 3. Callback con state inválido es rechazado por protección CSRF OAuth
        $callbackResponse = $this->get('/auth/google/callback?code=fake-code&state=invalid-state');
        $callbackResponse->assertRedirect(route('login'));
        $callbackResponse->assertSessionHas('error');
        $this->assertGuest();
    }

    public function test_password_reset_flow_updates_password_using_database_token(): void
    {
        $business = Business::where('slug', 'puntoagil-central')->firstOrFail();

        $tempUser = User::create([
            'name' => 'Usuario Recuperación',
            'email' => 'reset_' . uniqid() . '@puntoagil.com',
            'password' => Hash::make('oldpassword123'),
            'role' => 'cajero',
            'status' => 'active',
            'current_business_id' => $business->id,
        ]);
        $tempUser->businesses()->attach($business->id, ['role' => 'cajero', 'is_default' => true]);

        try {
            $this->get('/forgot-password')->assertStatus(200);

            $token = Password::broker()->createToken($tempUser);

            $this->get('/reset-password/' . $token . '?email=' . urlencode($tempUser->email))
                ->assertStatus(200);

            $resetResponse = $this->post('/reset-password', [
                'token' => $token,
                'email' => $tempUser->email,
                'password' => 'newsecurepass99',
                'password_confirmation' => 'newsecurepass99',
            ]);

            $resetResponse->assertRedirect(route('login'));
            $tempUser->refresh();
            $this->assertTrue(Hash::check('newsecurepass99', $tempUser->password));
        } finally {
            $tempUser->businesses()->detach();
            $tempUser->delete();
        }
    }

    public function test_deactivated_user_in_active_session_is_immediately_logged_out(): void
    {
        $business = Business::where('slug', 'puntoagil-central')->firstOrFail();

        $tempUser = User::create([
            'name' => 'Operador Temporal',
            'email' => 'temp_session_' . uniqid() . '@puntoagil.com',
            'password' => Hash::make('password123'),
            'role' => 'cajero',
            'status' => 'active',
            'current_business_id' => $business->id,
        ]);
        $tempUser->businesses()->attach($business->id, ['role' => 'cajero', 'is_default' => true]);

        try {
            $this->actingAs($tempUser)->get('/dashboard')->assertStatus(200);

            // El administrador desactiva al usuario mientras tiene sesión abierta
            $tempUser->update(['status' => 'inactive']);

            $response = $this->actingAs($tempUser)->get('/dashboard');
            $response->assertRedirect(route('login'));
            $this->assertGuest();
        } finally {
            $tempUser->businesses()->detach();
            $tempUser->delete();
        }
    }
}
