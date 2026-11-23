<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JustificacionAsistencia extends Model
{
    protected $table = 'justificaciones_asistencia';

    protected $fillable = [
        'practica_id',
        'estudiante_id',
        'fecha',
        'tipo',
        'horas_reales',
        'motivo',
        'ruta_documento',
        'estado',
        'observacion_resolucion',
        'revisada_por_coordinador_id',
        'fecha_resolucion',
    ];

    protected $casts = [
        'fecha' => 'date',
        'fecha_resolucion' => 'datetime',
        'horas_reales' => 'decimal:2',
    ];

    public function practica()
    {
        return $this->belongsTo(Practica::class, 'practica_id');
    }

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'estudiante_id');
    }

    public function coordinador()
    {
        return $this->belongsTo(Coordinador::class, 'revisada_por_coordinador_id');
    }
}

