<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeSanction extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['fecha_hechos' => 'date', 'monto' => 'decimal:2'];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'empleado_id');
    }
}
