<?php

namespace App\Models;

use App\Support\Enums\Priority;
use App\Support\Enums\ProjectStatus;
use App\Support\Enums\ProjectType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'code', 'name', 'description', 'type', 'client_id', 'start_date', 'estimated_end_date',
    'actual_end_date', 'status', 'priority', 'responsible_employee_id', 'manager_employee_id', 'budget', 'observations',
])]
class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'type' => ProjectType::class,
            'status' => ProjectStatus::class,
            'priority' => Priority::class,
            'start_date' => 'date',
            'estimated_end_date' => 'date',
            'actual_end_date' => 'date',
            'budget' => 'decimal:2',
            'risk_calculated_at' => 'datetime',
        ];
    }

    public function responsibleEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'responsible_employee_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_employee_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function areas(): BelongsToMany
    {
        return $this->belongsToMany(Area::class, 'project_areas')
            ->withPivot(['id', 'area_lead_id', 'budget_share'])
            ->withTimestamps();
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'project_members')
            ->using(ProjectMember::class)
            ->withPivot(['id', 'role_in_project', 'assigned_at', 'left_at', 'status'])
            ->withTimestamps();
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function ragDocuments(): HasMany
    {
        return $this->hasMany(RagDocument::class);
    }
}
