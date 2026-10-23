<?php

namespace App\Models\Scopes;

use App\Services\TenantManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class BusinessScope implements Scope
{
    /**
     * Aplica el filtro de aislamiento de negocio a la consulta de Eloquent.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantId = $tenantManager->getTenantId() ?: auth()->user()?->current_business_id;

        if ($tenantId !== null) {
            $builder->where($model->qualifyColumn('business_id'), $tenantId);
        }
    }
}
