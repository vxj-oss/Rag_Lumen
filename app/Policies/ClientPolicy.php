<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isManager() || $user->isLeader();
    }

    public function view(User $user, Client $client): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    public function update(User $user, Client $client): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    public function delete(User $user, Client $client): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, Client $client): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, Client $client): bool
    {
        return $user->isAdmin();
    }
}
