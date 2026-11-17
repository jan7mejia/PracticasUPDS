<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocenteTutor extends Model
{
    protected $table = 'docentes_tutores';
    public $timestamps = false;

    protected $fillable = [
        'usuario_id',
        'nombres',
        'apellidos',
        'especialidad',
        'telefono',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function practicas()
    {
        return $this->hasMany(Practica::class, 'tutor_id');
    }

    public function getNombreCompletoAttribute()
    {
        return "{$this->nombres} {$this->apellidos}";
    }
}

