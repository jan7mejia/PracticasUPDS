<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Practica extends Model
{
    protected $table = 'practicas';

    protected $fillable = [
        'solicitud_id',
        'coordinador_id',
        'tutor_id',
        'fecha_inicio',
        'fecha_fin_prevista',
        'fecha_fin_real',
        'horas_objetivo',
        'horas_programadas',
        'estado',
        'version_reglamento',
        'cronograma_aprobado',
        'cronograma_aprobado_at',
        'cronograma_aprobado_por',
        'faltas_justificadas',
        'incompletas_justificadas',
        'motivo_abandono',
        'observacion_cierre',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin_prevista' => 'date',
        'fecha_fin_real' => 'date',
        'horas_objetivo' => 'decimal:2',
        'horas_programadas' => 'decimal:2',
        'cronograma_aprobado' => 'boolean',
        'cronograma_aprobado_at' => 'datetime',
    ];

    public function solicitud()
    {
        return $this->belongsTo(SolicitudPractica::class, 'solicitud_id');
    }

    public function coordinador()
    {
        return $this->belongsTo(Coordinador::class, 'coordinador_id');
    }

    public function tutor()
    {
        return $this->belongsTo(DocenteTutor::class, 'tutor_id');
    }

    public function actividades()
    {
        return $this->hasMany(CronogramaActividad::class, 'practica_id');
    }

    public function registrosHoras()
    {
        return $this->hasMany(RegistroHora::class, 'practica_id');
    }

    public function informes()
    {
        return $this->hasMany(InformeAvance::class, 'practica_id');
    }

    public function observaciones()
    {
        return $this->hasMany(ObservacionTutor::class, 'practica_id')->orderBy('fecha', 'desc');
    }

    public function evaluacionesSeguimiento()
    {
        return $this->hasMany(EvaluacionSeguimiento::class, 'practica_id')->orderBy('fecha', 'desc');
    }

    public function evaluacionFinal()
    {
        return $this->hasOne(EvaluacionFinal::class, 'practica_id');
    }

    public function evaluacionOrganizacion()
    {
        return $this->hasOne(EvaluacionOrganizacion::class, 'practica_id');
    }

    public function historial()
    {
        return $this->hasMany(HistorialEstadoPractica::class, 'practica_id')->orderBy('fecha_cambio', 'desc');
    }

    public function justificaciones()
    {
        return $this->hasMany(JustificacionAsistencia::class, 'practica_id')->orderBy('fecha', 'desc');
    }

    public function incidencias()
    {
        return $this->hasMany(IncidenciaPractica::class, 'practica_id')->orderBy('created_at', 'desc');
    }

    public function aprobadorCronograma()
    {
        return $this->belongsTo(Coordinador::class, 'cronograma_aprobado_por');
    }

    public function evaluacionesCriterios()
    {
        return $this->hasMany(EvaluacionCriterio::class, 'practica_id');
    }

    public function esHistorica(): bool
    {
        return $this->version_reglamento === 'v1_historico';
    }

    /**
     * Días hábiles (Lunes a Viernes) entre dos fechas inclusive.
     */
    public static function contarDiasHabiles($fechaInicio, $fechaFin): int
    {
        $inicio = \Carbon\Carbon::parse($fechaInicio);
        $fin = \Carbon\Carbon::parse($fechaFin);
        if ($inicio->gt($fin)) {
            return 0;
        }

        $diasHabiles = 0;
        $curr = $inicio->copy();
        while ($curr->lte($fin)) {
            if (!$curr->isWeekend()) {
                $diasHabiles++;
            }
            $curr->addDay();
        }
        return $diasHabiles;
    }

    /**
     * Calcula fecha fin prevista exacta de 3 meses calendario para nuevas prácticas.
     */
    public static function calcularFechaFin3Meses($fechaInicio): string
    {
        return \Carbon\Carbon::parse($fechaInicio)->addMonths(3)->format('Y-m-d');
    }

    /**
     * Calcula horas requeridas = días hábiles * 4 para nuevas prácticas.
     */
    public static function calcularHorasRequeridasV2($fechaInicio, $fechaFin = null): float
    {
        $fin = $fechaFin ?: self::calcularFechaFin3Meses($fechaInicio);
        $dias = self::contarDiasHabiles($fechaInicio, $fin);
        return round($dias * 4.0, 2);
    }

    /**
     * Nota final calculada automáticamente por rúbricas o promedio ponderado de actividades.
     */
    public function calcularNotaFinalAutomatica(): ?float
    {
        $actividades = $this->actividades()->with('rubrica.criterios.evaluaciones')->get();
        if ($actividades->isEmpty()) {
            return null;
        }

        $totalNotas = 0;
        $count = 0;
        foreach ($actividades as $act) {
            $notaAct = $act->calcularNota();
            if ($notaAct !== null) {
                $totalNotas += $notaAct;
                $count++;
            }
        }

        return $count > 0 ? round($totalNotas / $count, 2) : null;
    }

    public function getHorasValidadasAttribute()
    {
        return $this->registrosHoras()->where('estado', 'validada')->sum('horas');
    }

    public function getPorcentajeHorasAttribute()
    {
        if ($this->horas_objetivo && $this->horas_objetivo > 0) {
            return min(100, round(($this->horas_validadas * 100) / $this->horas_objetivo, 1));
        }
        return 0;
    }

    public function getDuracionMesesAttribute()
    {
        return 3; // Reglamentario UPDS: 3 meses (240 horas / 4 hrs/día = 60 jornadas hábiles)
    }
}

