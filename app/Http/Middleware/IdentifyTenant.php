<?php

namespace App\Http\Middleware;

use App\Models\Business;
use App\Services\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    public function __construct(
        protected TenantManager $tenantManager
    ) {}

    /**
     * Intercepta la petición para identificar, validar y fijar el contexto del negocio (Tenant).
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            // Si la cuenta fue desactivada mientras tenía sesión abierta, revocar sesión inmediatamente
            if ($user->status !== 'active') {
                Auth::logout();
                $this->tenantManager->clear();

                if ($request->hasSession()) {
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                }

                return redirect()->route('login')->withErrors([
                    'email' => 'Esta cuenta ha sido desactivada. Por favor, contacte al administrador.',
                ]);
            }

            // Si el usuario no tiene current_business_id asignado, resolvemos su empresa por defecto
            if (! $user->current_business_id) {
                $membership = $user->businesses()->wherePivot('is_default', true)->first()
                    ?: $user->businesses()->first();

                if (! $membership && $user->isSuperAdmin()) {
                    $membership = Business::where('status', 'active')->first();
                }

                if ($membership) {
                    $user->current_business_id = $membership->id;
                    $user->save();
                }
            }

            // Validar que el usuario realmente sea miembro de la empresa seleccionada (salvo Super Admin)
            if (! $user->isSuperAdmin()) {
                $isMember = $user->businesses()->where('businesses.id', $user->current_business_id)->exists();
                if (! $isMember) {
                    $fallback = $user->businesses()->first();
                    if ($fallback) {
                        $user->current_business_id = $fallback->id;
                        $user->save();
                    } else {
                        abort(403, 'Tu cuenta no tiene una empresa asignada o autorizada en PuntoÁgil.');
                    }
                }
            }

            // Cargar el modelo del negocio y validar su estado
            $business = Business::find($user->current_business_id);
            if (! $business || (! $business->isActive() && ! $user->isSuperAdmin())) {
                abort(403, 'La empresa asociada a tu sesión se encuentra inactiva o suspendida.');
            }

            // Fijar el tenant en el singleton de la aplicación
            $this->tenantManager->setTenant($business);

            // Aplicar zona horaria configurada para el negocio activo en tiempo de ejecución
            if ($business) {
                $businessTimezone = (string) ($business->getSetting('timezone', config('app.timezone')) ?: 'America/Managua');
                if (in_array($businessTimezone, timezone_identifiers_list(), true)) {
                    config(['app.timezone' => $businessTimezone]);
                    @date_default_timezone_set($businessTimezone);
                }
            }

            // Compartir la empresa y rol actual con todas las vistas Blade
            view()->share('currentBusiness', $business);
            view()->share('currentBusinessUserRole', $user->roleLabel($business?->id));

            // Control en servidor del estado de la licencia/suscripción (Módulo 14 y 17)
            if ($business && ! $user->isSuperAdmin()) {
                $subscription = $business->currentSubscription();
                if ($subscription && ! $subscription->isActive()) {
                    $allowedRoutesWhenInactive = [
                        'admin.subscription.*',
                        'business.switch',
                        'profile.*',
                        'logout',
                        'verification.*',
                    ];

                    if (! $request->routeIs($allowedRoutesWhenInactive)) {
                        if ($request->expectsJson() || $request->ajax()) {
                            return response()->json([
                                'success' => false,
                                'message' => 'La suscripción de la empresa no se encuentra activa (' . $subscription->statusLabel() . '). Regularice su licencia para operar.',
                            ], 403);
                        }

                        if ($user->isOwner() || $user->isAdmin()) {
                            return redirect()->route('admin.subscription.index')
                                ->with('error', 'La suscripción de tu empresa se encuentra en estado "' . $subscription->statusLabel() . '". Regulariza tu plan para continuar operando.');
                        }

                        abort(403, 'La suscripción de la empresa se encuentra vencida o suspendida. Contacte al propietario del negocio.');
                    }
                }
            }
        }

        return $next($request);
    }
}
