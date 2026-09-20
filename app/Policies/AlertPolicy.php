<?php

namespace App\Policies;

use App\Models\User;

class AlertPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isManager() || $user->isLeader() || $user->isEmployee();
    }
}
