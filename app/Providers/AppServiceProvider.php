<?php

namespace App\Providers;

use App\Models\Business;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use App\Policies\BusinessPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\ProductPolicy;
use App\Policies\PurchasePolicy;
use App\Policies\SalePolicy;
use App\Policies\SupplierPolicy;
use App\Policies\UserPolicy;
use App\Services\TenantManager;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TenantManager::class, fn () => new TenantManager());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // Registro explícito de Políticas de Autorización por Modelo
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Sale::class, SalePolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Business::class, BusinessPolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(Supplier::class, SupplierPolicy::class);
        Gate::policy(Purchase::class, PurchasePolicy::class);

        // Gates de Capacidades Operativas por Nivel de Rol
        Gate::define('manage-platform', fn (User $user): bool => $user->status === 'active' && $user->isSuperAdmin());
        Gate::define('manage-business-settings', fn (User $user): bool => $user->status === 'active' && $user->isOwner());
        Gate::define('view-audit-logs', fn (User $user): bool => $user->status === 'active' && ($user->isOwner() || $user->isSuperAdmin()));
        Gate::define('access-admin-panel', fn (User $user): bool => $user->status === 'active' && $user->isAdmin());
        Gate::define('manage-inventory', fn (User $user): bool => $user->status === 'active' && $user->isAdmin());
        Gate::define('view-reports', fn (User $user): bool => $user->status === 'active' && $user->isAdmin());
        Gate::define('export-reports', fn (User $user): bool => $user->status === 'active' && $user->isAdmin());
        Gate::define('manage-users', fn (User $user): bool => $user->status === 'active' && $user->isAdmin());
        Gate::define('operate-pos', fn (User $user): bool => $user->status === 'active');
    }
}
