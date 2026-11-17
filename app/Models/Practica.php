<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Practica extends Model
{
    protected $table = 'practicas';

    protected $fillable = [
        'solicitud_id',
        'coordinador_id',
        'tutor_id',
        'fecha_inicio',
        'fecha_fin_prevista',
        'fecha_fin_real',
        'horas_objetivo',
        'estado',
        'motivo_abandono',
        'observacion_cierre',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin_prevista' => 'date',
        'fecha_fin_real' => 'date',
        'horas_objetivo' => 'decimal:2',
    ];

    public function solicitud()
    {
        return $this->belongsTo(SolicitudPractica::class, 'solicitud_id');
    }

    public function coordinador()
    {
        return $this->belongsTo(Coordinador::class, 'coordinador_id');
    }

    public function tutor()
    {
        return $this->belongsTo(DocenteTutor::class, 'tutor_id');
    }

    public function actividades()
    {
        return $this->hasMany(CronogramaActividad::class, 'practica_id');
    }

    public function registrosHoras()
    {
        return $this->hasMany(RegistroHora::class, 'practica_id');
    }

    public function informes()
    {
        return $this->hasMany(InformeAvance::class, 'practica_id');
    }

    public function observaciones()
    {
        return $this->hasMany(ObservacionTutor::class, 'practica_id')->orderBy('fecha', 'desc');
    }

    public function evaluacionesSeguimiento()
    {
        return $this->hasMany(EvaluacionSeguimiento::class, 'practica_id')->orderBy('fecha', 'desc');
    }

    public function evaluacionFinal()
    {
        return $this->hasOne(EvaluacionFinal::class, 'practica_id');
    }

    public function evaluacionOrganizacion()
    {
        return $this->hasOne(EvaluacionOrganizacion::class, 'practica_id');
    }

    public function historial()
    {
        return $this->hasMany(HistorialEstadoPractica::class, 'practica_id')->orderBy('fecha_cambio', 'desc');
    }

    public function getHorasValidadasAttribute()
    {
        return $this->registrosHoras()->where('estado', 'validada')->sum('horas');
    }

    public function getPorcentajeHorasAttribute()
    {
        if ($this->horas_objetivo && $this->horas_objetivo > 0) {
            return min(100, round(($this->horas_validadas * 100) / $this->horas_objetivo, 1));
        }
        return 0;
    }
}

