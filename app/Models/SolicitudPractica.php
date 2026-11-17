<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SolicitudPractica extends Model
{
    protected $table = 'solicitudes_practica';

    protected $fillable = [
        'estudiante_id',
        'organizacion_id',
        'periodo_id',
        'fecha_solicitud',
        'estado',
        'observacion',
        'revisada_por_coordinador_id',
        'fecha_revision',
    ];

    protected $casts = [
        'fecha_solicitud' => 'datetime',
        'fecha_revision' => 'datetime',
    ];

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'estudiante_id');
    }

    public function organizacion()
    {
        return $this->belongsTo(OrganizacionReceptora::class, 'organizacion_id');
    }

    public function periodo()
    {
        return $this->belongsTo(PeriodoAcademico::class, 'periodo_id');
    }

    public function revisor()
    {
        return $this->belongsTo(Coordinador::class, 'revisada_por_coordinador_id');
    }

    public function documentos()
    {
        return $this->hasMany(DocumentoSolicitud::class, 'solicitud_id');
    }

    public function historial()
    {
        return $this->hasMany(HistorialEstadoSolicitud::class, 'solicitud_id')->orderBy('fecha_cambio', 'desc');
    }

    public function practica()
    {
        return $this->hasOne(Practica::class, 'solicitud_id');
    }
}

