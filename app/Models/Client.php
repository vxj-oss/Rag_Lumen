<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name', 'tax_id', 'contact_name', 'email', 'phone', 'sector', 'status',
])]
class Client extends Model
{
    use SoftDeletes;

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
