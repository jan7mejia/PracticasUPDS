<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistorialEstadoSolicitud extends Model
{
    protected $table = 'historial_estados_solicitud';
    public $timestamps = false;

    protected $fillable = [
        'solicitud_id',
        'estado_anterior',
        'estado_nuevo',
        'motivo',
        'cambiado_por',
        'fecha_cambio',
    ];

    protected $casts = [
        'fecha_cambio' => 'datetime',
    ];

    public function solicitud()
    {
        return $this->belongsTo(SolicitudPractica::class, 'solicitud_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'cambiado_por');
    }
}

