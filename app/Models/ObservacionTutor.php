<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ObservacionTutor extends Model
{
    protected $table = 'observaciones_tutor';
    public $timestamps = false;

    protected $fillable = [
        'practica_id',
        'tutor_id',
        'informe_id',
        'detalle',
        'fecha',
    ];

    protected $casts = [
        'fecha' => 'datetime',
    ];

    public function practica()
    {
        return $this->belongsTo(Practica::class, 'practica_id');
    }

    public function tutor()
    {
        return $this->belongsTo(DocenteTutor::class, 'tutor_id');
    }

    public function informe()
    {
        return $this->belongsTo(InformeAvance::class, 'informe_id');
    }
}

