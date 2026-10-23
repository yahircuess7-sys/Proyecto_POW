<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\UserController as AdminUserController;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:6,1');
Route::match(['get', 'post'], '/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware(['auth', 'tenant'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Inventario (Accesible por roles autorizados en panel)
    Route::get('inventory/export/pdf', [ProductController::class, 'exportPdf'])->name('inventory.export.pdf');
    Route::get('inventory/export/csv', [ProductController::class, 'exportCsv'])->name('inventory.export.csv');
    Route::resource('inventory', ProductController::class)
        ->parameters(['inventory' => 'product']);
    
    // POS / Ventas
    Route::get('/pos', [SaleController::class, 'create'])->name('pos.create');
    Route::post('/sales', [SaleController::class, 'store'])->name('sales.store');
    Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
    Route::get('/sales/{sale}/pdf', [SaleController::class, 'exportPdf'])->name('sales.pdf');
    Route::get('/sales/{sale}', [SaleController::class, 'show'])->name('sales.show');
    
    // Módulos con permiso exclusivo de Administrador
    Route::middleware(['admin'])->group(function () {
        Route::get('/reports', [SaleController::class, 'reports'])->name('reports.index');
        Route::get('/admin/reports/export', [SaleController::class, 'exportReports'])->name('admin.reports.export');

        Route::prefix('admin')->name('admin.')->group(function () {
            Route::resource('users', AdminUserController::class)->except(['show']);
        });
    });
});
