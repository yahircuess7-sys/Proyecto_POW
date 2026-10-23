<?php

namespace App\Traits;

use App\Models\Business;
use App\Models\Scopes\BusinessScope;
use App\Services\TenantManager;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToBusiness
{
    /**
     * Inicializa el trait vinculando el alcance global y asignación automática de tenant.
     */
    protected static function bootBelongsToBusiness(): void
    {
        static::addGlobalScope(new BusinessScope());

        static::creating(function ($model) {
            if (empty($model->business_id)) {
                $tenantManager = app(TenantManager::class);
                $tenantId = $tenantManager->getTenantId() ?: auth()->user()?->current_business_id;

                if ($tenantId) {
                    $model->business_id = $tenantId;
                }
            }
        });
    }

    /**
     * Relación con la empresa a la que pertenece el registro comercial.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Desactiva el alcance global de tenant para consultas administrativas o auditorías.
     */
    public function scopeWithoutBusinessScope($query)
    {
        return $query->withoutGlobalScope(BusinessScope::class);
    }
}
