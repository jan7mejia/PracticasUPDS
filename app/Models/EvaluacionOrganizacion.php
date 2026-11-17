<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvaluacionOrganizacion extends Model
{
    protected $table = 'evaluaciones_organizacion';
    public $timestamps = false;

    protected $fillable = [
        'practica_id',
        'responsable_id',
        'registrada_por',
        'fecha',
        'desempeno',
        'cumplimiento',
        'comentario',
        'recomendacion',
        'ruta_evidencia',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function practica()
    {
        return $this->belongsTo(Practica::class, 'practica_id');
    }

    public function responsable()
    {
        return $this->belongsTo(ResponsableOrganizacion::class, 'responsable_id');
    }

    public function registrador()
    {
        return $this->belongsTo(User::class, 'registrada_por');
    }
}

