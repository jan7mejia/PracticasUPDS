<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Estudiante extends Model
{
    protected $table = 'estudiantes';

    protected $fillable = [
        'usuario_id',
        'carrera_id',
        'ru',
        'ci',
        'nombres',
        'apellidos',
        'telefono',
        'semestre',
        'estado',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function usuario()
    {
        return $this->user();
    }

    public function carrera()
    {
        return $this->belongsTo(Carrera::class, 'carrera_id');
    }

    public function requisitos()
    {
        return $this->hasMany(EstudianteRequisito::class, 'estudiante_id');
    }

    public function solicitudes()
    {
        return $this->hasMany(SolicitudPractica::class, 'estudiante_id');
    }

    public function getNombreCompletoAttribute()
    {
        return "{$this->nombres} {$this->apellidos}";
    }
}

