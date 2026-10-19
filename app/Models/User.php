<?php

namespace App\Models;

use App\Support\Enums\RoleName;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'notify_by_email', 'notify_in_app', 'is_active', 'force_password_change', 'auth_version'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable;

    protected $attributes = ['is_active' => true, 'force_password_change' => false, 'auth_version' => 1, 'notify_in_app' => true];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'notify_by_email' => 'boolean',
            'notify_in_app' => 'boolean',
            'is_active' => 'boolean',
            'force_password_change' => 'boolean',
            'auth_version' => 'integer',
        ];
    }

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class, 'usuario_id');
    }

    public function notificationChannels(): array
    {
        if (! $this->is_active) return [];
        return array_values(array_filter([$this->notify_in_app ? 'database' : null, $this->notify_by_email ? 'mail' : null]));
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(RoleName::Administrator->value);
    }

    public function isManager(): bool
    {
        return $this->hasRole(RoleName::Manager->value);
    }

    public function isLeader(): bool
    {
        return $this->hasRole(RoleName::ProjectLead->value);
    }

    public function isEmployee(): bool
    {
        return $this->hasRole(RoleName::Employee->value);
    }

    public function isPlainEmployee(): bool
    {
        return $this->hasRole(RoleName::Employee->value)
            && ! $this->hasRole([
                RoleName::Administrator->value,
                RoleName::Manager->value,
                RoleName::ProjectLead->value,
            ]);
    }
}
