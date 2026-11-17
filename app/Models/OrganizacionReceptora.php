<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrganizacionReceptora extends Model
{
    protected $table = 'organizaciones_receptoras';

    protected $fillable = [
        'nombre',
        'nit',
        'rubro',
        'direccion',
        'telefono',
        'correo',
        'estado',
    ];

    public function responsables()
    {
        return $this->hasMany(ResponsableOrganizacion::class, 'organizacion_id');
    }

    public function solicitudes()
    {
        return $this->hasMany(SolicitudPractica::class, 'organizacion_id');
    }
}

