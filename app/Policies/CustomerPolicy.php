<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    /**
     * Cualquier operador activo puede listar y buscar clientes de su empresa.
     */
    public function viewAny(User $user): bool
    {
        return $user->status === 'active';
    }

    /**
     * Consultar detalle y ficha de un cliente de la misma empresa.
     */
    public function view(User $user, Customer $customer): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->status === 'active' && $user->belongsToBusiness($customer->business_id);
    }

    /**
     * Cualquier operador activo puede registrar nuevos clientes (incluidos cajeros en POS).
     */
    public function create(User $user): bool
    {
        return $user->status === 'active';
    }

    /**
     * Modificar datos comerciales o de contacto de un cliente.
     */
    public function update(User $user, Customer $customer): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->status === 'active' && $user->belongsToBusiness($customer->business_id);
    }

    /**
     * Solo administradores, propietarios o super admin pueden desactivar o eliminar un cliente.
     */
    public function delete(User $user, Customer $customer): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->status === 'active'
            && $user->belongsToBusiness($customer->business_id)
            && $user->isAdmin($customer->business_id);
    }
}
