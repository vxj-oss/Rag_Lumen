<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[Fillable(['project_id', 'employee_id', 'role_in_project', 'assigned_at', 'left_at', 'status'])]
class ProjectMember extends Pivot
{
    protected $table = 'project_members';

    public $incrementing = true;

    protected function casts(): array
    {
        return [
            'assigned_at' => 'date',
            'left_at' => 'date',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
