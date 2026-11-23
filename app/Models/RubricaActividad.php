<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RubricaActividad extends Model
{
    protected $table = 'rubricas_actividad';

    protected $fillable = [
        'actividad_id',
        'titulo',
        'descripcion',
    ];

    public function actividad()
    {
        return $this->belongsTo(CronogramaActividad::class, 'actividad_id');
    }

    public function criterios()
    {
        return $this->hasMany(CriterioRubrica::class, 'rubrica_id');
    }
}

