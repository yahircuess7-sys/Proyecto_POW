<?php

namespace App\Policies;

use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\User;

class SaleReturnPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->status === 'active';
    }

    public function view(User $user, SaleReturn $saleReturn): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->status === 'active' && $user->belongsToBusiness($saleReturn->business_id);
    }

    public function create(User $user, ?Sale $sale = null): bool
    {
        if ($user->status !== 'active') {
            return false;
        }

        if ($sale) {
            return $user->belongsToBusiness($sale->business_id) && $sale->canBeRefunded();
        }

        return true;
    }
}
