<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;

class SupplierPolicy
{
    /**
     * Cualquier operador activo puede consultar la lista de proveedores de la empresa.
     */
    public function viewAny(User $user): bool
    {
        return $user->status === 'active';
    }

    /**
     * Consultar detalle y catálogo de compras de un proveedor.
     */
    public function view(User $user, Supplier $supplier): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->status === 'active' && $user->belongsToBusiness($supplier->business_id);
    }

    /**
     * Solo administradores, propietarios o super admin pueden dar de alta proveedores.
     */
    public function create(User $user): bool
    {
        return $user->status === 'active' && $user->isAdmin();
    }

    /**
     * Modificar datos fiscales o de contacto de un proveedor.
     */
    public function update(User $user, Supplier $supplier): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->status === 'active'
            && $user->belongsToBusiness($supplier->business_id)
            && $user->isAdmin($supplier->business_id);
    }

    /**
     * Desactivar o eliminar proveedores.
     */
    public function delete(User $user, Supplier $supplier): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->status === 'active'
            && $user->belongsToBusiness($supplier->business_id)
            && $user->isAdmin($supplier->business_id);
    }
}
