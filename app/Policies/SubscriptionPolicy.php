<?php

namespace App\Policies;

use App\Models\Subscription;
use App\Models\User;

class SubscriptionPolicy
{
    /**
     * Acceso al estado de suscripción de la empresa.
     */
    public function viewAny(User $user): bool
    {
        return $user->status === 'active' && ($user->isAdmin() || $user->isSuperAdmin());
    }

    public function view(User $user, Subscription $subscription): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->status === 'active' && $user->belongsToBusiness($subscription->business_id) && $user->isAdmin($subscription->business_id);
    }

    /**
     * Solo el propietario (owner) o un Super Admin pueden modificar o cambiar el plan de suscripción.
     */
    public function update(User $user, Subscription $subscription): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->status === 'active' && $user->belongsToBusiness($subscription->business_id) && $user->isOwner($subscription->business_id);
    }

    public function cancel(User $user, Subscription $subscription): bool
    {
        return $this->update($user, $subscription);
    }
}
