<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CronogramaActividad extends Model
{
    protected $table = 'cronograma_actividades';
    public $timestamps = false;

    protected $fillable = [
        'practica_id',
        'titulo',
        'objetivo',
        'descripcion',
        'fecha_inicio',
        'fecha_fin',
        'fecha_completada',
        'horas_planificadas',
        'estado',
        'observacion',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'fecha_completada' => 'date',
        'horas_planificadas' => 'decimal:2',
    ];

    public function practica()
    {
        return $this->belongsTo(Practica::class, 'practica_id');
    }

    public function registrosHoras()
    {
        return $this->hasMany(RegistroHora::class, 'actividad_id');
    }
}

