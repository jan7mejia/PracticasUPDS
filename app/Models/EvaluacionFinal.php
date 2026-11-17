<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvaluacionFinal extends Model
{
    protected $table = 'evaluaciones_finales';
    public $timestamps = false;

    protected $fillable = [
        'practica_id',
        'tutor_id',
        'fecha',
        'calificacion',
        'resultado',
        'comentario',
    ];

    protected $casts = [
        'fecha' => 'date',
        'calificacion' => 'decimal:2',
    ];

    public function practica()
    {
        return $this->belongsTo(Practica::class, 'practica_id');
    }

    public function tutor()
    {
        return $this->belongsTo(DocenteTutor::class, 'tutor_id');
    }
}

