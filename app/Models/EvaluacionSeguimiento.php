<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvaluacionSeguimiento extends Model
{
    protected $table = 'evaluaciones_seguimiento';
    public $timestamps = false;

    protected $fillable = [
        'practica_id',
        'tutor_id',
        'fecha',
        'periodo',
        'resultado',
        'calificacion',
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

