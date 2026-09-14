<?php

namespace App\Policies;

use App\Models\RagDocument;
use App\Models\User;
use App\Support\Enums\RoleName;

class RagDocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole([
            RoleName::Administrator->value,
            RoleName::Manager->value,
            RoleName::ProjectLead->value,
            RoleName::Employee->value,
        ]);
    }

    public function view(User $user, RagDocument $ragDocument): bool
    {
        if ($user->hasRole([RoleName::Administrator->value, RoleName::Manager->value])) {
            return true;
        }

        if ($ragDocument->project_id === null) {
            return true;
        }

        return $user->can('view', $ragDocument->project);
    }

    public function create(User $user): bool
    {
        return $user->hasRole([
            RoleName::Administrator->value,
            RoleName::Manager->value,
            RoleName::ProjectLead->value,
        ]);
    }

    public function update(User $user, RagDocument $ragDocument): bool
    {
        return $user->hasRole([RoleName::Administrator->value, RoleName::Manager->value]);
    }

    public function delete(User $user, RagDocument $ragDocument): bool
    {
        return $user->hasRole(RoleName::Administrator->value);
    }
}
