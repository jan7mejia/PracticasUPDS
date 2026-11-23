<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncidenciaPractica extends Model
{
    protected $table = 'incidencias_practica';

    protected $fillable = [
        'practica_id',
        'tipo',
        'descripcion',
        'bloquea_cierre',
        'resuelta',
        'resolucion',
        'registrada_por_usuario_id',
    ];

    protected $casts = [
        'bloquea_cierre' => 'boolean',
        'resuelta' => 'boolean',
    ];

    public function practica()
    {
        return $this->belongsTo(Practica::class, 'practica_id');
    }

    public function registrador()
    {
        return $this->belongsTo(User::class, 'registrada_por_usuario_id');
    }
}

