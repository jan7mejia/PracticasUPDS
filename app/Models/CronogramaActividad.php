<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CronogramaActividad extends Model
{
    protected $table = 'cronograma_actividades';
    public $timestamps = false;

    protected $fillable = [
        'practica_id',
        'titulo',
        'objetivo',
        'descripcion',
        'entregable_esperado',
        'aprobada_coordinacion',
        'rubrica_definida',
        'fecha_inicio',
        'fecha_fin',
        'fecha_completada',
        'horas_planificadas',
        'estado',
        'observacion',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'fecha_completada' => 'date',
        'horas_planificadas' => 'decimal:2',
        'aprobada_coordinacion' => 'boolean',
        'rubrica_definida' => 'boolean',
    ];

    public function practica()
    {
        return $this->belongsTo(Practica::class, 'practica_id');
    }

    public function registrosHoras()
    {
        return $this->hasMany(RegistroHora::class, 'actividad_id');
    }

    public function rubrica()
    {
        return $this->hasOne(RubricaActividad::class, 'actividad_id');
    }

    public function informes()
    {
        return $this->hasMany(InformeAvance::class, 'actividad_id');
    }

    /**
     * Retorna el estado automatizado de entrega del informe vinculado:
     * - 'aprobado': informe entregado y revisado/aprobado por el tutor
     * - 'en_revision': informe entregado, en espera de revisión por el tutor
     * - 'observado': informe observado por el tutor (requiere corrección)
     * - 'vencido': plazo vencido sin entrega de informe
     * - 'pendiente': dentro del plazo, aún no entregado
     */
    public function getEstadoEntregaAttribute(): string
    {
        $ultimoInforme = $this->ultimo_informe;
        if ($ultimoInforme) {
            if ($ultimoInforme->estado === 'revisado') {
                return 'aprobado';
            }
            if ($ultimoInforme->estado === 'observado') {
                return 'observado';
            }
            if ($ultimoInforme->estado === 'enviado') {
                return 'en_revision';
            }
        }

        if ($this->fecha_fin && $this->fecha_fin->format('Y-m-d') < now()->toDateString() && $this->estado !== 'completada') {
            return 'vencido';
        }

        return 'pendiente';
    }

    public function getUltimoInformeAttribute(): ?InformeAvance
    {
        if ($this->relationLoaded('informes')) {
            return $this->informes->sortByDesc('version')->first();
        }
        return $this->informes()->orderBy('version', 'desc')->first();
    }

    /**
     * Calcula la nota de la actividad en base a su rúbrica y criterios evaluados.
     * Retorna un float (0-100) o null si aún no tiene criterios evaluados.
     * Si el plazo de la actividad ya venció y el estudiante nunca presentó informe,
     * se asigna automáticamente 0.00 por incumplimiento del plazo reglamentario.
     */
    public function calcularNota(): ?float
    {
        // 1. Regla estricta UPDS: Si el plazo de la actividad venció y no se entregó informe: Calificación 0.00 automática
        if ($this->estado_entrega === 'vencido') {
            return 0.00;
        }

        // 2. Si la actividad tiene informe evaluado formalmente por el tutor:
        $ultimoInf = $this->ultimo_informe;
        if ($ultimoInf && $ultimoInf->calificacion !== null) {
            return (float)$ultimoInf->calificacion;
        }

        // 3. Si tiene rúbrica con criterios evaluados:
        if ($this->rubrica) {
            $criterios = $this->rubrica->criterios()->with('evaluaciones')->get();
            if ($criterios->isNotEmpty()) {
                $notaTotal = 0;
                $evaluados = 0;
                foreach ($criterios as $criterio) {
                    $eval = $criterio->evaluaciones->sortByDesc('created_at')->first();
                    if ($eval) {
                        // Nota ponderada: (puntuacion / 100) * ponderacion
                        $notaTotal += ($eval->puntuacion * ($criterio->ponderacion / 100.0));
                        $evaluados++;
                    }
                }

                if ($evaluados > 0) {
                    return round($notaTotal, 2);
                }
            }
        }

        // 4. Si la fecha límite expiró sin entrega y no está completada:
        $hoy = now()->toDateString();
        $tieneInformes = $this->relationLoaded('informes') ? $this->informes->isNotEmpty() : $this->informes()->exists();
        if ($this->fecha_fin && $this->fecha_fin->format('Y-m-d') < $hoy && !$tieneInformes && $this->estado !== 'completada') {
            return 0.00;
        }

        return null;
    }
}

