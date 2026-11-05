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
use Illuminate\Support\Facades\Http;
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

        // Si el negocio no tiene suscripción creada aún, asignar el plan oficial de $30 en período de prueba
        if (! $subscription) {
            $defaultPlan = Plan::where('price', 30.00)->where('is_active', true)->first()
                ?: Plan::where('slug', 'puntoagil')->first()
                ?: Plan::where('is_active', true)->first()
                ?: Plan::first();

            if ($defaultPlan) {
                $trialDays = max(1, (int) ($defaultPlan->trial_days ?: 14));
                $subscription = Subscription::create([
                    'business_id' => $business->id,
                    'plan_id' => $defaultPlan->id,
                    'status' => Subscription::STATUS_TRIALING,
                    'billing_cycle' => 'monthly',
                    'price' => $defaultPlan->price,
                    'starts_at' => now(),
                    'trial_ends_at' => now()->addDays($trialDays),
                    'ends_at' => now()->addDays($trialDays),
                    'payment_provider' => 'manual',
                    'notes' => 'Suscripción inicial en período de prueba.',
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
                'current' => $business->products()->withoutBusinessScope()->count(),
                'max' => $currentPlan?->max_products,
                'unlimited' => $currentPlan?->isUnlimitedProducts() ?? true,
            ],
            'cash_registers' => [
                'current' => $business->cashRegisters()->withoutBusinessScope()->count(),
                'max' => $currentPlan?->max_cash_registers,
                'unlimited' => $currentPlan?->isUnlimitedCashRegisters() ?? true,
            ],
        ];

        $gateways = [
            'stripe' => ! empty(config('services.stripe.secret')),
            'mercadopago' => ! empty(config('services.mercadopago.access_token')),
        ];

        return view('admin.subscription.index', compact('business', 'subscription', 'plans', 'usage', 'gateways'));
    }

    /**
     * Cambia el plan o periodicidad de suscripción del negocio con validación en servidor de cuotas existentes.
     * No extiende fechas de vencimiento ni activa suscripciones vencidas sin confirmación de pago verificada.
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

        if ($subscription && $subscription->isSuspended() && ! $actor->isSuperAdmin()) {
            return back()->with('error', 'La suscripción se encuentra suspendida administrativamente. Contacte a soporte para reactivarla.');
        }

        // Impedir que un propietario active o extienda gratis una suscripción vencida, pendiente o cancelada sin pago verificado
        if ($subscription && ! $subscription->isActive() && ! $actor->isSuperAdmin()) {
            return back()->with('error', 'No es posible activar o renovar una suscripción vencida o pendiente de pago sin completar primero el pago en la pasarela o mediante autorización administrativa.');
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
            $productCount = $business->products()->withoutBusinessScope()->count();
            if ($productCount > $newPlan->max_products) {
                return back()->with('error', "No es posible cambiar al plan '{$newPlan->name}' porque tu catálogo tiene {$productCount} productos y el plan admite un máximo de {$newPlan->max_products}.");
            }
        }

        if (! $newPlan->isUnlimitedCashRegisters()) {
            $registerCount = $business->cashRegisters()->withoutBusinessScope()->count();
            if ($registerCount > $newPlan->max_cash_registers) {
                return back()->with('error', "No es posible cambiar al plan '{$newPlan->name}' porque tu negocio tiene {$registerCount} cajas registradoras y el plan admite un máximo de {$newPlan->max_cash_registers}.");
            }
        }

        DB::transaction(function () use ($business, $subscription, $newPlan, $billingCycle, $actor) {
            $oldPlanName = $subscription?->plan?->name ?? 'Ninguno';

            if ($subscription) {
                $updateData = [
                    'plan_id' => $newPlan->id,
                    'price' => $newPlan->price,
                    'billing_cycle' => $billingCycle,
                ];

                // Solo el Super Admin puede extender fechas o activar directamente sin pasarela
                if ($actor->isSuperAdmin()) {
                    $updateData['status'] = Subscription::STATUS_ACTIVE;
                    $updateData['ends_at'] = $billingCycle === 'yearly' ? now()->addYear() : now()->addMonth();
                    $updateData['cancelled_at'] = null;
                }

                $subscription->update($updateData);
            } else {
                $trialDays = max(1, (int) ($newPlan->trial_days ?: 14));
                $subscription = Subscription::create([
                    'business_id' => $business->id,
                    'plan_id' => $newPlan->id,
                    'price' => $newPlan->price,
                    'billing_cycle' => $billingCycle,
                    'status' => $actor->isSuperAdmin() ? Subscription::STATUS_ACTIVE : Subscription::STATUS_TRIALING,
                    'starts_at' => now(),
                    'trial_ends_at' => $actor->isSuperAdmin() ? null : now()->addDays($trialDays),
                    'ends_at' => $actor->isSuperAdmin()
                        ? ($billingCycle === 'yearly' ? now()->addYear() : now()->addMonth())
                        : now()->addDays($trialDays),
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
     * Inicia una sesión de pago verificable (Checkout) con Stripe o MercadoPago.
     */
    public function checkout(Request $request): RedirectResponse
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
            'provider' => ['required', 'string', 'in:stripe,mercadopago'],
            'billing_cycle' => ['nullable', 'string', 'in:monthly,yearly'],
        ]);

        $provider = $validated['provider'];
        $billingCycle = $validated['billing_cycle'] ?? ($subscription?->billing_cycle ?? 'monthly');
        $plan = $subscription?->plan ?: Plan::where('slug', 'puntoagil')->first() ?: Plan::first();
        abort_if(! $plan, 404, 'Plan oficial no encontrado.');

        $amountUsd = $billingCycle === 'yearly' ? round((float) $plan->price * 12, 2) : (float) $plan->price;

        if ($provider === 'stripe') {
            $stripeSecret = (string) config('services.stripe.secret');
            if ($stripeSecret === '') {
                return back()->with(
                    'error',
                    'La pasarela de pagos Stripe aún no tiene credenciales configuradas en el servidor (STRIPE_SECRET). Configure las claves en .env para habilitar el cobro automático.'
                );
            }

            $response = Http::withToken($stripeSecret)
                ->asForm()
                ->timeout(15)
                ->post('https://api.stripe.com/v1/checkout/sessions', [
                    'mode' => 'payment',
                    'success_url' => route('admin.subscription.index') . '?checkout=success',
                    'cancel_url' => route('admin.subscription.index') . '?checkout=cancelled',
                    'client_reference_id' => (string) $business->id,
                    'customer_email' => $business->email ?: $actor->email,
                    'line_items[0][price_data][currency]' => 'usd',
                    'line_items[0][price_data][unit_amount]' => (int) round($amountUsd * 100),
                    'line_items[0][price_data][product_data][name]' => "{$plan->name} ({$business->name})",
                    'line_items[0][quantity]' => 1,
                    'metadata[business_id]' => (string) $business->id,
                    'metadata[subscription_id]' => (string) ($subscription?->id ?? ''),
                    'metadata[billing_cycle]' => $billingCycle,
                ]);

            if (! $response->successful() || empty($response->json('url'))) {
                return back()->with('error', 'No fue posible iniciar la sesión de pago con Stripe. Verifique sus credenciales de API.');
            }

            AuditLogger::record(
                'subscription.checkout_started',
                "Se inició sesión de Checkout en Stripe para {$business->name} por \${$amountUsd} USD.",
                $subscription,
                ['provider' => 'stripe', 'amount' => $amountUsd, 'billing_cycle' => $billingCycle],
                $business->id
            );

            return redirect()->away($response->json('url'));
        }

        $mpToken = (string) config('services.mercadopago.access_token');
        if ($mpToken === '') {
            return back()->with(
                'error',
                'La pasarela MercadoPago aún no tiene credenciales configuradas en el servidor (MERCADOPAGO_ACCESS_TOKEN). Configure las claves en .env para habilitar el cobro automático.'
            );
        }

        $mpResponse = Http::withToken($mpToken)
            ->timeout(15)
            ->post('https://api.mercadopago.com/checkout/preferences', [
                'items' => [[
                    'title' => "{$plan->name} - {$business->name}",
                    'quantity' => 1,
                    'currency_id' => 'USD',
                    'unit_price' => $amountUsd,
                ]],
                'external_reference' => (string) $business->id,
                'metadata' => [
                    'business_id' => $business->id,
                    'subscription_id' => $subscription?->id,
                    'billing_cycle' => $billingCycle,
                ],
                'back_urls' => [
                    'success' => route('admin.subscription.index') . '?checkout=success',
                    'failure' => route('admin.subscription.index') . '?checkout=failed',
                    'pending' => route('admin.subscription.index') . '?checkout=pending',
                ],
            ]);

        if (! $mpResponse->successful() || empty($mpResponse->json('init_point'))) {
            return back()->with('error', 'No fue posible iniciar la preferencia de pago con MercadoPago. Verifique sus credenciales de API.');
        }

        AuditLogger::record(
            'subscription.checkout_started',
            "Se inició preferencia de pago en MercadoPago para {$business->name} por \${$amountUsd} USD.",
            $subscription,
            ['provider' => 'mercadopago', 'amount' => $amountUsd, 'billing_cycle' => $billingCycle],
            $business->id
        );

        return redirect()->away($mpResponse->json('init_point'));
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

        if ($subscription->isSuspended() && ! $actor->isSuperAdmin()) {
            return back()->with('error', 'La suscripción se encuentra suspendida administrativamente. Contacte a soporte.');
        }

        if (in_array($subscription->status, [Subscription::STATUS_PAST_DUE, Subscription::STATUS_PENDING], true) && ! $actor->isSuperAdmin()) {
            return back()->with('error', 'No se puede reactivar una suscripción con pago pendiente o vencido sin confirmación de pago verificada.');
        }

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
