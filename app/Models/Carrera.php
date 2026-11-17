<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Carrera extends Model
{
    protected $table = 'carreras';
    public $timestamps = false;

    protected $fillable = [
        'codigo',
        'nombre',
        'activa',
    ];

    protected $casts = [
        'activa' => 'boolean',
    ];

    public function estudiantes()
    {
        return $this->hasMany(Estudiante::class, 'carrera_id');
    }

    public function requisitos()
    {
        return $this->hasMany(RequisitoAcademico::class, 'carrera_id');
    }
}

