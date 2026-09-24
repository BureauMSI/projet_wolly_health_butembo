<?php

namespace App\Policies;

use App\Models\CashMovement;
use App\Models\User;

class CashMovementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isBranchStaff() || $user->isAdmin();
    }

    public function view(User $user, CashMovement $movement): bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        return $user->belongsToBranch($movement->branch_id);
    }

    public function create(User $user): bool
    {
        return $user->isBranchStaff() || $user->isAdmin();
    }

    public function update(User $user, CashMovement $movement): bool
    {
        return $this->create($user)
            && $this->view($user, $movement)
            && $movement->sale_id === null
            && $movement->commission_id === null;
    }

    public function delete(User $user, CashMovement $movement): bool
    {
        return $this->update($user, $movement);
    }
}
