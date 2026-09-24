<?php

namespace App\Policies;

use App\Models\User;

class PlanSettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, $setting = null): bool
    {
        return $user->isAdmin();
    }
}
