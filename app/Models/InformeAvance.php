<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InformeAvance extends Model
{
    protected $table = 'informes_avance';

    protected $fillable = [
        'practica_id',
        'actividad_id',
        'version',
        'titulo',
        'periodo_inicio',
        'periodo_fin',
        'resumen',
        'ruta_privada',
        'estado',
        'fecha_envio',
        'revisado_por_tutor_id',
        'fecha_revision',
        'observaciones_tutor',
        'calificacion',
    ];

    protected $casts = [
        'periodo_inicio' => 'date',
        'periodo_fin' => 'date',
        'fecha_envio' => 'datetime',
        'fecha_revision' => 'datetime',
        'version' => 'integer',
        'calificacion' => 'decimal:2',
    ];

    public function practica()
    {
        return $this->belongsTo(Practica::class, 'practica_id');
    }

    public function actividad()
    {
        return $this->belongsTo(CronogramaActividad::class, 'actividad_id');
    }

    public function revisor()
    {
        return $this->belongsTo(DocenteTutor::class, 'revisado_por_tutor_id');
    }

    public function observaciones()
    {
        return $this->hasMany(ObservacionTutor::class, 'informe_id');
    }

    public function evaluacionesCriterio()
    {
        return $this->hasMany(EvaluacionCriterio::class, 'informe_avance_id');
    }
}

