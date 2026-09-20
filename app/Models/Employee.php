<?php

namespace App\Models;

use App\Support\Enums\EmployeeSpecialty;
use App\Support\Enums\EmployeeStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'user_id', 'first_name', 'last_name', 'email', 'phone',
    'position', 'specialty', 'area_id', 'status', 'hire_date',
])]
class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'specialty' => EmployeeSpecialty::class,
            'status' => EmployeeStatus::class,
            'hire_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function fullName(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_members')
            ->using(ProjectMember::class)
            ->withPivot(['id', 'role_in_project', 'assigned_at', 'left_at', 'status'])
            ->withTimestamps();
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }
}
