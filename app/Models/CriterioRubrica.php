<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CriterioRubrica extends Model
{
    protected $table = 'criterios_rubrica';

    protected $fillable = [
        'rubrica_id',
        'nombre',
        'descripcion',
        'ponderacion',
        'puntuacion_maxima',
    ];

    protected $casts = [
        'ponderacion' => 'decimal:2',
        'puntuacion_maxima' => 'decimal:2',
    ];

    public function rubrica()
    {
        return $this->belongsTo(RubricaActividad::class, 'rubrica_id');
    }

    public function evaluaciones()
    {
        return $this->hasMany(EvaluacionCriterio::class, 'criterio_id');
    }
}

