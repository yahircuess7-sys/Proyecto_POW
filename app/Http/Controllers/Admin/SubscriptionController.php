<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\AuditLogger;
use App\Services\TenantManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function __construct(
        protected TenantManager $tenantManager
    ) {}

    /**
     * Muestra el estado de la suscripción actual del negocio, métricas de consumo y planes disponibles.
     */
    public function index(): View
    {
        Gate::authorize('viewAny', Subscription::class);

        $actor = Auth::user();
        $business = $this->tenantManager->getTenant() ?: $actor->currentBusiness;
        abort_if(! $business, 404, 'Negocio no localizado.');

        $subscription = $business->currentSubscription();

        // Si el negocio no tiene suscripción creada aún, asignar el plan oficial de $30 por defecto
        if (! $subscription) {
            $defaultPlan = Plan::where('price', 30.00)->where('is_active', true)->first()
                ?: Plan::where('slug', 'puntoagil')->first()
                ?: Plan::where('is_active', true)->first()
                ?: Plan::first();

            if ($defaultPlan) {
                $subscription = Subscription::create([
                    'business_id' => $business->id,
                    'plan_id' => $defaultPlan->id,
                    'status' => Subscription::STATUS_ACTIVE,
                    'billing_cycle' => 'monthly',
                    'price' => $defaultPlan->price,
                    'starts_at' => now(),
                    'ends_at' => now()->addMonth(),
                    'payment_provider' => 'manual',
                    'notes' => 'Suscripción oficial asignada automáticamente.',
                ]);
            }
        }

        $subscription?->load('plan');

        $plans = Plan::where('is_active', true)->orderBy('sort_order')->get();

        // Cálculo del consumo real de cuotas vs límites del plan
        $currentPlan = $subscription?->plan;
        $usage = [
            'users' => [
                'current' => $business->users()->count(),
                'max' => $currentPlan?->max_users,
                'unlimited' => $currentPlan?->isUnlimitedUsers() ?? true,
            ],
            'products' => [
                'current' => $business->products()->count(),
                'max' => $currentPlan?->max_products,
                'unlimited' => $currentPlan?->isUnlimitedProducts() ?? true,
            ],
            'cash_registers' => [
                'current' => $business->cashRegisters()->count(),
                'max' => $currentPlan?->max_cash_registers,
                'unlimited' => $currentPlan?->isUnlimitedCashRegisters() ?? true,
            ],
        ];

        return view('admin.subscription.index', compact('business', 'subscription', 'plans', 'usage'));
    }

    /**
     * Cambia el plan de suscripción del negocio con validación en servidor de cuotas existentes.
     */
    public function changePlan(Request $request): RedirectResponse
    {
        $actor = Auth::user();
        $business = $this->tenantManager->getTenant() ?: $actor->currentBusiness;
        abort_if(! $business, 404);

        $subscription = $business->currentSubscription();
        if ($subscription) {
            Gate::authorize('update', $subscription);
        } else {
            abort_unless($actor->isOwner() || $actor->isSuperAdmin(), 403);
        }

        $validated = $request->validate([
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'billing_cycle' => ['nullable', 'string', 'in:monthly,yearly'],
        ]);

        $newPlan = Plan::findOrFail($validated['plan_id']);
        $billingCycle = $validated['billing_cycle'] ?? ($subscription?->billing_cycle ?? 'monthly');

        // Control en el servidor: si se intenta degradar a un plan menor, validar que el uso actual quepa en los nuevos límites
        if (! $newPlan->isUnlimitedUsers()) {
            $userCount = $business->users()->count();
            if ($userCount > $newPlan->max_users) {
                return back()->with('error', "No es posible cambiar al plan '{$newPlan->name}' porque tu negocio tiene {$userCount} usuarios registrados y el plan admite un máximo de {$newPlan->max_users}. Debes reducir usuarios antes de degradar el plan.");
            }
        }

        if (! $newPlan->isUnlimitedProducts()) {
            $productCount = $business->products()->count();
            if ($productCount > $newPlan->max_products) {
                return back()->with('error', "No es posible cambiar al plan '{$newPlan->name}' porque tu catálogo tiene {$productCount} productos y el plan admite un máximo de {$newPlan->max_products}.");
            }
        }

        DB::transaction(function () use ($business, $subscription, $newPlan, $billingCycle, $actor) {
            $oldPlanName = $subscription?->plan?->name ?? 'Ninguno';

            if ($subscription) {
                $subscription->update([
                    'plan_id' => $newPlan->id,
                    'price' => $newPlan->price,
                    'billing_cycle' => $billingCycle,
                    'status' => Subscription::STATUS_ACTIVE,
                    'ends_at' => $billingCycle === 'yearly' ? now()->addYear() : now()->addMonth(),
                    'cancelled_at' => null,
                ]);
            } else {
                $subscription = Subscription::create([
                    'business_id' => $business->id,
                    'plan_id' => $newPlan->id,
                    'price' => $newPlan->price,
                    'billing_cycle' => $billingCycle,
                    'status' => Subscription::STATUS_ACTIVE,
                    'starts_at' => now(),
                    'ends_at' => $billingCycle === 'yearly' ? now()->addYear() : now()->addMonth(),
                    'payment_provider' => 'manual',
                ]);
            }

            AuditLogger::record(
                'subscription.plan_changed',
                "Se cambió el plan de suscripción de {$business->name} de '{$oldPlanName}' a '{$newPlan->name}'.",
                $subscription,
                [
                    'old_plan' => $oldPlanName,
                    'new_plan' => $newPlan->name,
                    'price' => $newPlan->price,
                    'billing_cycle' => $billingCycle,
                ],
                $business->id
            );
        });

        return redirect()->route('admin.subscription.index')
            ->with('success', "Plan actualizado con éxito al '{$newPlan->name}'.");
    }

    /**
     * Cancela la renovación automática de la suscripción del negocio.
     */
    public function cancel(Request $request): RedirectResponse
    {
        $actor = Auth::user();
        $business = $this->tenantManager->getTenant() ?: $actor->currentBusiness;
        abort_if(! $business, 404);

        $subscription = $business->currentSubscription();
        abort_unless($subscription, 404, 'Suscripción no encontrada.');

        Gate::authorize('cancel', $subscription);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $subscription->update([
            'status' => Subscription::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'notes' => $validated['reason'] ?? 'Cancelada por el propietario del negocio.',
        ]);

        AuditLogger::record(
            'subscription.cancelled',
            "Se canceló la suscripción de {$business->name}.",
            $subscription,
            ['reason' => $validated['reason'] ?? 'Cancelación manual'],
            $business->id
        );

        return redirect()->route('admin.subscription.index')
            ->with('success', 'La suscripción ha sido cancelada. Mantendrás acceso hasta la fecha de finalización del ciclo.');
    }

    /**
     * Reactiva una suscripción cancelada.
     */
    public function resume(): RedirectResponse
    {
        $actor = Auth::user();
        $business = $this->tenantManager->getTenant() ?: $actor->currentBusiness;
        abort_if(! $business, 404);

        $subscription = $business->currentSubscription();
        abort_unless($subscription, 404);

        Gate::authorize('update', $subscription);

        $subscription->update([
            'status' => Subscription::STATUS_ACTIVE,
            'cancelled_at' => null,
            'notes' => 'Suscripción reactivada por el propietario.',
        ]);

        AuditLogger::record(
            'subscription.resumed',
            "Se reactivó la suscripción de {$business->name}.",
            $subscription,
            [],
            $business->id
        );

        return redirect()->route('admin.subscription.index')
            ->with('success', '¡Suscripción reactivada con éxito!');
    }
}
