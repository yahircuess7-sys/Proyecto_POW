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

            // Si el usuario no tiene current_business_id asignado, resolvemos su empresa por defecto
            if (! $user->current_business_id) {
                $membership = $user->businesses()->wherePivot('is_default', true)->first() 
                    ?: $user->businesses()->first();

                if ($membership) {
                    $user->current_business_id = $membership->id;
                    $user->save();
                }
            }

            // Validar que el usuario realmente sea miembro de la empresa seleccionada
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

            // Cargar el modelo del negocio y validar su estado
            $business = Business::find($user->current_business_id);
            if (! $business || ! $business->isActive()) {
                abort(403, 'La empresa asociada a tu sesión se encuentra inactiva o suspendida.');
            }

            // Fijar el tenant en el singleton de la aplicación
            $this->tenantManager->setTenant($business);

            // Compartir la empresa y rol actual con todas las vistas Blade
            view()->share('currentBusiness', $business);
            view()->share('currentBusinessUserRole', $user->roleInCurrentBusiness());
        }

        return $next($request);
    }
}
