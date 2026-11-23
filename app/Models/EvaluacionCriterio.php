<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvaluacionCriterio extends Model
{
    protected $table = 'evaluaciones_criterio';

    protected $fillable = [
        'criterio_id',
        'informe_avance_id',
        'practica_id',
        'tutor_id',
        'puntuacion',
        'comentario',
    ];

    protected $casts = [
        'puntuacion' => 'decimal:2',
    ];

    public function criterio()
    {
        return $this->belongsTo(CriterioRubrica::class, 'criterio_id');
    }

    public function informe()
    {
        return $this->belongsTo(InformeAvance::class, 'informe_avance_id');
    }

    public function practica()
    {
        return $this->belongsTo(Practica::class, 'practica_id');
    }

    public function tutor()
    {
        return $this->belongsTo(DocenteTutor::class, 'tutor_id');
    }
}

