<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'nombre', 'identificacion_fiscal', 'nombre_contacto', 'correo', 'telefono', 'sector', 'estado',
])]
class Client extends Model
{
    use SoftDeletes;

    protected $table = 'clientes';

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'cliente_id');
    }
}
