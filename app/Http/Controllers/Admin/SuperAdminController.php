<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Business;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Subscription;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\TenantManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SuperAdminController extends Controller
{
    public function __construct(
        protected TenantManager $tenantManager
    ) {}

    /**
     * Panel Principal de Métricas Globales del SaaS (Exclusivo Super Admin).
     */
    public function dashboard(): View
    {
        Gate::authorize('manage-platform');

        // Métricas de Empresas
        $totalBusinesses = Business::count();
        $activeBusinesses = Business::where('status', 'active')->count();
        $suspendedBusinesses = Business::where('status', 'suspended')->count();

        // Métricas de Usuarios
        $totalUsers = User::count();
        $activeUsers = User::where('status', 'active')->count();

        // Métricas de Suscripciones
        $totalSubscriptions = Subscription::count();
        $activeSubscriptions = Subscription::whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIALING])->count();
        $pastDueSubscriptions = Subscription::where('status', Subscription::STATUS_PAST_DUE)->count();

        // Cálculo de MRR Real (Monthly Recurring Revenue)
        $mrr = (float) Subscription::whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIALING])
            ->selectRaw("SUM(CASE WHEN billing_cycle = 'yearly' THEN price / 12 ELSE price END) as mrr_total")
            ->value('mrr_total') ?? 0.0;

        // Métricas de Actividad Global
        $totalSalesVolume = (float) Sale::withoutBusinessScope()
            ->where('status', '!=', Sale::STATUS_CANCELLED)
            ->sum('total');
        $totalProductsInPlatform = Product::withoutBusinessScope()->count();

        // Desglose de distribución por Planes
        $plansDistribution = Plan::withCount(['subscriptions' => function ($q) {
            $q->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIALING]);
        }])->orderBy('sort_order')->get();

        // Actividad reciente de Auditoría Global
        $recentAuditLogs = AuditLog::with(['user', 'business'])
            ->orderByDesc('created_at')
            ->take(10)
            ->get();

        // Empresas registradas recientemente
        $recentBusinesses = Business::with(['subscriptions.plan', 'users'])
            ->withCount([
                'users',
                'products' => fn ($q) => $q->withoutBusinessScope(),
                'sales' => fn ($q) => $q->withoutBusinessScope(),
            ])
            ->orderByDesc('created_at')
            ->take(6)
            ->get();

        return view('admin.superadmin.dashboard', compact(
            'totalBusinesses',
            'activeBusinesses',
            'suspendedBusinesses',
            'totalUsers',
            'activeUsers',
            'totalSubscriptions',
            'activeSubscriptions',
            'pastDueSubscriptions',
            'mrr',
            'totalSalesVolume',
            'totalProductsInPlatform',
            'plansDistribution',
            'recentAuditLogs',
            'recentBusinesses'
        ));
    }

    /**
     * Directorio Global y Gestión de Empresas (Tenants).
     */
    public function businesses(Request $request): View
    {
        Gate::authorize('manage-platform');

        $query = Business::with(['subscriptions.plan'])
            ->withCount([
                'users',
                'products' => fn ($q) => $q->withoutBusinessScope(),
                'sales' => fn ($q) => $q->withoutBusinessScope(),
            ]);

        if ($search = $request->input('search')) {
            $query->where(function ($sub) use ($search) {
                $sub->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('tax_id', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($planId = $request->input('plan_id')) {
            $query->whereHas('subscriptions', function ($q) use ($planId) {
                $q->where('plan_id', $planId);
            });
        }

        $businesses = $query->orderByDesc('created_at')->paginate(15)->withQueryString();
        $plans = Plan::where('is_active', true)->orderBy('sort_order')->get();

        return view('admin.superadmin.businesses', compact('businesses', 'plans'));
    }

    /**
     * Modificación administrativa de suscripción o plan de una empresa (Super Admin).
     */
    public function updateBusinessSubscription(Request $request, Business $business): RedirectResponse
    {
        Gate::authorize('manage-platform');

        $validated = $request->validate([
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'status' => ['required', 'string', 'in:active,trialing,pending,past_due,cancelled,suspended'],
            'ends_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $plan = Plan::findOrFail($validated['plan_id']);

        DB::transaction(function () use ($business, $plan, $validated) {
            $subscription = $business->currentSubscription();

            if ($subscription) {
                $subscription->update([
                    'plan_id' => $plan->id,
                    'price' => $plan->price,
                    'status' => $validated['status'],
                    'ends_at' => ! empty($validated['ends_at']) ? Carbon::parse($validated['ends_at']) : $subscription->ends_at,
                    'notes' => $validated['notes'] ?? $subscription->notes,
                ]);
            } else {
                $subscription = Subscription::create([
                    'business_id' => $business->id,
                    'plan_id' => $plan->id,
                    'price' => $plan->price,
                    'status' => $validated['status'],
                    'starts_at' => now(),
                    'ends_at' => ! empty($validated['ends_at']) ? Carbon::parse($validated['ends_at']) : now()->addMonth(),
                    'payment_provider' => 'manual',
                    'notes' => $validated['notes'] ?? 'Asignación administrativa por Super Admin.',
                ]);
            }

            AuditLogger::record(
                'superadmin.subscription_updated',
                "El Super Admin actualizó administrativamente la suscripción de {$business->name} al plan {$plan->name} ({$validated['status']}).",
                $subscription,
                [
                    'business_id' => $business->id,
                    'plan_id' => $plan->id,
                    'plan_name' => $plan->name,
                    'status' => $validated['status'],
                ],
                $business->id
            );
        });

        return back()->with('success', "Suscripción de {$business->name} actualizada al plan '{$plan->name}'.");
    }

    /**
     * Gestión de Catálogo de Planes de Suscripción (Super Admin).
     */
    public function plans(): View
    {
        Gate::authorize('manage-platform');

        $plans = Plan::withCount(['subscriptions' => function ($q) {
            $q->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIALING]);
        }])->orderBy('sort_order')->get();

        return view('admin.superadmin.plans', compact('plans'));
    }

    /**
     * Crea un nuevo plan en el SaaS (Super Admin).
     */
    public function storePlan(Request $request): RedirectResponse
    {
        Gate::authorize('manage-platform');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'price' => ['required', 'numeric', 'min:0'],
            'billing_cycle' => ['required', 'string', 'in:monthly,yearly'],
            'trial_days' => ['required', 'integer', 'min:0'],
            'max_users' => ['nullable', 'integer', 'min:1'],
            'max_products' => ['nullable', 'integer', 'min:1'],
            'max_cash_registers' => ['nullable', 'integer', 'min:1'],
            'features' => ['nullable', 'array'],
        ]);

        $baseSlug = Str::slug($validated['name']) ?: 'plan';
        $slug = $baseSlug;
        $count = 1;
        while (Plan::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $count++;
        }

        $features = [];
        if (! empty($validated['features'])) {
            foreach ($validated['features'] as $featKey => $enabled) {
                $features[$featKey] = (bool) $enabled;
            }
        }

        $plan = Plan::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'],
            'price' => $validated['price'],
            'billing_cycle' => $validated['billing_cycle'],
            'trial_days' => $validated['trial_days'],
            'max_users' => $validated['max_users'] ?: null,
            'max_products' => $validated['max_products'] ?: null,
            'max_cash_registers' => $validated['max_cash_registers'] ?: null,
            'features' => $features,
            'is_active' => true,
            'sort_order' => (Plan::max('sort_order') ?? 0) + 1,
        ]);

        AuditLogger::record(
            'superadmin.plan_created',
            "Se creó el nuevo plan '{$plan->name}' en la plataforma.",
            $plan,
            ['slug' => $plan->slug, 'price' => $plan->price]
        );

        return redirect()->route('admin.superadmin.plans')
            ->with('success', "Plan '{$plan->name}' creado correctamente.");
    }

    /**
     * Alterna la disponibilidad pública de un plan (Super Admin).
     */
    public function togglePlan(Plan $plan): RedirectResponse
    {
        Gate::authorize('manage-platform');

        $newStatus = ! $plan->is_active;
        $plan->update(['is_active' => $newStatus]);

        $statusStr = $newStatus ? 'activado' : 'desactivado';
        AuditLogger::record(
            'superadmin.plan_toggled',
            "Plan '{$plan->name}' {$statusStr} en la plataforma.",
            $plan
        );

        return back()->with('success', "Plan '{$plan->name}' {$statusStr}.");
    }

    /**
     * Directorio Global de Usuarios de la Plataforma (Super Admin).
     */
    public function users(Request $request): View
    {
        Gate::authorize('manage-platform');

        $query = User::with(['businesses']);

        if ($search = $request->input('search')) {
            $query->where(function ($sub) use ($search) {
                $sub->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role = $request->input('role')) {
            if ($role === 'super_admin') {
                $query->where('is_super_admin', true);
            } else {
                $query->where('role', $role);
            }
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $users = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        return view('admin.superadmin.users', compact('users'));
    }

    /**
     * Asigna o revoca el privilegio global de Super Admin protegiendo que siempre exista al menos uno.
     */
    public function toggleSuperAdmin(User $user): RedirectResponse
    {
        Gate::authorize('manage-platform');

        $currentUser = Auth::user();

        // Protección de seguridad: No se puede revocar a sí mismo si es el único Super Admin
        if ($user->id === $currentUser->id && $user->isSuperAdmin()) {
            $superAdminsCount = User::where('is_super_admin', true)->where('status', 'active')->count();
            if ($superAdminsCount <= 1) {
                return back()->with('error', 'No puedes revocar tu propio acceso de Super Admin porque eres el único administrador global en el sistema.');
            }
        }

        $isCurrentlySuper = $user->isSuperAdmin();
        $newSuperAdminStatus = ! $isCurrentlySuper;

        $primaryPivot = $user->businesses()->orderByDesc('business_user.is_default')->first();
        $fallbackRole = ($primaryPivot && ! empty($primaryPivot->pivot?->role))
            ? (string) $primaryPivot->pivot->role
            : User::ROLE_CASHIER;

        $user->update([
            'is_super_admin' => $newSuperAdminStatus,
            'role' => $newSuperAdminStatus ? User::ROLE_SUPER_ADMIN : $fallbackRole,
        ]);

        $actionDesc = $newSuperAdminStatus ? 'concedió privilegio de Super Admin a' : 'revocó privilegio de Super Admin de';

        AuditLogger::record(
            'superadmin.privilege_toggled',
            "El administrador {$currentUser->email} {$actionDesc} {$user->email}.",
            $user,
            ['target_user_id' => $user->id, 'new_status' => $newSuperAdminStatus]
        );

        return back()->with('success', "Privilegio de Super Admin " . ($newSuperAdminStatus ? 'concedido' : 'revocado') . " para {$user->email}.");
    }

    /**
     * Cambio de contexto / suplantación protegida para auditar problemas técnicos de un negocio.
     */
    public function impersonate(Business $business): RedirectResponse
    {
        Gate::authorize('manage-platform');

        $user = Auth::user();
        $user->update(['current_business_id' => $business->id]);
        $this->tenantManager->setTenant($business);

        AuditLogger::record(
            'superadmin.impersonated',
            "El Super Admin {$user->email} ingresó en modo auditoría técnica al negocio {$business->name}.",
            $business,
            ['business_id' => $business->id]
        );

        return redirect()->route('dashboard')
            ->with('success', "Has cambiado el contexto operativo a {$business->name} (Modo Auditoría Técnica).");
    }
}
