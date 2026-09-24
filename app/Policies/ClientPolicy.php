<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isBranchStaff() || $user->isAdmin();
    }

    public function view(User $user, Client $client): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->canRegisterMembers();
    }

    public function update(User $user, Client $client): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, Client $client): bool
    {
        return $this->update($user, $client);
    }
}
