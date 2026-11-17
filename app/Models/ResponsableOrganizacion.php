<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResponsableOrganizacion extends Model
{
    protected $table = 'responsables_organizacion';
    public $timestamps = false;

    protected $fillable = [
        'organizacion_id',
        'usuario_id',
        'nombres',
        'apellidos',
        'cargo',
        'correo',
        'telefono',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function organizacion()
    {
        return $this->belongsTo(OrganizacionReceptora::class, 'organizacion_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function getNombreCompletoAttribute()
    {
        return "{$this->nombres} {$this->apellidos}";
    }
}

