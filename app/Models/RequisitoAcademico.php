<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequisitoAcademico extends Model
{
    protected $table = 'requisitos_academicos';
    public $timestamps = false;

    protected $fillable = [
        'carrera_id',
        'nombre',
        'descripcion',
        'obligatorio',
        'activo',
    ];

    protected $casts = [
        'obligatorio' => 'boolean',
        'activo' => 'boolean',
    ];

    public function carrera()
    {
        return $this->belongsTo(Carrera::class, 'carrera_id');
    }
}

