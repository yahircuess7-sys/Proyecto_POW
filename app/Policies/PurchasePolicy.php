<?php

namespace App\Policies;

use App\Models\Purchase;
use App\Models\User;

class PurchasePolicy
{
    /**
     * Solo administradores, propietarios o super admin pueden ver el listado de compras.
     */
    public function viewAny(User $user): bool
    {
        return $user->status === 'active' && $user->isAdmin();
    }

    /**
     * Consultar detalle y comprobante de compra.
     */
    public function view(User $user, Purchase $purchase): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->status === 'active'
            && $user->belongsToBusiness($purchase->business_id)
            && $user->isAdmin($purchase->business_id);
    }

    /**
     * Registrar compras e ingresar stock a proveedores.
     */
    public function create(User $user): bool
    {
        return $user->status === 'active' && $user->isAdmin();
    }

    /**
     * Modificar compras.
     */
    public function update(User $user, Purchase $purchase): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->status === 'active'
            && $user->belongsToBusiness($purchase->business_id)
            && $user->isAdmin($purchase->business_id);
    }

    /**
     * Anular compras.
     */
    public function delete(User $user, Purchase $purchase): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->status === 'active'
            && $user->belongsToBusiness($purchase->business_id)
            && $user->isAdmin($purchase->business_id);
    }
}
