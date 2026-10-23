<?php

namespace App\Services;

use App\Models\Business;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TenantManager
{
    protected ?Business $currentTenant = null;

    /**
     * Establece el negocio activo para el contexto de la solicitud.
     */
    public function setTenant(?Business $tenant): void
    {
        $this->currentTenant = $tenant;

        if ($tenant) {
            try {
                // Sincroniza el parámetro de sesión en PostgreSQL para soporte RLS en capa de datos
                DB::statement("SET app.current_business_id = '{$tenant->id}'");
            } catch (\Throwable $e) {
                // Silencioso si la conexión aún no está inicializada o en modo testing de baja conexión
                Log::debug("No se pudo asignar app.current_business_id en PostgreSQL: " . $e->getMessage());
            }
        } else {
            try {
                DB::statement("RESET app.current_business_id");
            } catch (\Throwable $e) {
                // Silencioso
            }
        }
    }

    /**
     * Obtiene el negocio activo en el contexto actual.
     */
    public function getTenant(): ?Business
    {
        return $this->currentTenant;
    }

    /**
     * Obtiene el ID del negocio activo.
     */
    public function getTenantId(): ?int
    {
        return $this->currentTenant?->id;
    }

    /**
     * Verifica si hay un negocio activo configurado.
     */
    public function hasTenant(): bool
    {
        return $this->currentTenant !== null;
    }

    /**
     * Limpia el contexto de tenant.
     */
    public function clear(): void
    {
        $this->setTenant(null);
    }
}
