<?php

namespace App\Policies;

use App\Models\Sale;
use App\Models\User;

class SalePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isBranchStaff() || $user->isAdmin();
    }

    public function view(User $user, Sale $sale): bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        return $user->belongsToBranch($sale->branch_id);
    }

    public function create(User $user): bool
    {
        return $user->canRecordSales();
    }

    public function update(User $user, Sale $sale): bool
    {
        return $this->create($user) && $this->view($user, $sale);
    }

    public function delete(User $user, Sale $sale): bool
    {
        return $this->update($user, $sale);
    }
}
