<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistroHora extends Model
{
    protected $table = 'registros_horas';
    public $timestamps = false;

    protected $fillable = [
        'practica_id',
        'actividad_id',
        'fecha',
        'horas',
        'descripcion',
        'estado',
        'revisado_por_tutor_id',
        'fecha_revision',
        'observacion_revision',
        'created_at',
    ];

    protected $casts = [
        'fecha' => 'date',
        'horas' => 'decimal:2',
        'fecha_revision' => 'datetime',
        'created_at' => 'datetime',
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
}

