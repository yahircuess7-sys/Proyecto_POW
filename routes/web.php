<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterBusinessController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\InvitationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\BusinessController as AdminBusinessController;
use App\Http\Controllers\Admin\AuditLogController as AdminAuditLogController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\CashRegisterController;
use App\Http\Controllers\SaleReturnController;
use App\Http\Controllers\Admin\SubscriptionController as AdminSubscriptionController;
use App\Http\Controllers\Admin\SuperAdminController;
use App\Http\Controllers\SubscriptionWebhookController;

// Webhooks de Proveedores de Pago (Sin autenticación de sesión, verificados por firma criptográfica e idempotencia)
Route::post('/webhooks/subscriptions/{provider?}', [SubscriptionWebhookController::class, 'handle'])->name('webhooks.subscriptions');

Route::get('/', function () {
    return view('welcome');
})->name('home');

// Autenticación Local, Auto-Registro de Negocios y OAuth 2.0 con Google
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:6,1');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
Route::get('/logout', [LoginController::class, 'logout']);

Route::get('/register', [RegisterBusinessController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [RegisterBusinessController::class, 'register'])
    ->middleware('throttle:5,1')
    ->name('register.store');

Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])
    ->middleware('throttle:10,1')
    ->name('auth.google');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])
    ->middleware('throttle:10,1')
    ->name('auth.google.callback');

