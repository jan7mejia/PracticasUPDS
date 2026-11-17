<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistorialEstadoPractica extends Model
{
    protected $table = 'historial_estados_practica';
    public $timestamps = false;

    protected $fillable = [
        'practica_id',
        'estado_anterior',
        'estado_nuevo',
        'motivo',
        'cambiado_por',
        'fecha_cambio',
    ];

    protected $casts = [
        'fecha_cambio' => 'datetime',
    ];

    public function practica()
    {
        return $this->belongsTo(Practica::class, 'practica_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'cambiado_por');
    }
}

