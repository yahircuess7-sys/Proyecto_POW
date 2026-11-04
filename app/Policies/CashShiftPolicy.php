<?php

namespace App\Policies;

use App\Models\CashShift;
use App\Models\User;

class CashShiftPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->status === 'active';
    }

    public function view(User $user, CashShift $shift): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->status === 'active' && $user->belongsToBusiness($shift->business_id);
    }

    public function create(User $user): bool
    {
        return $user->status === 'active';
    }

    public function update(User $user, CashShift $shift): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Solo el usuario que abrió el turno o un administrador pueden operar en él mientras esté abierto
        return $user->status === 'active'
            && $user->belongsToBusiness($shift->business_id)
            && ($user->id === $shift->user_id || $user->isAdmin($shift->business_id))
            && $shift->isOpen();
    }

    public function close(User $user, CashShift $shift): bool
    {
        return $this->update($user, $shift);
    }
}
