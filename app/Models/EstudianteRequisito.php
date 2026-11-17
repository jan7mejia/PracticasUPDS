<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstudianteRequisito extends Model
{
    protected $table = 'estudiante_requisitos';
    public $timestamps = false;

    protected $fillable = [
        'estudiante_id',
        'requisito_id',
        'estado',
        'observacion',
        'verificado_por',
        'fecha_verificacion',
    ];

    protected $casts = [
        'fecha_verificacion' => 'datetime',
    ];

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'estudiante_id');
    }

    public function requisito()
    {
        return $this->belongsTo(RequisitoAcademico::class, 'requisito_id');
    }

    public function verificadoPor()
    {
        return $this->belongsTo(User::class, 'verificado_por');
    }
}

