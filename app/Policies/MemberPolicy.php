<?php

namespace App\Policies;

use App\Models\Member;
use App\Models\User;

class MemberPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isBranchStaff() || $user->isAdmin();
    }

    public function view(User $user, Member $member): bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        return $user->belongsToBranch($member->registration_branch_id);
    }

    public function create(User $user): bool
    {
        return $user->canRegisterMembers();
    }

    public function update(User $user, Member $member): bool
    {
        if (! $user->canRegisterMembers()) {
            return false;
        }

        return $user->belongsToBranch($member->registration_branch_id);
    }

    public function delete(User $user, Member $member): bool
    {
        return $user->isAdmin();
    }
}
