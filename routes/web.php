<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\InvitationController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\BusinessController as AdminBusinessController;
use App\Http\Controllers\Admin\AuditLogController as AdminAuditLogController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\PurchaseController;

Route::get('/', function () {
    return view('welcome');
})->name('home');

// Autenticación Local y OAuth 2.0 con Google
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:6,1');
Route::match(['get', 'post'], '/logout', [LoginController::class, 'logout'])->name('logout');

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

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Inventario (Lectura para Cajeros; Escritura autorizada por ProductPolicy para Owner/Admin/SuperAdmin)
    Route::get('inventory/export/pdf', [ProductController::class, 'exportPdf'])->name('inventory.export.pdf');
    Route::get('inventory/export/csv', [ProductController::class, 'exportCsv'])->name('inventory.export.csv');
    Route::post('inventory/import/csv', [ProductController::class, 'importCsv'])->name('inventory.import.csv');
    Route::patch('inventory/{product}/toggle-status', [ProductController::class, 'toggleStatus'])->name('inventory.toggle-status');
    Route::post('inventory/{product}/adjust', [ProductController::class, 'adjustStock'])->name('inventory.adjust');
    Route::resource('inventory', ProductController::class)
        ->parameters(['inventory' => 'product']);

    // POS / Ventas (Autorizado por SalePolicy)
    Route::get('/pos', [SaleController::class, 'create'])->name('pos.create');
    Route::post('/sales', [SaleController::class, 'store'])->name('sales.store');
    Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
    Route::get('/sales/{sale}/pdf', [SaleController::class, 'exportPdf'])->name('sales.pdf');
    Route::get('/sales/{sale}', [SaleController::class, 'show'])->name('sales.show');

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

            // Bitácora de Auditoría Protegida (Protegido por Gate: view-audit-logs -> Owner / Super Admin)
            Route::get('audit-logs', [AdminAuditLogController::class, 'index'])->name('audit.index');

            // Administración Global de Negocios (Separada exclusivamente para Super Admin)
            Route::get('businesses', [AdminBusinessController::class, 'index'])->name('businesses.index');
            Route::get('businesses/create', [AdminBusinessController::class, 'create'])->name('businesses.create');
            Route::post('businesses', [AdminBusinessController::class, 'store'])->name('businesses.store');
            Route::patch('businesses/{business}/toggle-status', [AdminBusinessController::class, 'toggleStatus'])->name('businesses.toggle-status');
        });
    });
});
