<?php

namespace App\Policies;

use App\Models\Area;
use App\Models\User;

class AreaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isManager() || $user->isLeader();
    }

    public function view(User $user, Area $area): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Area $area): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Area $area): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, Area $area): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, Area $area): bool
    {
        return $user->isAdmin();
    }
}
