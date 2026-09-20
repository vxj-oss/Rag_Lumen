<?php

namespace App\Support;

use App\Models\User;

class RagAccessScope
{
    public static function accessibleProjectIds(User $user): ?array
    {
        return ProjectScope::accessibleProjectIds($user);
    }
}