// Recuperación de Contraseña
Route::get('/forgot-password', [PasswordResetController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLinkEmail'])
    ->middleware('throttle:5,1')
    ->name('password.email');
Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
Route::post('/reset-password', [PasswordResetController::class, 'reset'])
    ->middleware('throttle:5,1')
    ->name('password.update');

// Aceptación de Invitaciones a Negocios
Route::get('/invitations/{token}', [InvitationController::class, 'show'])->name('invitations.show');
Route::post('/invitations/{token}/accept', [InvitationController::class, 'accept'])
    ->middleware('throttle:6,1')
    ->name('invitations.accept');

// Rutas Protegidas (Sesión Activa + Contexto Multiempresa)
Route::middleware(['auth', 'tenant'])->group(function () {
    // Verificación de Correo Electrónico
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    // Cambio de Empresa Activa
    Route::post('/business/switch', [AdminBusinessController::class, 'switchBusiness'])->name('business.switch');

    // Perfil Personal y Cambio de Contraseña del Usuario Autenticado
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Inventario (Lectura para Cajeros; Escritura autorizada por ProductPolicy para Owner/Admin/SuperAdmin)
    Route::get('inventory/template/csv', [ProductController::class, 'downloadTemplate'])->name('inventory.template.csv');
    Route::get('inventory/export/pdf', [ProductController::class, 'exportPdf'])->name('inventory.export.pdf');
    Route::get('inventory/export/csv', [ProductController::class, 'exportCsv'])->name('inventory.export.csv');
    Route::post('inventory/import/csv', [ProductController::class, 'importCsv'])->name('inventory.import.csv');
    Route::patch('inventory/{product}/quick-update', [ProductController::class, 'quickUpdate'])->name('inventory.quick-update');
    Route::patch('inventory/{product}/toggle-status', [ProductController::class, 'toggleStatus'])->name('inventory.toggle-status');
    Route::post('inventory/{product}/adjust', [ProductController::class, 'adjustStock'])->name('inventory.adjust');
    Route::resource('inventory', ProductController::class)
        ->parameters(['inventory' => 'product']);

    // POS / Ventas (Autorizado por SalePolicy)
    Route::get('/pos', [SaleController::class, 'create'])->name('pos.create');
    Route::post('/sales', [SaleController::class, 'store'])->name('sales.store');
    Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
    Route::get('/sales/history', [SaleController::class, 'history'])->name('sales.history');
    Route::get('/sales/{sale}/ticket', [SaleController::class, 'ticket'])->name('sales.ticket');
    Route::get('/sales/{sale}/pdf', [SaleController::class, 'exportPdf'])->name('sales.pdf');
    Route::get('/sales/{sale}', [SaleController::class, 'show'])->name('sales.show');

    // Caja y Turnos (Módulo 11)
    Route::get('/cash', [CashRegisterController::class, 'index'])->name('cash.index');
    Route::get('/cash/current', [CashRegisterController::class, 'current'])->name('cash.current');
    Route::post('/cash/open', [CashRegisterController::class, 'openShift'])->name('cash.open');
    Route::post('/cash/shifts/{shift}/movements', [CashRegisterController::class, 'recordMovement'])->name('cash.movements.store');
    Route::post('/cash/shifts/{shift}/close', [CashRegisterController::class, 'closeShift'])->name('cash.close');
    Route::get('/cash/shifts/{shift}', [CashRegisterController::class, 'show'])->name('cash.show');

    // Facturación y Devoluciones (Módulo 12)
    Route::get('/returns', [SaleReturnController::class, 'index'])->name('sales.returns.index');
    Route::get('/sales/{sale}/returns/create', [SaleReturnController::class, 'create'])->name('sales.returns.create');
    Route::post('/sales/{sale}/returns', [SaleReturnController::class, 'store'])->name('sales.returns.store');
    Route::get('/returns/{saleReturn}', [SaleReturnController::class, 'show'])->name('sales.returns.show');

    // Clientes (Directorio Comercial, registro, búsqueda y ficha técnica)
    Route::patch('customers/{customer}/toggle-status', [CustomerController::class, 'toggleStatus'])->name('customers.toggle-status');
    Route::resource('customers', CustomerController::class);

    // Proveedores (Empresas proveedoras y contactos)
    Route::patch('suppliers/{supplier}/toggle-status', [SupplierController::class, 'toggleStatus'])->name('suppliers.toggle-status');
    Route::resource('suppliers', SupplierController::class);

    // Compras a Proveedores, Recepción de Mercancía e Ingreso de Stock
    Route::patch('purchases/{purchase}/receive', [PurchaseController::class, 'receive'])->name('purchases.receive');
    Route::patch('purchases/{purchase}/cancel', [PurchaseController::class, 'cancel'])->name('purchases.cancel');
    Route::resource('purchases', PurchaseController::class)->except(['edit', 'update', 'destroy']);

    // Módulos con permiso de Administración (Super Admin, Propietario o Administrador de Empresa)
    Route::middleware(['admin'])->group(function () {
        Route::get('/reports', [SaleController::class, 'reports'])->name('reports.index');
        Route::get('/admin/reports/export', [SaleController::class, 'exportReports'])->name('admin.reports.export');

        Route::prefix('admin')->name('admin.')->group(function () {
            // Gestión de Usuarios, Estados e Invitaciones del Negocio
            Route::post('users/invite', [AdminUserController::class, 'invite'])->name('users.invite');
            Route::patch('users/{user}/toggle-status', [AdminUserController::class, 'toggleStatus'])->name('users.toggle-status');
            Route::delete('invitations/{invitation}', [AdminUserController::class, 'revokeInvitation'])->name('invitations.revoke');
            Route::resource('users', AdminUserController::class)->except(['show']);

            // Configuración del Negocio Activo (Protegido por BusinessPolicy::update -> Owner / Super Admin)
            Route::get('business/settings', [AdminBusinessController::class, 'settings'])->name('business.settings');
            Route::put('business/settings', [AdminBusinessController::class, 'updateSettings'])->name('business.update');

            // Suscripción y Licencia del Negocio (Módulo 14)
            Route::get('subscription', [AdminSubscriptionController::class, 'index'])->name('subscription.index');
            Route::post('subscription/change-plan', [AdminSubscriptionController::class, 'changePlan'])->name('subscription.change-plan');
            Route::post('subscription/checkout', [AdminSubscriptionController::class, 'checkout'])->name('subscription.checkout');
            Route::post('subscription/cancel', [AdminSubscriptionController::class, 'cancel'])->name('subscription.cancel');
            Route::post('subscription/resume', [AdminSubscriptionController::class, 'resume'])->name('subscription.resume');

            // Bitácora de Auditoría Protegida (Protegido por Gate: view-audit-logs -> Owner / Super Admin)
            Route::get('audit-logs/export', [AdminAuditLogController::class, 'exportCsv'])->name('audit.export');
            Route::get('audit-logs', [AdminAuditLogController::class, 'index'])->name('audit.index');

            // Administración Global de Negocios (Separada exclusivamente para Super Admin)
            Route::get('businesses', [AdminBusinessController::class, 'index'])->name('businesses.index');
            Route::get('businesses/create', [AdminBusinessController::class, 'create'])->name('businesses.create');
            Route::post('businesses', [AdminBusinessController::class, 'store'])->name('businesses.store');
            Route::patch('businesses/{business}/toggle-status', [AdminBusinessController::class, 'toggleStatus'])->name('businesses.toggle-status');
        });
    });

    // Panel Central de Control Super Admin Global (Módulo 15)
    Route::middleware(['can:manage-platform'])->prefix('superadmin')->name('admin.superadmin.')->group(function () {
        Route::get('/', [SuperAdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/businesses', [SuperAdminController::class, 'businesses'])->name('businesses');
        Route::post('/businesses/{business}/subscription', [SuperAdminController::class, 'updateBusinessSubscription'])->name('businesses.subscription');
        Route::post('/businesses/{business}/impersonate', [SuperAdminController::class, 'impersonate'])->name('businesses.impersonate');
        Route::get('/plans', [SuperAdminController::class, 'plans'])->name('plans');
        Route::post('/plans', [SuperAdminController::class, 'storePlan'])->name('plans.store');
        Route::patch('/plans/{plan}/toggle', [SuperAdminController::class, 'togglePlan'])->name('plans.toggle');
        Route::get('/users', [SuperAdminController::class, 'users'])->name('users');
        Route::patch('/users/{user}/toggle-super-admin', [SuperAdminController::class, 'toggleSuperAdmin'])->name('users.toggle-super');
    });
});
