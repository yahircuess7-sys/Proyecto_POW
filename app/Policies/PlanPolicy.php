<?php

namespace App\Policies;

use App\Models\Plan;
use App\Models\User;

class PlanPolicy
{
    /**
     * Cualquier usuario activo puede ver los planes disponibles.
     */
    public function viewAny(User $user): bool
    {
        return $user->status === 'active';
    }

    public function view(User $user, Plan $plan): bool
    {
        return $user->status === 'active';
    }

    /**
     * Solo Super Administradores de la plataforma pueden gestionar planes.
     */
    public function create(User $user): bool
    {
        return $user->status === 'active' && $user->isSuperAdmin();
    }

    public function update(User $user, Plan $plan): bool
    {
        return $user->status === 'active' && $user->isSuperAdmin();
    }

    public function delete(User $user, Plan $plan): bool
    {
        return $user->status === 'active' && $user->isSuperAdmin();
    }
}
